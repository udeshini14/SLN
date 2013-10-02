<?php

/** ******************************** cbeads/manage_applications.php **********************************
 *
 *  Contains code for managing applications, functions and
 *  associations between team roles and functions.
 *
 *  2010/06/16 - Markus
 *
 *  2010/07/19 - Markus
 *  - Added line to print link to cbeads_style.css
 *
 *  2010/08/12 - Markus
 *  - create_app() now uses the database values as defined in the
 *    database config file.
 *
 *  2010/09/23 - Markus
 *  - No longer showing the team-role-functions menu tab as it was 
 *    pointless.
 *  - Specified order for functions table and other small fixes.
 *  - Added a warning message saying to remove files and db when
 *    an app is deleted.
 *
 *  2010/11/23 - Markus
 *  - Moved to using Renderer class instead of renderer help
 *
 *  2010/11/30 - Markus
 *  - Renamed _function() to _function_view()
 *
 *  2011/03/02 - Markus
 *  - Set the output flag to true when rendering the UI to avoid headers already sent warnings.
 *
 *  2011/08/11 - Markus
 *  - Updated function names throughout. Cannot refer to functions in the old cbeads/routines anymore.
 *
 *  2011/08/21 - Markus
 *  - Added 'is_public' field to the Usecases form field order.
 *
 *  2011/11/03 - Markus
 *  - For application and function creation, the selected teams/team-roles are now linked to the
 *    create application/function.
 *  - Updated select_app so that it redirects to the usecase tabs automatically to save the user form 
 *    having to click the tab.
 *  
 ******************************************************************************************************/

class Manage_applications extends CI_Controller 
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
            'item_order' => array('application', 'function_'),
            'items' => array(
                'application' => array(
                    'content' => $this->_application()          // Generates array now.
                 ),
                'function_' => array(
                    'label' => 'Usecases',
                    'content' => array($this, '_function_view') // Generates content array via a callback
                 )
            ),
            'output' => TRUE
        );
        $R = new Renderer();
        $result = $R->render_as_menu($options);
        if($result['success'] == FALSE)
        {
            echo cbeads_error_message('Error when rendering menu for application management:<br>' . $result['msg']);
        }
        else
        {
            echo '<link href="'.base_url().'cbeads/css/cbeads_style.css" type="text/css" rel="stylesheet">';
            echo $result['output_html'];
        }
    }
    
    function _application()
    {
        $app = $this->session->userdata('selected_cbeads_appname');
        if(!empty($app))
            $description = "Selected application is $app";
        else
            $description = "No application selected";
        return array(
            'title' => 'Apps',
            'description' => $description,
            'column_order' => array('id', 'name', 'description', 'namespace', 'Teams', 'Functions'),
            'controllers' => array(
                'select' => array('label' => 'Select', 'callback' => array($this, 'select_app'))
            ),
            'form_options' => array(
                'default' => array(
                    'order' => array('name', 'description', 'namespace', 'version', 'Teams', 'Functions')
                ),
                'create' => array(
                    'order' => array('name', 'description', 'namespace', 'version', 'Teams'),
                    'fields' => array(
                        'name' => array('validate' => '/[A-Za-z0-9_ ]/'),
                        'namespace' => array('validate' => '/[A-Za-z0-9_ ]/'),
                    ),
                    'controllers' => array(
                        'create' => array('label' => 'Create', 'create' => FALSE, 'callback' => array($this, 'create_app')),
                        'cancel' => array('label' => 'Cancel')
                    ),
                    //'validation' => array($this, 'app_validation')
                ),
                'edit' => array(
                    'order' => array('name', 'description', 'version', 'Teams'),
                    'fields' => array(
                        'name' => array('validate' => '/[A-Za-z0-9_ ]/'),
                        'namespace' => array('validate' => '/[A-Za-z0-9_ ]/'),
                    ),
                    'controllers' => array(
                       'edit' => array('label' => 'Save', 'edit' => TRUE, 'callback' => array($this, 'update_app')),
                       'cancel' => array('label' => 'Cancel')
                    )
                ),
                'delete' => array(
                    //'order' => array('name', 'description', 'namespace', 'version', 'Teams', 'Functions'),
                    'controllers' => array(
                       'delete' => array('label' => 'Delete', 'delete' => TRUE, 'callback' => array($this, 'delete_app')),
                       'cancel' => array('label' => 'Cancel')
                    )
                )
            )
        );
    }

    function _function_view()
    {
        $app = $this->session->userdata('selected_cbeads_appname');
        $app_id = $this->session->userdata('selected_cbeads_appid');
        if($app_id == NULL) print cbeads_warning_message('Please select an application');
        //$team_role = $this->session->userdata('~team-role');
        $teams = cbeads_get_teams_for_application($app_id);
        if(empty($teams))
        {
            
        }
        $available_teams = array();
        foreach($teams as $team)
        {
            //echo "Team: " . $team['id'] . ' - ' . $team['name'] . ' - ' . $team['description'] . '<br>';
            $team_roles = cbeads_get_teamroles_for_team($team['id']);
            //nice_vardump($team_roles);
            foreach($team_roles as $team_role)
            {
                $available_teams[$team_role['id']] = $team_role['team-role'];
            }
        }

        return array(
            'title' => 'Usecases',
            'description' => "Manage usecases for application '$app'",
            'column_order' => array('id', 'name', 'description', 'controller_name', 'application_id', 'TeamRoles', 'is_public'),
            'columns' => array(
                'application_id' => array('label' => 'Application'),
				'is_public' => array('output_type' => 'yesno')
            ),
            'form_options' => array(
                'default' => array(
                    'order' => array('name', 'description', 'application_id', 'TeamRoles', 'is_public'),
					'fields' => array(
						'is_public' => array('output_type' => 'yesno', 'input_type' => 'yesno')
					)
                ),
                'create' => array(
                    'fields' => array(
                        'TeamRoles' => array('label' => 'Team-Roles', 'items' => $available_teams),
                        'application_id' => array('label' => 'Application', 'value' => $app_id, 'static' => TRUE )
                    ),
                    'controllers' => array(
                        'create' => array('label' => 'Create Function', 'create' => FALSE, 'callback' => array($this, 'create_func')),
                        'cancel' => array()
                    )
                ),
                'edit' => array(
                    'fields' => array(
                        'TeamRoles' => array('label' => 'Team-Roles', 'items' => $available_teams),
                        'application_id' => array('label' => 'Appliction', 'value' => $app_id, 'static' => TRUE)
                    ),
                    'controllers' => array(
                       'edit' => array('label' => 'Update Function', 'edit' => TRUE),
                       'cancel' => array()
                    )
                ),
                'delete' => array(
                    'fields' => array(
                        'TeamRoles' => array('label' => 'Team-Roles', 'items' => $available_teams),
                        'application_id' => array('label' => 'Appliction')
                    ),
                    'controllers' => array(
                       'delete' => array('label' => 'Delete Function', 'delete' => TRUE),
                       'cancel' => array()
                    )
                ),
                'view' => array(
                    'fields' => array(
                        'application_id' => array('label' => 'Appliction')
                    )
                )
            ),
            'filters' => array(
                array('column' => 'application_id', 'operator' => '=', 'value' => $app_id, 'type' => 'AND')
            )
        );
    }
    
    // Callback for when 'Select' is clicked in the applications table. This makes the
    // current application the selected one and redirects the user to the Usecases tab.
    // params: this associative array contains the object id that was selected
    function select_app($params)
    {
        $id = $params['object_id'];
        $obj = Doctrine::getTable('cbeads\Application')->find($id);
        if($obj === FALSE) 
        {
            $param['table_options']['description'] = "No application selected!";
            cbeads_error_message('No application with id: ' .$id);
            return;
        }
        $this->session->set_userdata('selected_cbeads_appname', $obj->name);
        $this->session->set_userdata('selected_cbeads_appid', $id);
		redirect('cbeads/manage_applications/index/_menu_active/function_');
    }

    // Callback for when an application is created.
    function create_app($obj)
    {   
        $name = $_POST['name'];
        $namespace = $_POST['namespace'];
        $namespace = str_replace(' ', '_', $namespace);
        
        $new = new cbeads\Application();
        $new->name = $name;
        $new->description = $_POST['description'];
        $new->namespace = $namespace;
        $new->version = $_POST['version'];
		$new->link('Teams', $_POST['Teams']);
        $new->save();
        print cbeads_success_message('Created new application ('.$new->name.') record.');

        if(cbeads_createApplicationFolder($namespace) === FALSE)
            print cbeads_error_message('Failed to create application folders');
        else
            print cbeads_success_message('Created folders for new application');
        
        require APPPATH.'config/database.php';
        $dns = 'mysql://'.$db[$active_group]['username'] . ':' . $db[$active_group]['password'] . '@' . 
                $db[$active_group]['hostname'] . '/' . $namespace;
        //$conn = Doctrine_Manager::connection('mysql://root:pass@127.0.0.1/'.$namespace, $namespace);
        $conn = Doctrine_Manager::connection($dns, $namespace);
        try
        {
            $conn->createDatabase();
            print cbeads_success_message('Created database: '. $namespace);
        }
        catch(Exception $e) 
        {
            print cbeads_error_message('Could not create database "' . $namespace .'". Error was:<br>' . $e->getMessage());
        }
        
    }
    
    function app_validation($data)
    {
        $errors = array();
        
        if(preg_match('/[^A-Za-z0-9_ ]/', $name))
        {
            print cbeads_error_message('You can only use alphanumeric characters, space and underscores (A-Z, a-z, 0-9, ,_) for your application name.<br>Name provided was: "'.$name.'"');
            return;
        }
        if($namespace == '')
        {
            print cbeads_error_message('You need to provide a namespace for the application!');
            return;
        }
        if(preg_match('/[^A-Za-z0-9_]/', $namespace))
        {
            print cbeads_error_message('You can only use alphanumeric characters and underscores (A-Z, a-z, 0-9,_) for the application namespace.<br>Namespace provided was: "'.$namespace.'"');
            return;
        }
        
        if(!$obj->isValid())
        {
            $errors_ = $obj->getErrorStack();
            foreach($errors_ as $fieldName => $errorCodes) 
                $errors[$fieldName] = $errorCodes;
        }
        return $errors;
    }
    
    function update_app($obj)
    {
        //print $obj->name;
    }
    
    function delete_app($obj)
    {
        echo cbeads_warning_message("Remember to remove the files and database for this application.");
    }

    // Create a function.
    function create_func()
    {
        //nice_vardump($_POST);
        
        // Sanitize the usecase name. Only want alphanumeric characters and underscores. 
        $name = $_POST['name'];
        if($name == '')
        {
            print cbeads_error_message('You need to provide a name for the usecase!');
            return;
        }
        if(preg_match('/[^A-Za-z0-9_ ]/', $name))
        {
            print cbeads_error_message('You can only use alphanumeric characters, space and underscores (A-Z, a-z, 0-9, ,_) for your function name.<br>Name provided was: "'.$name.'"');
            return;
        }
        
        // Make the filename out of the function name if the function name is valid.
        // Spaces are replaced with underscores.
        $filename = strtolower($name);
        $filename = str_replace(' ', '_', $filename);
        $filename .= '.php';
        
        // Make the name of the controller (classname) out of the function name.
        // Spaces are replaced with underscores and everything but the first
        // letter is uppercase.
        $controller_name = strtolower($name);
        $controller_name = str_replace(' ', '_', $controller_name);
        $controller_name = ucfirst(($controller_name));
        
        // Create the controller file.
        $result = cbeads_createControllerFile($_POST['application_id'], $name, $controller_name, $filename, $_POST['description']);
        if($result['success'] === FALSE)
        {
            print cbeads_error_message($result['msg']);
            print cbeads_error_message('No record was created for this usecase.');
            return;
        }
        print cbeads_success_message('Created file for usecase ('.$name.')');
        
        // Create the controller record.
		$team_role_ids = $_POST['TeamRoles'];
        $new = new cbeads\Function_();
        $new->name = $name;
        $new->description = $_POST['description'];
        $new->controller_name = $controller_name;
        $new->application_id = $_POST['application_id'];
		$new->is_public = $_POST['is_public'];
		$new->link('TeamRoles', $team_role_ids);
        $new->save();
        
        print cbeads_success_message('Created new usecase ('.$new->name.') record.');
    }

    
}