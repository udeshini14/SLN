<?php

/** ********************************* cbeads/Manage_users.php *************************************
 *
 *  Allows managing users, teams, roles, team to role associations
 *  and user to team-role associations.
 *
 ** Changelog:
 * 
 *  2010/05/01 - Markus
 *  - Created file.
 *  - Added index, create, create_submit, edit, edit_submit, delete
 *    and delete_submit functions. Can now create/edit/delete users.
 *  
 *  2010/05/04 - Markus
 *  - Made changes to the form layouts and what fields are
 *    displayed. Submitting an edit form now checks to see if a 
 *    password value was submitted. If a value was sent then it is
 *    saved.
 *
 *  2010/06/18 - Markus
 *  - Now using render_as_menu to display various tables for
 *    user management, team management, role management,
 *    team-role management and user-team-role management.
 *
 *  2010/07/19 - Markus
 *  - Added cbeads_style css to be printed out.
 *
 *  2010/09/17 - Markus
 *  - Fixed up table column labels and form field labels and 
 *    specified column and field orders.
 *  - When creating a Team, it is automatically associated with role
 *    'All'.
 *
 *  2010/09/24 - Markus
 *  - Added function _get_available_functions() which is used in 
 *    generating the edit form options for team-roles.
 *  - Added function _team_role_create_validation() which is the 
 *    validation function called for team-role creations.
 *  - Added function _edited_team to check if a team-role-function
 *    mappings need to be removed.
 *
 *  2010/11/23 - Markus
 *  - Moved to using Renderer class instead of renderer helper.
 *
 *  2011/03/02 - Markus
 *  - Set the output flag to true when rendering the UI to avoid header already sent warnings.
 *
 *  2011/08/22 - Markus
 *  - Removed the change_password field appearing in the User table view and removed the 'dummy'
 *    column.
 *  - Replaced select boxes with checkboxes where multiple selections are possible. Checkboxes are
 *    much better to use in such cases.
 *
 **************************************************************************************************/

class Manage_users extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->helper(array('url'));
        include_once(APPPATH . 'libraries/Renderer.php');
    }
        
    function index()
    {
        $options = array(
            'namespace' => 'cbeads',
            'item_order' => array('user', 'team', 'role', 'team_role'),
            'items' => array(
                'user' => array(
                    'label' => 'Users',
                    'content' => array(
                        'title' => 'Manage Users',
                        'column_order' => array('id', 'uid', 'firstname', 'lastname', 'email', 'TeamRoles'),
                        'columns' => array(
                            'uid' => array('label' => 'username')
                        ),
                        'form_options' => array(
							'default' => array(
								'fields' => array(
									'TeamRoles' => array('input_type' => 'check', 'list_as_columns' => 3)
								)
							),
                            'create' => array(
                                'order' => array('uid', 'firstname', 'lastname', 'email', 'password', 'change_password', 'TeamRoles'),
                                'fields' => array(
                                    'change_password' => array('label' => 'Change Password', 'input_type' => 'yesno'),
                                    'uid' => array('label' => 'Username')
                                )
                            ),
                            'edit' => array(
                                'order' => array('uid', 'firstname', 'lastname', 'email', 'password', 'change_password', 'TeamRoles'),
                                'fields' => array(
                                    'change_password' => array('label' => 'Change Password', 'input_type' => 'yesno', 'required' => FALSE),
                                    'password' => array('required' => FALSE),
                                    'uid' => array('label' => 'Username')
                                ),
                                'validation' => array($this, '_user_edit_validation')
                            ),
                            'view' => array(
                                'order' => array('uid', 'firstname', 'lastname', 'email', 'password', 'TeamRoles'),
                                'fields' => array(
                                    'uid' => array('label' => 'Username'),
                                    'change_password' => array('label' => 'Change Password')
                                )
                            )
                        )
                    )
                ),
                'team' => array(
                    'label' => 'Teams',
                    'content' => array(
                        'title' => 'Manage Teams',
                        'column_order' => array('id', 'name', 'description', 'Applications', 'Roles'),
                        'form_options' => array(
                            'default' => array(
                                'order' => array('name', 'description', 'Applications', 'Roles'),
								'fields' => array(
									'Applications' => array('input_type' => 'check', 'list_as_columns' => 3),
									'Roles' => array('list_as_columns' => 3)
								)
                            ),
                            'create' => array(
                                'controllers' => array(
                                    'create' => array('label' => 'Create', 'create' => TRUE, 'callback' => array($this, '_created_team')),
                                    'cancel' => array('lable' => 'Cancel')
                                )
                            ),
                            'edit' => array(
                                'controllers' => array(
                                    'edit' => array('label' => 'Save', 'edit' => TRUE, 'callback' => array($this, '_edited_team')),
                                    'cancel' => array('label' => 'Cancel')
                                )
                            )
                        )
                    )
                ),
                'role' => array(
                    'label' => 'Roles',
                    'content' => array(
                        'title' => 'Manage Roles',
                        'column_order' => array('id', 'name', 'description', 'Teams'),
                        'form_options' => array(
                            'default' => array(
                                'order' => array('name', 'description', 'Teams'),
                            )
                        )
                    )
                ),
                'team_role' => array(
                    'label' => 'TeamRoles',
                    'content' => array($this, '_team_role_options'),
                )
            ),
            'output' => TRUE
        );
        $R = new Renderer();
        $result = $R->render_as_menu($options);
        if($result['success'] == FALSE)
        {
            echo cbeads_error_message('Error when rendering menu for user management:<br>'.$result['msg']);
        }
        else
        {
            echo '<link href="'.base_url().'cbeads/css/cbeads_style.css" type="text/css" rel="stylesheet">';
            echo $result['output_html'];
        }
    }
    
    // Returns an array of options to use for the team-role table rendering
    function _team_role_options()
    {
        return array(
            'title' => 'Manage Team-Role mappings',
            'column_order' => array('id', 'team_id', 'role_id', 'Users', 'Functions'),
            'columns' => array(
                'team_id' => array('label' => 'Team'),
                'role_id' => array('label' => 'Role'),
                'Functions' => array('label' => 'Usecases')
            ),
            'form_options' => array(
                'default' => array(
                    'order' => array('team_id', 'role_id', 'Users', 'Functions'),
                    'fields' => array(
                        'team_id' => array('label' => 'Team'),
                        'role_id' => array('label' => 'Role'),
						'Users' => array('input_type' => 'check', 'list_as_columns' => 3),
                        'Functions' => array('label' => 'Usecases', 'list_as_columns' => 2)
                    )
                ),
                'create' => array(
                    'description' => 'Create a Team-Role association. Once a Team-Role is created, you can assign functions to it.',
                    'order' => array('team_id', 'role_id', 'Users'),
                    'validation' => array($this, '_team_role_create_validation')
                ),
                'edit' => array(
                    'fields' => array(
                        'team_id' => array('static' => TRUE, 'label' => 'Team'),
                        'role_id' => array('static' => TRUE, 'label' => 'Role'),
                        'Functions' => array('label' => 'Usecases', 'items' => $this->_get_available_functions(), 'input_type' => 'check')
                    ),
                )
            )
        );
    }
    
    // Function called when a user is edited. It ensures the values to assign will validate.
    function _user_edit_validation($data)
    {
        //nice_vardump($data);
        $errors = array();
        $obj = Doctrine::getTable('cbeads\User')->find($data['_id']);
        if($obj === FALSE) return array('_ERROR_' => "Could not find user to update!<br>");

        $obj->uid = $data['uid'];
        $obj->firstname = $data['firstname'];
        $obj->lastname = $data['lastname'];
        if(!empty($data['password'])) $obj->password = $data['password'];
        $obj->email = $data['email'];
        $obj->change_password = $data['change_password'];
        if(!$obj->isValid())
        {
            $errors_ = $obj->getErrorStack();
            foreach($errors_ as $fieldName => $errorCodes) 
                $errors[$fieldName] = $errorCodes;
        }
        return $errors;
    }
    
    // Function called when a team is created. Need to perform some tasks on team creation.
    function _created_team($obj)
    {
        // New teams are associated with a role called 'All'
        $role = Doctrine::getTable('cbeads\Role')->findOneByName('All');
        if($role)
        {
            $obj->Roles[] = $role;
            $obj->save();
            echo cbeads_success_message("Associated team '" . $obj->name . "' with role 'All'");
        }
        else
        {
            echo cbeads_warning_message("Error with cbeads role setup. Did not find expected role 'All'. Ensure this role exists and associate it with this team");
        }
    }
    
    // Function called when a team is edited. As applications can be unlinked from teams, need
    // make sure that the function to team-role mappings are valid.
    function _edited_team($obj)
    {
        // Get application IDs mapped to this team.
        $app_ids = array();
        foreach($obj->Applications as $app)
            $app_ids[] = $app->id;
        //nice_vardump($app_ids);

        // Get function IDs for apps
        $q = Doctrine_Query::create()
            ->select('f.id')
            ->from('cbeads\Function_ f')
            ->whereIn('f.application_id', $app_ids);
        $fs = $q->execute(array(), DOCTRINE_CORE::HYDRATE_ARRAY);
        $func_ids = array();
        foreach($fs as $f)
            $func_ids[] = $f['id'];
        
        // Get all ids of team-roles which include this team
        $q = Doctrine_Query::create()->select('tr.id')->from('cbeads\Team_role tr')->where('tr.team_id = ?', $obj->id);
        $trs = $q->execute(array(), DOCTRINE_CORE::HYDRATE_ARRAY);
        $tr_ids = array();
        foreach($trs as $tr)
            $tr_ids[] = $tr['id'];
        //nice_vardump($tr_ids);
        
        // Finally get all ids team-role-function mappings where the team-role is in the team-roles
        // ids list and where the function id is not in the list of function ids.
        $q = Doctrine_Query::create()
            ->select('trf.id')
            ->from('cbeads\Team_role_function trf')
            ->whereIn('trf.team_role_id', $tr_ids)
            ->AndWhereNotIn('trf.function_id', $func_ids);
        $trfs = $q->execute();
        //nice_vardump($trfs->toArray(true));

        // Delete all these. (Couldn't use DQL to generate a delete query because it doesn't allow using WhereIn to select specific records)
        $trfs->delete();
    }
    
    // Function called when a team-role is being created. Need to ensure there is no existing
    // team-role with the specified team and role.
    function _team_role_create_validation($data)
    {
        $errors = array();
        $team_id = $data['team_id'];
        $role_id = $data['role_id'];
        
        // Find any existing team-role with those ids. If one is found a error is returned.
        $tr = Doctrine::getTable('cbeads\Team_role')->findOneByTeam_idAndRole_id($team_id, $role_id);
        if($tr)
        {
            $errors['_ERROR_'] = "Team-Role for team: '" . $tr->Team->name . " and role: '" . $tr->Role->name . "' already exists!";
        }
        
        return $errors;
    }
    
    // Called to generate the list of functions (usecases) that can be associated with a team-role.
    // This depends on what the team is.
    function _get_available_functions()
    {
        // See if there was an edit reqiest and get the team-role requested.
        $editing = FALSE;
        $id = 0;
        $CI =& get_instance();
        $uri_segments = $CI->uri->segment_array();  // The url segments of the current page.
        for($i = 4; $i < count($uri_segments) + 1; $i++)  // indices start from 1 in the array returned by segment_array. Ignore app/ctrl/func/
        {
            if($uri_segments[$i] == '_table_edit')
            {
                $id = $uri_segments[$i + 1];
                $editing = TRUE;
                break;
            }
        }
        if($editing == FALSE) return;
        $tr = Doctrine::getTable('cbeads\Team_role')->find($id);
        $team_id = $tr->team_id;
        // Get the applications and their functions associated with this team.
        $functions = array();
        $maps = Doctrine::getTable('cbeads\Team_application')->findByTeam_id($team_id);
        foreach($maps as $map)
        {
            $q = Doctrine_Query::create()
                ->select('f.id, f.name, f.description, a.name')
                ->from('cbeads\Function_ f')
                ->leftJoin('f.Application a')
                ->where('f.application_id = ?', $map->application_id);
            $fs = $q->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
            foreach($fs as $f)
            {
                $functions[$f['id']] = $f['name'] . ' (' . $f['Application']['name'] . ')';
            }
        }
        
        return $functions;
    }
    
}


?>