<?php
/** ************************************ cbeads/menu.php ****************************************
 *
 *  This controller creates the menu by looking at the menu structure stored in the database.
 *  If no menu structure is stored then it generates the menu from the order in which
 *  applications and functions are stored in the system.
 *
 ** Changelog:
 *
 *  2010/06/20 - Markus
 *  - Menu is now auto generated and the data passed to the view. For now it is just a basic auto
 *    generation with no menu
 *    ordering taken into account.
 *
 *  2010/06/24 - Markus
 *  - Changed from using the function 'name' to using the controller name in constructing the 
 *    'namespace/controller' value
 *
 *  2010/07/19 - Markus
 *  - Added doctype to the start of the produced html document.
 *
 *  2010/08/24 - Markus
 *  - Now passing username to menu view file.
 *
 *  2011/03/24 - Markus
 *  - Updated the function to use the menu_group and menu_item tables for generating the menu.
 *    Basically rewrote most of the function.
 *
 *  2011/04/13 - Markus
 *  - Added function _update_menu_structure which is used to update the menu structure to include
 *    newly added applications and functions.
 *
 *  2011/08/08 - Markus
 *  - Removed old code.
 *  - Removed include for the cbeads/routines and updated functions called to use those in
 *    cbeads helper.
 * 
 *************************************************************************************************/

class Menu extends CI_Controller {

    private $function_ids;

    function index()
    {
        $this->load->helper(array('url', 'html'));
        //echo doctype();
        
        $function_ids = array();
        $found = FALSE;
        
        // Get the current team role and make sure the user has access to it
        $cur_teamrole = $this->session->userdata('~team-role');
        $id = $this->session->userdata('~id');
        
        $q = Doctrine_Query::create()
                ->select('t.team_role_id')
                ->from('cbeads\User_team_role t')
                ->where('t.user_id = ?', $id);
        $team_roles = $q->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
        foreach($team_roles as $tr)
            if($tr['team_role_id'] == $cur_teamrole) $found = TRUE;
        
        if($found != TRUE)
        {
            echo "Error: You do not have the requested team-role available to you. Please report this error.";
            return;
        }

        // Now get all functions for this team role.
        $q = Doctrine_Query::create()
                ->select('t.function_id')
                ->from('cbeads\Team_role_function t')
                ->where('t.team_role_id = ?', $cur_teamrole);
        $results = $q->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
        foreach($results as $result)
            $function_ids[] = $result['function_id'];
        $this->function_ids = $function_ids;
        // If there are none then show a message.
        if(count($function_ids) == 0)
        {
            echo "There are no functions assigned to this team-role.";
            return;
        }
        
        // Get the functions (names) and applications (names) to use in the menu.
        $q = Doctrine_Query::create()
            ->select('a.name, a.id, a.namespace')
            ->from("cbeads\Application a, a.Functions f")
            ->whereIn('f.id', $function_ids);
        $applications = $q->fetchArray();
        $app_by_id_lookup = array();
        foreach($applications as $application)
        {
            $app_by_id_lookup[$application['id']] = $application;
        }
        //echo '<pre>' . var_dump($app_by_id_lookup) . '</pre>';
        $q = Doctrine_Query::create()
            ->select('f.name, f.id, f.application_id, f.controller_name')
            ->from("cbeads\Function_ f")
            ->whereIn('f.id', $function_ids);
        $functions = $q->fetchArray();
        $func_by_id_lookup = array();
        $app_by_func_id_lookup = array();
        foreach($functions as $function)
        {
            $func_by_id_lookup[$function['id']] = $function;
            $app_by_func_id_lookup[$function['id']] = $app_by_id_lookup[$function['application_id']];
        }
        
        // Get the menu structure. First check if there is a team-role specific one. If not, then check if there is a
        // team specific one. If not then check if there is a global one. If all that fails, work out the menu 
        // structure from the applications and functions tables.
        $menu_structure = cbeads_get_menu_order(NULL, NULL);
        if(count($menu_structure) == 0)
        {
            // Must work out order from the app/function tables and format the data the same way
            // as it is stored in the menu tables.
            $q = Doctrine_Query::create()
                ->select('a.id, f.id, f.name')
                ->from('cbeads\Application a, a.Functions f')
                ->whereIn('f.id', $function_ids);
            $apps = $q->fetchArray();
            $menu_structure = array();
            foreach($apps as $app)
            {
                $group = array('application_id' => $app['id'], 'name' => NULL, 'id' => 'new' . $app['id'], 'show_group_header' => TRUE, 'Items' => array());
                foreach($app['Functions'] as $function)
                {
                    $group['Items'][] = array('function_id' => $function['id'], 'name' => NULL, 'custom_url' => NULL, 'item_id' => 'new' . $function['id']);
                    //$menu[] = array('func_id' => $function['id'], 'func_name' => NULL, 'group_name' => NULL, 'cust_grp_id' => NULL, 'stand_alone' => NULL);
                }
                $menu_structure[] = $group;
            }
        }
        else    // Menu order is stored. Need to check if new applications or functions have been added since the menu order was last saved.
        {
            $menu_structure = $this->_update_menu_structure($menu_structure);
        }
        
        $groups = array();
        // app name, show header, items
        // items: name, url, 
        // Collect the groups and items into a useable structure. Need to remove items that match functions which
        // cannot be accessed. If a group has no items to display, then the group shouldn't be kept.
        foreach($menu_structure as $group_structure)
        {
            $items = array();
            $application = NULL;
            $name = NULL;
            if($group_structure['application_id'] !== NULL)     // Group is for a specific app.
            {
                if(isset($app_by_id_lookup[$group_structure['application_id']]))
                    $application = $app_by_id_lookup[$group_structure['application_id']];
                else
                    continue;       // App is not found or not assigned to the current team/role.
            }
            foreach($group_structure['Items'] as $item)
            {
                $add_item = array();
                if(in_array($item['function_id'], $function_ids))   // Item is associated with a function.
                {
                    $function = $func_by_id_lookup[$item['function_id']];
                    if($item['name'] !== NULL)
                        $add_item['name'] = cbeads_make_title_text($item['name']);
                    else
                    {
                        $add_item['name'] = cbeads_make_title_text($function['name']);
                    }
                    $add_item['url'] = site_url(strtolower($application['namespace'] . '/' . $function['controller_name']) . '/index');
                }
                else if($item['function_id'] === NULL) // Not associated with a function.
                {
                    $add_item['name'] = cbeads_make_title_text($item['name']);
                    $add_item['url'] = $item['custom_url']; // Should be a fully qualified url.
                }
                if(!empty($add_item)) $items[] = $add_item;
            }
            // Only add group if there are items.
            if(!empty($items))
            {
                if($application !== NULL)
                    $name = $group_structure['name'] !== NULL ? $group_structure['name'] : $application['name'];
                else
                    $name = $group_structure['name'];
                    
                $groups[] = array(
                    'name' => $name, 
                    'show_group_header' => $group_structure['show_group_header'] == 1 ? TRUE : FALSE,
                    'group_id' => $group_structure['id'],
                    'items' => $items
                );
            }
        }
        
        $data = array();
        
        $data['groups'] = $groups;
        $data['username'] = $this->session->userdata('~uname');
        //echo '<pre>'; var_dump($data); echo '</pre>';
        
        $this->load->view('cbeads/menu', $data);
    }
    
    // Makes sure the menu array provided is up to date for all groups and items that are 
    // related to applications/functions. Applications or functions may have been added
    // since the last time the menu was saved to the database. This will add those if they aren't
    // in the array.
    // menu - the menu structure to update.
    // Returns an array with the updated menu structure.
    function _update_menu_structure($menu)
    {
        // Creates a new menu array which includes all the new applications as groups and new 
        // functions as items.
        $q = Doctrine_Query::create()
            ->select('a.id, f.id, f.name')
            ->from('cbeads\Application a, a.Functions f')
            ->whereIn('f.id', $this->function_ids);
        $apps = $q->fetchArray();
        $app_by_id_lookup = array();
        $new_menu = $menu;
        // Add new applications if they aren't included in the saved menu structure.
        foreach($apps as $app)
        {
            $found = FALSE;
            foreach($menu as $grp)
                if($grp['application_id'] == $app['id']) $found = TRUE; 
            if(!$found)
                $new_menu[] = array('application_id' => $app['id'], 'name' => NULL, 'id' => 'new' . $app['id'], 'show_group_header' => TRUE, 'Items' => array());
            $app_by_id_lookup[$app['id']] = $app;
        }
        // Add new functions if they aren't included in the saved menu structure. 
        // Can only be done for groups associated with an application.
        for($i = 0; $i < count($new_menu); $i++)
        {
            $grp = $new_menu[$i];
            if(isset($app_by_id_lookup[$grp['application_id']]))
            {
                $app = $app_by_id_lookup[$grp['application_id']];
                $items = $grp['Items'];
                foreach($app['Functions'] as $function)
                {
                    $found = FALSE;
                    foreach($grp['Items'] as $item)
                        if($item['function_id'] == $function['id']) $found = TRUE;
                    if(!$found)
                        $items[] = array('function_id' => $function['id'], 'name' => NULL, 'custom_url' => NULL, 'item_id' => 'new' . $function['id']);
                }
                $new_menu[$i]['Items'] = $items;
            }
        }
        //nice_vardump($new_menu);
        return $new_menu;
    }
    
}