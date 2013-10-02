<?php

/** ******************************  cbeads/manage_menu.php  *************************************
 *
 *  Allows one to change the structure of the CBEADS menu on a global, team or team-role basis.
 *
 ** Changelog:
 *  2011/03/21 - Markus
 *  - Created Controller.
 *
 *  2011/04/13 - Markus
 *  - Renamed function _update_menu to _update_menu_structure and added code into it. New apps 
 *    and functions will now appear in the menu list provided to the client. 
 *
 ***********************************************************************************************/

class Manage_menu extends CI_Controller 
{

    function __construct()
    {
        parent::__construct();
        $this->load->helper(array('url'));
        include_once(APPPATH . 'libraries/Renderer.php');
    }
    
    function index()
    {
        // Obtain all teams and team-roles to allow the user to select for what team or
        // team-role they want to modify the menu.
        $teams = cbeads_get_teams();
        $teamroles = cbeads_get_teamroles();
        $data = array();
        foreach($teams as $team)    // don't need the description component.
        {
            $data['teams'][] = array('id' => $team['id'], 'name' => $team['name']);
        }
        //nice_vardump($teams);
        //nice_vardump($teamroles);
        $data['teams'] = json_encode($data['teams']);
        $data['teamroles'] = json_encode($teamroles);
        // Also need a list of all applications an functions.
        $apps = $this->_get_apps_and_functions();
        $app_funcs = array();
        foreach($apps as $app)
        {
            $app_funcs[$app['id']] = $app;
            unset($app_funcs[$app['id']]['id']);    // remove id since the key already contains it.
        }
        $data['app_functions'] = json_encode($app_funcs);
        
        $this->load->view('cbeads/manage_menu_view', $data);
    }
    
    
    // Returns the global menu structure if available. If not, has to resort to using the 
    // order of the applications and functions as they are stored in their respective tables.
    function get_global_menu()
    {
        $menu = cbeads_get_menu_order(NULL, NULL);
        //nice_vardump($menu);
        if(count($menu) != 0)
        {
            $menu = $this->_update_menu_structure($menu);
            $this->_clean_up_db_menu_data($menu);
            echo json_encode($menu);
            return;
        }
        // Must work out order from the app/function tables and format the data the same way
        // as it is stored in the menu tables.
        $q = Doctrine_Query::create()
            ->select('a.id, f.id, f.name')
            ->from('cbeads\Application a, a.Functions f');
        $apps = $q->fetchArray();
        $menu = array();
        foreach($apps as $app)
        {
            $group = array('application_id' => $app['id'], 'name' => NULL, 'group_id' => 'new_g' . $app['id'], 'show_group_header' => TRUE, 'items' => array());
            foreach($app['Functions'] as $function)
            {
                $group['items'][] = array('function_id' => $function['id'], 'name' => NULL, 'custom_url' => NULL, 'item_id' => 'new_i' . $function['id']);
                //$menu[] = array('func_id' => $function['id'], 'func_name' => NULL, 'group_name' => NULL, 'cust_grp_id' => NULL, 'stand_alone' => NULL);
            }
            $menu[] = $group;
        }
        
        echo json_encode($menu);
        return;
    }
    
    // Called when the user clicks the save button to save the menu structure.
    function save_menu()
    {
        //nice_vardump($_POST);
        //nice_vardump(cbeads_get_current_team_and_role());
        $group_items = $_POST['group_items'];
        $role_id = $_POST['role_id'];
        $team_id = $_POST['team_id'];
        //echo "role_id is $role_id and team_id is: $team_id<br>";
        $this->_save_groups($group_items, $team_id, $role_id);

    }
    
    // Saves the groups to the database. Adds, updates and delets groups/items as needed.
    // groups: array of groups and their items to process.
    // team_id: the id of the team that the menu has been defined for.
    // role_id: the id of the role that the menu has been defined for.
    private function _save_groups($groups, $team_id, $role_id)
    {
        if($team_id == 'null') $team_id = NULL;
        if($role_id == 'null') $role_id = NULL;
        // Current menu setup. Used for working out what needs to be deleted.
        $existing_groups = cbeads_get_menu_order($team_id, $role_id);
        $existing_ids = array();                    // Stores ids of existing groups.
        foreach($existing_groups as $group)
        {
            $existing_ids[$group['id']] = array('delete' => TRUE);
            $items = array();
            foreach($group['Items'] as $item)
            {
                $items[$item['id']] = TRUE;
            }
            $existing_ids[$group['id']]['items'] = $items;
        }
        
        $debug = "";        // var for debugging.
        
        $update_item_ids = array();     // Array for matching the id of a newly created item to the id used on the client side.
        $update_group_ids = array();    // Same thing for groups. This info is sent back to the client to update DOM ids.
        $counter = 0;
        foreach($groups as $group_data)
        {
            $grp = NULL;
            $counter++;
            $item_counter = 0;
            // If the group_id starts with 'new', it indicates this is a new group (not previously stored in the database).
            if(strpos($group_data['group_id'], 'new') !== FALSE)
            {
            //echo $group_data['group_id'] . " - new<br>";
                // Add new group with the appropriate order id.
                $grp = new cbeads\Menu_group();
                $grp->application_id = $group_data['application_id'] != 'null' ? $group_data['application_id'] : NULL;
                $grp->show_group_header = $group_data['show_group_header'] ? 1 : 0;
                $grp->position = $counter;
                $grp->team_id = $team_id;
                $grp->role_id = $role_id;
                $grp->name = $group_data['name'] != 'null' ? $group_data['name'] : NULL;
                $grp->save();
                $update_group_ids[$group_data['group_id']] = $grp->id;
                $debug .= "Adding new group: " . $group_data['group_id'] . 'which has the record id: "' . $grp->id . '"<br>';
            }
            else    // Find the matching group record and update it.
            {
            //echo $group_data['group_id'] . " - existing<br>";
                $grp = Doctrine_Core::getTable('cbeads\Menu_group')->find($group_data['group_id']);
                $grp->position = $counter;
                $grp->show_group_header = $group_data['show_group_header'] ? 1 : 0;
                $grp->name = $group_data['name'] != 'null' ? $group_data['name'] : NULL;
                $grp->save();
            }
            
            $cur_group_id = NULL;
            if(isset($existing_ids[$grp->id]))
            {
                $cur_group_id = $grp->id;
                $existing_ids[$cur_group_id]['delete'] = FALSE;     // Don't delete this group.
            }
            
            //echo "group id is: " . $grp->id . '<br>';
            
            // Process the items for the group (if there are any).
            if(isset($group_data['items']))
            {
                foreach($group_data['items'] as $item_data)
                {
                    $item = NULL;
                    $item_counter++;
                    //echo "item id is: " . $item_data['item_id'];
                    if(strpos($item_data['item_id'], 'new') !== FALSE)
                    {
                        //echo "new<br>";
                        // Add new group with the appropriate order id.
                        $item = new cbeads\Menu_item();
                        $item->function_id = $item_data['function_id'] != 'null' ? $item_data['function_id'] : NULL;
                        $item->menu_group_id = $grp->id;
                        $item->position = $item_counter;
                        $item->name = $item_data['name'] != 'null' ? $item_data['name'] : NULL;
                        $item->custom_url = $item_data['custom_url'] != 'null' ? $item_data['custom_url'] : NULL;
                        $item->save();
                        $update_item_ids[$item_data['item_id']] = $item->id;
                        $debug .= "Added new item: " . $item_data['item_id'] . 'which has the record id: "' . $item->id . '"<br>';
                    }
                    else    // Find the matching group record and update it.
                    {
                        //echo "old<br>";
                        $item = Doctrine_Core::getTable('cbeads\Menu_item')->find($item_data['item_id']);
                        $item->position = $item_counter;
                        $item->name = $item_data['name'] != 'null' ? $item_data['name'] : NULL;
                        $item->custom_url = $item_data['custom_url'] != 'null' ? $item_data['custom_url'] : NULL;
                        $item->save();
                    }
                    if($cur_group_id !== NULL)
                    {
                        if(isset($existing_ids[$cur_group_id]['items'][$item->id]))
                            $existing_ids[$cur_group_id]['items'][$item->id] = FALSE;   // Don't delete this item.
                    }
                }
            }
        }
        
        $return_data = array(); // Stores information to return to the client.
        $return_data['update_group_ids'] = $update_group_ids;
        $return_data['update_item_ids'] = $update_item_ids;
        

        
        // Delete groups and items that are not in the supplied data.
        //nice_vardump($existing_ids);
        $group_ids = array();
        $item_ids = array();
        foreach($existing_ids as $grp_id => $group)
        {
            if($group['delete']) $group_ids[] = $grp_id;
            foreach($group['items'] as $id => $delete)
            {
                if($delete) $item_ids[] = $id;
            }
        }
        //nice_vardump($group_ids);
        //nice_vardump($item_ids);
        if(count($item_ids) > 0)
        {
            $q = Doctrine_Query::create()->delete('cbeads\Menu_item i')->whereIn('i.id', $item_ids);
            $result = $q->execute();
            $debug .= "deleted $result items<br>";
        }
        if(count($group_ids) > 0)
        {
            $q = Doctrine_Query::create()->delete('cbeads\Menu_group g')->whereIn('g.id', $group_ids);
            $result = $q->execute();
            $debug .= "deleted $result groups<br>";
        }
        
        $return_data['debug_text'] = $debug;
        echo json_encode($return_data);
    }
    

    // Returns all applications and their functions. Contains app: id, name. func: id, name.
    function _get_apps_and_functions()
    {
        $q = Doctrine_Query::create()
            ->select('a.id, a.name, f.id, f.name')
            ->from('cbeads\Application a, a.Functions f')
            ->orderBy('a.name, f.name');
        return $q->fetchArray();
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
            ->from('cbeads\Application a, a.Functions f');
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
                        $items[] = array('function_id' => $function['id'], 'name' => NULL, 'custom_url' => NULL, 'id' => 'new' . $function['id']);
                }
                $new_menu[$i]['Items'] = $items;
            }
        }
        
        //nice_vardump($new_menu);
        return $new_menu;
    }
    
    
    // Cleans up and adds to the menu data so that the client can use the information.
    function _clean_up_db_menu_data(&$menu)
    {
        for($i = 0; $i < count($menu); $i++)
        {
            //nice_vardump($menu);
            $menu[$i]['group_id'] = $menu[$i]['id'];
            $menu[$i]['items'] = $menu[$i]['Items'];
            //nice_vardump($menu[$i]['Items']);
            unset($menu[$i]['team_id'], $menu[$i]['role_id'], $menu[$i]['id'], $menu[$i]['Items']);
            for($j = 0; $j < count($menu[$i]['items']); $j++)
            {
                $menu[$i]['items'][$j]['item_id'] = $menu[$i]['items'][$j]['id'];
                unset($menu[$i]['items'][$j]['id'],$menu[$i]['items'][$j]['menu_group_id']);
            }
        }
    }
    
}

?>