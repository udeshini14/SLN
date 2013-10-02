<?php

/**
 * 	@file
 *	@brief Common routines for CBEADS.
 *
 *  This module defines common routines used by CBEADS. It originally existed as file
 *  \a controllers/cbeads/routines.php but since it isn't a controller it shouldn't
 *  live there.
 *
 ** Changelog:
 * 
 *  2010/06/06 - Markus
 *  - Created file.
 *  - Added get_loaded_model_names_for_db().
 *  - Added does_namespace_exist().
 * 
 *  2010/06/08 - Markus
 *  - does_namespace_exist now does a case insensitive compare.
 *
 *  2010/06/09 - Markus
 *  - Added function createApplicationFolder() used for generating
 *    folders for an application to store the application files in.
 *  - Added function createFunction() for creating controller 
 *    files.
 *  
 *  2010/06/18 - Markus
 *  - Copied _make_title_text function from renderer_helper as this
 *    functionality might be needed in multiple files.
 *
 *  2010/06/21 - Markus
 *  - Added getRelations(). Work in progress so do not use yet.
 *
 *  2010/06/23 - Markus
 *  - Added functions:
 *    - get_teams_for_application
 *    - get_teamroles_for_team
 *
 *  2010/06/24 - Markus
 *  - Updated createFunction by renaming it to createControllerFile
 *    and to accept a new parameter (controller-_name) and added a
 *    function description.
 *
 *  2010/07/01 - Markus
 *  - Added get_current_team function.
 *
 *  2010/08/18 - Markus
 *  - Added function array_sort which can sort array elements that
 *    are associative arrays by specifying the key to sort by.
 *    Got this function from: http://au.php.net/manual/en/function.sort.php#99419
 *
 *  2010/09/20 - Markus
 *  - Added functions get_current_app_ctrl_func and get_teamrole_ids_for_user.
 *
 *  2010/09/26 - Markus
 *  - Added remove_namespace_component() to remove the namespace from a class name.
 *
 *  2010/11/11 - Markus
 *  - MAJOR CHANGE: Made copy of cbeads/routines.php.
 *  - Added cbeads prefix to all function names to make it clear these are CBEADS
 *    related functions. Should prevent name conflicts.
 *
 *  2011/03/21 - Markus
 *  - Added functions cbeads_get_teams, cbeads_get_teamroles and 
 *    cbeads_get_menu_order.
 *
 *  2011/03/23 - Markus
 *  - Added functions cbeads_get_current_role and cbeads_get_current_team_and_role
 *
 *  2011/04/08 - Markus
 *  - Fixed bug in cbeads_get_current_role. Mistake in creating the DQL.
 *  - Added functions cbeads_get_username() and cbeads_get_user_id().
 *
 *  2011/04/14 - Markus
 *  - Added function cbeads_write_to_access_log() for write to the access log file.
 *
 *  2011/04/18 - Markus
 *  - cbeads_write_to_access_log() now gets the path to use from the config file.
 *
 *  2011/04/21 - Markus
 *  - cbeads_write_to_access_log() now tests if logging is disabled or not.
 *
 *  2011/05/20 - Markus
 *  - Fixed inncorrect DQL statements in cbeads_get_teamrole_ids_for_user(). It now works as intended.
 *
 *  2011/06/10 - Markus
 *  - Added functions cbeads_session_clear_data, cbeads_session_get_data, cbeads_session_has_data and
 *    cbeads_session_set_data to store session data in a cbeads namespace/controller heirarchical
 *    structure. This way different applications can save session data in the knowledge that other 
 *    applications are not likely to be interfereing. It is possible to set the session data in 
 *    another namespace/controller just in case that has to be done.
 *
 *  2011/07/08 - Markus
 *  - Removed function cbeads_get_relations() since it isn't being used anywhere, doesn't appear to have been
 *    finished and doesn't seems useful.
 *
 *  2011/09/19 - Markus
 *  - success, error and warning message div elements now have class names assigned instead of ids, since
 *    elements should have unique IDs and it is more consistent to do styling via classes.
 *
 *  2011/10/06 - Markus
 *  - Added function cbeads_write_to_email_log for writing data to the email log file.
 *  - Added function cbeads_send_email for sending emails. It also logs the emails if logging is enabled.
 *  - Fixed cbeads_write_to_access_log not returning TRUE when it is successful in writing to the file.
 *
 *  2012/01/25 - Markus
 *  - Updated function comments so that they can be processed doxygen.
 *
 ****************************************************************************************************************/


/** 
 *	Gets the table names associated with all loaded models for a given database/namespace.
 * 	@param string $db
 * 		The name of the database for which to get the table names.
 * 	@returns An array of table names. Will be empty if there are no defined models or the
 * 		db cannot be matched to any model.
 */
function cbeads_get_loaded_model_names_for_db($db)
{
    // Get all model classnames. Split classes into namespace and
    // classname components and record
    $models = Doctrine_Core::getLoadedModels();
    $names = array();
    foreach($models as $model)
    {
        $parts = preg_split('/\\\\/', $model);
        if(count($parts) == 2)
        {
            if($parts[0] == $db) $names[] = strtolower($parts[1]);
        }
    }
    
    return $names;
}

/**
 *	Checks if the passed namespace exists in the models registered
 * 	with Doctrine.
 * 	@param string $db
 *		The namespace to look for.
 * 	@returns True if there are models that exist for that namespace,
 * 	otherwise false.
 */
function cbeads_does_namespace_exist($db)
{
    $models = Doctrine_Core::getLoadedModels();
    foreach($models as $model)
    {
        $parts = preg_split('/\\\\/', $model);
        if(count($parts) == 2)
        {
            if(strtolower($parts[0]) == strtolower($db)) return TRUE;
        }
    }
    return FALSE;
}

/**
 *  Creates application namespace (ie folders) in the application/controller folder,
 *  application/model folder and application/views folder.
 *  @param string $ns
 *		The namespace, aka appcode. For example an application called 'Smart Business Objects' could
 * 		be given the namespace 'sbo'. The value \b MUST be unique and must only contains alphanumeric
 *		characters and underscores!
 *  @returns Returns boolean TRUE if the folders were created. Returns boolean FALSE if they were not.
 */
function cbeads_createApplicationFolder($ns)
{
    if(empty($ns)) return FALSE ;     // Nothing to create.
    $ns = strtolower($ns);
    $success = mkdir(APPPATH."controllers/$ns", 0777);
    if(!$success) return FALSE;
    $success = chmod(APPPATH."controllers/$ns", 0777);  # Do this in case mkdir is too stupid to set the mode as specified.
    if(!$success) return FALSE;
    $success = mkdir(APPPATH."models/$ns", 0777);
    if(!$success) return FALSE;
    $success = chmod(APPPATH."models/$ns", 0777);
    if(!$success) return FALSE;
    $success = mkdir(APPPATH."views/$ns", 0777);
    if(!$success) return FALSE;
    $success = chmod(APPPATH."views/$ns", 0777);
    if(!$success) return FALSE;
}


/**
 *  Creates a function using a template file and replacing parts of it with
 * 	the passed parameters.
 *	@param int $appid
 *		The id of the application this function belongs to.
 *	@param string $funcname
 * 		The name of the function
 * 	@param string $controller_name
 *		The name of the controller (ie the classname) should be the same as the name given to
 *      the function without spaces.
 * 	@param string $filename
 *		The filename to save the controller to.
 * 	@param string $description
 * 		The description to put in the controller.
 *  @returns An associative array containing the field 'success'. If success is set to boolean TRUE 
 *		the operation succeeded. When set to boolean FALSE, the field 'msg' contains an error message.
 */
function cbeads_createControllerFile($appid, $funcname, $controller_name, $filename, $description)
{
    $app = Doctrine::getTable('cbeads\Application')->find($appid);
    if(!$app) return array('success' => FALSE, 'msg' => 'Could not find the application to create the function for. Used id: ('.$appid.')');
  
    if($funcname == '' || $filename == '') 
        return array('success' => FALSE, 'msg' => "The function name ($funcname) and filename ($filename) cannot be empty");
  
    // Cannot have another function with the same name or filename within this application.
    foreach($app->Functions as $func)
    {
        if($func->name == $funcname && $func->controller_name == $controller_name)
            return array('success' => FALSE, 'msg' => "Another function with this name ($funcname) AND filename ($filename) already exists!");
        if($func->name == $funcname)
            return array('success' => FALSE, 'msg' => "Another function with this name ($funcname) already exists!");
        if($func->controller_name == $controller_name)
            return array('success' => FALSE, 'msg' => "Another function with this filename ($filename) already exists!");
    }
    
    $namespace = $app->namespace;
    // Get the contents of template controller, replace values and output to the new function file.
    $filepath = APPPATH . 'controllers/cbeads/template.php';
    $handle = fopen($filepath, 'r');
    if($handle === FALSE) return array('success' => FALSE, 'msg' => 'Could not read controller template file from location ('.$filepath.')!');
    $content = fread($handle, filesize($filepath));
    if($content === FALSE) return array('success' => FALSE, 'msg' => 'Could not read controller template file contents!');
    fclose($handle);
    
    $content = str_replace(array('[[namespace]]', '[[filename]]', '[[controller_name]]', '[[description]]'), 
                           array($namespace, $filename, $controller_name, $description), $content);

    $filepath = APPPATH . 'controllers/'.$namespace.'/'.$filename;
    $handle = fopen($filepath, 'x');
    if($handle === FALSE) return array('success' => FALSE, 'msg' => 'Could not create controller file at location ('.$filepath.')!');
    if(fwrite($handle, $content) === FALSE)
        return array('success' => FALSE, 'msg' => 'Could not write to file as location ('.$filepath.')!');
    fclose($handle);
    
    if(chmod($filepath, 0777) === FALSE)
        return array('success' => FALSE, 'msg' => 'Could not change mode of file ('.$filepath.')!');
    
    return array('success' => TRUE);
    
}

/**
 * 	Just debug prints a variable but places it within a \<pre>\</pre> block so it
 * 	is nicely formatted when displayed on the browser.
 *  @param variable $var
 *  	A variable to debug print.
 */
function cbeads_nice_vardump($var)
{
    print '<pre>'; print_r($var); print '</pre>';
    
}

/**
 * 	Converts a string into a string where words are capitalised and underscores are replaced with
 * 	spaces.
 * 	@param string $val
 *		A string to modify.
 * 	@returns The modified string.
 */
function cbeads_make_title_text($val)
{
    $val = str_replace('_', ' ', $val);
    $val = ucwords($val);
    return $val;
}

/**
 *	Gets all teams that exist.
 * 	@returns An array of teams. The teams are arrays containing the fields 'id', 'name' and 'description'.
 */
function cbeads_get_teams()
{
    $q = Doctrine_Query::create()->select('t.id, t.name, t.description')->from('cbeads\Team t')->orderBy('t.name');
    $teams = $q->fetchArray();
    return $teams;
}

/**
 *	Gets all team-roles that exist.
 *  @returns An array of team-roles. Each team-role is an array containing the fields 'id' and 'name'.
 */
function cbeads_get_teamroles()
{
    $q = Doctrine_Query::create()->from('cbeads\Team_Role tr, tr.Team t')->orderBy('t.name');
    $results = $q->execute(array(), Doctrine_Core::HYDRATE_ON_DEMAND);
    $team_roles = array();
    foreach($results as $result)
    {
        $team_roles[] = array('id' => $result->id, 'name' => $result->stringify_self());
    }
    return $team_roles;
}


/**
 *	Gets all teams that have been associated with a given application.
 * 	@param int $app_id
 *		The id of the application for which to get the associated teams.
 *	@returns An array of teams (id, name, description) that have been associated
 * 		with this application.
 */
function cbeads_get_teams_for_application($app_id)
{
    $q = Doctrine_Query::create()
            ->select('t.id, t.name, t.description')
            ->from('cbeads\Team t')
            ->leftJoin('t.Applications a')
            ->where('a.id = ?', $app_id);
    $teams = $q->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
    return $teams;
}


/**
 *	Gets the team-roles (id and name) that exist for a team.
 *	@param int $team_id
 *		The team id.
 *	@returns An array of team-roles. The team-roles are associative arrays containing fields 'id'
 *		and 'team-role'.
 */
function cbeads_get_teamroles_for_team($team_id)
{
    $q = Doctrine_Query::create()
            ->select('tr.id')
            ->from('cbeads\Team_Role tr')
            ->where('tr.team_id = ?', $team_id);
    $results = $q->execute(array(), Doctrine_Core::HYDRATE_ON_DEMAND);
    $team_roles = array();
    foreach($results as $result)
    {
        $team_roles[] = array('id' => $result->id, 'team-role' => $result->stringify_self());
    }
    return $team_roles;
}

/**
 *	Gets the team the user currently is in by checking their team-role.
 *	@returns An associative array with the fields 'name' and 'id' containing the values for 
 *  	the current team. Returns boolean false on failure.
 */
function cbeads_get_current_team()
{
    $CI =& get_instance();
    $cur_teamrole = $CI->session->userdata('~team-role');
    $q = Doctrine_Query::create()
            ->select('t.id, t.name')
            ->from('cbeads\Team t')
            ->where('t.id IN (SELECT tr.team_id FROM cbeads\Team_Role tr WHERE tr.id = ?)', $cur_teamrole);
    $result = $q->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
    if(count($result) == 0)
        return FALSE;
    else
        return $result[0];
}

/**
 *	Gets the role the user currently is in by checking their team-role.
 *	@returns An associative array with the fields 'name' and 'id' containing the values for 
 *  	the current role. Returns boolean false on failure.
 */
function cbeads_get_current_role()
{
    $CI =& get_instance();
    $cur_teamrole = $CI->session->userdata('~team-role');
    $q = Doctrine_Query::create()
            ->select('r.id, r.name')
            ->from('cbeads\Role r')
            ->where('r.id IN (SELECT tr.role_id FROM cbeads\Team_Role tr WHERE tr.id = ?)', $cur_teamrole);
    $result = $q->fetchArray();
    if(count($result) == 0)
        return FALSE;
    else
        return $result[0];
}

/**
 *	Gets the username of the current user. Username is what one logs in with, not the actual user's name.
 *	@returns The username of the current user.
 */
function cbeads_get_username()
{
    $CI =& get_instance();
    return $CI->session->userdata('~uname');
}

/** 
 *	Gets the id of the current user.
 *	@returns The current user's ID.
 */
function cbeads_get_user_id()
{
    $CI =& get_instance();
    return $CI->session->userdata('~id');
}

/**
 * Gets the team and role the user currently is in by checking their team-role.
 * @returns An associative array with the fields 'team_name', 'team_id', 'role_name' and 'role_id'
 * 		containing the values of the current team and role. Returns boolean false on failure.
 */
function cbeads_get_current_team_and_role()
{
    $CI =& get_instance();
    $cur_teamrole = $CI->session->userdata('~team-role');
    $q = Doctrine_Query::create()
            ->select('tr.id, r.id, r.name, t.id, t.name')
            ->from('cbeads\Team_Role tr, tr.Team t, tr.Role r')
            ->where('tr.id = ' . $cur_teamrole);
    $result = $q->fetchArray();
    if(count($result) == 0)
        return FALSE;
    else
    {
        return array(
            'team_id' => $result[0]['Team']['id'],
            'team_name' => $result[0]['Team']['name'],
            'role_id' => $result[0]['Role']['id'],
            'role_name' => $result[0]['Role']['name']
        );
    }
}


/**
 *  Takes an array and sorts the values. If the array elements are arrays themeselves, the \c $on key is
 *  used to find the value to use. Got code from: http://au.php.net/manual/en/function.sort.php#99419.
 *	@param array $array
 *  	An array to sort.
 *  @param string $on
 * 		A keyname to sort on, assuming the array contains elements that are arrays.
 *  @param enum $order
 *  	The order to sort the values in. Can be either SORT_ASC or SORT_DESC
 *  @returns An array of sorted values.
 */
function cbeads_array_sort($array, $on, $order=SORT_ASC)
{
    $new_array = array();
    $sortable_array = array();

    if (count($array) > 0) {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $k2 => $v2) {
                    if ($k2 == $on) {
                        $sortable_array[$k] = $v2;
                    }
                }
            } else {
                $sortable_array[$k] = $v;
            }
        }

        switch ($order) {
            case SORT_ASC:
                asort($sortable_array);
            break;
            case SORT_DESC:
                arsort($sortable_array);
            break;
        }

        foreach ($sortable_array as $k => $v) {
            $new_array[$k] = $array[$k];
        }
    }

    return $new_array;
}

/**
 * 	Gets the team role ids associated with a given user.
 * 	@param int $user_id
 * 		The id of the user
 * 	@param int $team_id
 * 		The id of a team, if one wants to limit team-roles to specific teams
 * 	@param int $role_id
 *		The id of a role, if one wants to limit team-roles to specific roles
 *		(Only makes sense to optionally provide a team or a role, but not both)
 * 	@returns An array of team-role ids. May be an empty array if there were no matches.
 */
function cbeads_get_teamrole_ids_for_user($user_id, $team_id = NULL, $role_id = NULL)
{
    $CI =& get_instance();
	$tr_ids = array();
    if($team_id !== NULL)
    {
		$q = Doctrine_Query::create()
			->select('tr.id')
			->from('cbeads\Team_Role tr')
			->where('tr.team_id = ?', $team_id);
		$results = $q->fetchArray();
		foreach($results as $res)
			$tr_ids[] = $res['id'];
        $q = Doctrine_Query::create()
            ->select('utr.team_role_id')
            ->from('cbeads\User_team_role utr')
            ->whereIn('utr.team_role_id', $tr_ids)
			->andWhere('utr.user_id = ?', $CI->session->userdata('~id'));
    }
    elseif($role_id !== NULL)
    {
		$q = Doctrine_Query::create()
			->select('tr.id')
			->from('cbeads\Team_Role tr')
			->where('tr.role_id = ?', $role_id);
		$results = $q->fetchArray();
		foreach($results as $res)
			$tr_ids[] = $res['id'];
        $q = Doctrine_Query::create()
            ->select('utr.team_role_id')
            ->from('cbeads\User_team_role utr')
			->whereIn('utr.team_role_id', $tr_ids)
			->andWhere('utr.user_id = ?', $CI->session->userdata('~id'));
    }
    else
    {
        $q = Doctrine_Query::create()
            ->select('utr.team_role_id')
            ->from('cbeads\User_team_role utr')
            ->where('utr.user_id = ?', $CI->session->userdata('~id'));
    }
    $utrs = $q->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
    $teamroles = array();
    foreach($utrs as $utr)
        $teamroles[] = $utr['team_role_id'];
    
    return $teamroles;
}

/**
 *	Gets the name of the current application, controller and function 
 *	as was specified in the current url. It also returns the ids of
 *  the application and controller (which is a usecase!).
 *  @returns An array containing the app, controller and function names.
 */
function cbeads_get_current_app_ctrl_func()
{
    // segments array contains the url accessed in the form base_url/app/ctrl/func/[parameters/]
    $CI =& get_instance();
    $uri_segments = $CI->uri->segment_array();
    $result['application'] = $uri_segments[1];
    $result['controller'] = $uri_segments[2];
    if(isset($uri_segments[3])) $result['function'] = $uri_segments[3];
    
    $app = Doctrine::getTable('cbeads\Application')->findOneByNamespace($uri_segments[1]);
    if(!$app) 
        $result['application_id'] = NULL;
    else
        $result['application_id'] = $app->id;
    
    // Controller must match to application, as multiple apps can have controllers of the same name.
    $ctrl = Doctrine::getTable('cbeads\Function_')->findOneByController_nameAndApplication_id($uri_segments[2], $result['application_id']);
    if(!$ctrl) 
        $result['controller_id'] = NULL;
    else      
        $result['controller_id'] = $ctrl->id;
    
    return $result;
}

/**
 * 	Gets the menu order for a given team and role.
 * 	@param int $team
 *		The team id for which to retrieve the menu order. If NULL then the global menu order is retrieved.
 * 	@param int $role
 * 		The role id for which to retrieve the menu order (requires a team id to be provided). Set to NULL
 *      when requesting global menu order.
 * 	@returns The menu groups and related menu items. Will return an empty array if no matches where found.
 */
function cbeads_get_menu_order($team, $role)
{    
    $q = Doctrine_Query::create()->from('cbeads\Menu_group m, m.Items i')->orderBy('m.position, i.position');
    if($team != NULL && $role != NULL)      // team-role
    {
        $q->where('m.team_id = ' . $team . ' AND m.role_id = ' . $role);
    }
    else if($team != NULL)                  // team
    {
        $q->where('m.team_id = ' . $team . ' AND m.role_id IS NULL');
    }
    else                                    // global
    {
        $q->where('m.team_id IS NULL AND m.role_id IS NULL');
    }
    return $q->fetchArray();
}



/**
 * Gets the class name part of a class name with a namespace component.
 * @param string $name
 *		the string containing a class name with a namespace component.
 *      (ie 'cbeads\\User').
 * @returns The class component. Eg: 'cbeads\\User' returns 'User'.
 */
function cbeads_remove_namespace_component($name)
{
    preg_match('/(\w*)\\\\(\w*)/', $name, $matches);    // '\' is the namespace-class seperator.
    if(isset($matches[2]))
        return $matches[2];
    return $name;
}

/**
 *  Puts a message into a html block meant for errors.
 * 	@param string $msg
 *		A error message.
 *  @returns The error message encapsulated in a html 'error' block.
 */
function cbeads_error_message($msg)
{
    return '<div class="error_message">'.$msg.'</div>';
}

/**
 *  Puts a message into a html block meant for successes.
 * 	@param string $msg
 *		A success message.
 *  @returns The success message encapsulated in a html 'success' block.
 */
function cbeads_success_message($msg)
{
    return '<div class="success_message">'.$msg.'</div>';
}

/**
 *  Puts a message into a html block meant for warnings.
 * 	@param string $msg
 *		A warning message.
 *  @returns The warning message encapsulated in a html 'warning' block.
 */
function cbeads_warning_message($msg)
{
    return '<div class="warning_message">'.$msg.'</div>';
}


/**
 * Writes data to the access log file if logging was not disabled.
 * @param string|array|object $data
 *		Data value to be json encoded before writing it to file.
 * @returns TRUE on success and FALSE on failure.
 */
function cbeads_write_to_access_log($data)
{
    $data = json_encode($data) . "\n";
    $date = date('Y-m');
    $CI =& get_instance();
    $dirpath = $CI->config->item('access_log_path');
    // See if logging was disabled.
    if($dirpath === FALSE) return TRUE;
    // If no custom path, use default.
    if(empty($dirpath)) $dirpath = APPPATH . 'logs';
    if($handle = fopen($dirpath . '/access-log_'.$date .'.log', 'a+'))
    {
        if (fwrite($handle, $data) === FALSE) {
            return FALSE;   // Couldn't write file.
        }
        fclose($handle);
        return TRUE;
    }
    
    return FALSE;   // Unable to open/create file.
}

/**
 * 	Gets the current namespace (based on the current URI)
 *  @returns The namespace.
 */
function cbeads_get_current_namespace()
{
	$CI =& get_instance();
	return $CI->uri->segment(1);
}

/**
 *	Gets the current controller (based on the current URI).
 *	@returns The name of the controller. 
 */
function cbeads_get_current_controller()
{
	$CI =& get_instance();
	return $CI->uri->segment(2);
}



/**
 * 	Sets a key/value pair to be stored in the user's session under the current 
 * 	controller's namespace or another namespace/controller if so set.
 * 	@param string $key
 * 		The key name.
 * 	@param $value 
 *		The data to store (can be anything as long as it can be serialised.
 * 	@param bool|array $opt
 * 		If set to TRUE, will store the key/value under the current namespace and current 
 *      controller name. If an array is passed, the array can sepcify the namespace and/or
 *      controller to put the key/value under. Eg: array('namespace' => 'cbeads',
 *      'controller' => 'manage_users')
 */
function cbeads_session_set_data($key, $value, $opt = FALSE)
{
	$namespace = cbeads_get_current_namespace();
	$controller = '~';		// Signifies global storage in namespace.
	if($opt === TRUE)
		$controller = cbeads_get_current_controller();
	elseif(is_array($opt))
	{
		if(isset($opt['namespace'])) $namespace = $opt['namespace'];
		if(isset($opt['controller'])) $controller = $opt['controller'];
	}

	$CI =& get_instance();
	$data = $CI->session->userdata('cbeads_session_data');
	$data = json_decode($data, TRUE);
	if(!isset($data[$namespace])) $data[$namespace] = array();
	if(!isset($data[$namespace][$controller])) $data[$namespace][$controller] = array();
	$data[$namespace][$controller][$key] = $value;

	$data = json_encode($data);
	$CI->session->set_userdata('cbeads_session_data', $data);
}

/** 
 * 	Gets the value stored in the session under the current controller's namespace
 * 	or another namespace/controller if so set.
 * 	@param string $key
 *  	The key to look for in the session.
 * 	@param bool|array $opt
 * 		If set to TRUE, will look for the key under the current namespace and current
 *      controller name. If an array is passed, the array can specify the namespace and/or
 *      controller to put the key/value under. Eg: array('namespace' => 'cbeads',
 *      'controller' => 'manage_user')
 * 	@returns the value of the key if found. If not found, returns FALSE.
 */
function cbeads_session_get_data($key, $opt = FALSE)
{
	$namespace = cbeads_get_current_namespace();
	$controller = '~';		// Signifies global storage in namespace.
	if($opt === TRUE)
		$controller = cbeads_get_current_controller();
	elseif(is_array($opt))
	{
		if(isset($opt['namespace'])) $namespace = $opt['namespace'];
		if(isset($opt['controller'])) $controller = $opt['controller'];
	}

	$CI =& get_instance();
	$data = $CI->session->userdata('cbeads_session_data');
	$data = json_decode($data, TRUE);
	$val = FALSE;
	if(isset($data[$namespace][$controller][$key]))
		$val = $data[$namespace][$controller][$key];
	return $val;
}

/** 
 * 	Clear the key/value pair stored in the session under the current controller's namespace
 * 	or another namespace/controller if so set.
 * 	@param string $key
 *  	The key to clear from the session.
 * 	@param bool|array $opt
 *		If set to TRUE, will look for the key under the current namespace and current
 *      controller name. If an array is passed, the array can specify the namespace and/or
 *      controller to put the key/value under. Eg: array('namespace' => 'cbeads',
 *      'controller' => 'manage_user')
 * 	@returns TRUE if the key was found, otherwise FALSE.
 */
function cbeads_session_clear_data($key, $opt = FALSE)
{
	$namespace = cbeads_get_current_namespace();
	$controller = '~';		// Signifies global storage in namespace.
	if($opt === TRUE)
		$controller = cbeads_get_current_controller();
	elseif(is_array($opt))
	{
		if(isset($opt['namespace'])) $namespace = $opt['namespace'];
		if(isset($opt['controller'])) $controller = $opt['controller'];
	}

	$CI =& get_instance();
	$data = $CI->session->userdata('cbeads_session_data');
	$data = json_decode($data, TRUE);
	$found = FALSE;
	if(isset($data[$namespace][$controller][$key]))
	{
		$found = TRUE;
		unset($data[$namespace][$controller][$key]);
	}

	$data = json_encode($data);
	$CI->session->set_userdata('cbeads_session_data', $data);
	return $found;
}

/** 
 *	Checks if there is a key under the current controller's namespace, or another namespace/
 * 	controller if so set. Since cbeads_session_get_data() can be ambiguous if it has or has not
 * 	found the requested key in the session, this function should be used to first test that
 *	the value exists.
 *	@param string $key
 *		The key to look for in the session.
 * 	@param bool|array $opt
 *		If set to TRUE, will look for the key under the current namespace and current
 *      controller name. If an array is passed, the array can specify the namespace and/or
 *      controller to put the key/value under. Eg: array('namespace' => 'cbeads',
 *      'controller' => 'manage_user')
 * 	@returns TRUE if the key is found. If not found, returns FALSE.
 */
function cbeads_session_has_data($key, $opt = FALSE)
{
	$namespace = cbeads_get_current_namespace();
	$controller = '~';		// Signifies global storage in namespace.
	if($opt === TRUE)
		$controller = cbeads_get_current_controller();
	elseif(is_array($opt))
	{
		if(isset($opt['namespace'])) $namespace = $opt['namespace'];
		if(isset($opt['controller'])) $controller = $opt['controller'];
	}

	$CI =& get_instance();
	$data = $CI->session->userdata('cbeads_session_data');
	$data = json_decode($data, TRUE);
	$found = FALSE;
	if(isset($data[$namespace][$controller][$key]))
		$found = TRUE;
	return $found;
}


/** 
 *  Writes data to the email log file if logging was not disabled.
 * 	@param string|array|object $data
 *			A value that will be json encoded before writing it to file.
 * 	@returns TRUE on success and FALSE on failure.
 */
function cbeads_write_to_email_log($data)
{
    $data = json_encode($data) . "\n";
    $date = date('Y-m');
    $CI =& get_instance();
    $dirpath = $CI->config->item('email_log_path');
    // See if logging was disabled.
    if($dirpath === FALSE) return FALSE;
    // If no custom path, use default.
    if(empty($dirpath)) $dirpath = APPPATH . 'logs';
    if($handle = fopen($dirpath . '/email-log_'.$date .'.log', 'a+'))
    {
        if (fwrite($handle, $data) === FALSE) {
            return FALSE;   // Couldn't write file.
        }
        fclose($handle);
        return TRUE;
    }
    
    return FALSE;   // Unable to open/create file.
}

/**
 *	Sends an email. Read the email documentation available at
 *	http://codeigniter.com/user_guide/libraries/email.html for a full description of all the 
 *	options. If logging is enabled, logs the email (with debug info when sending failed).
 *  @param array $args
 * 		An associative array that can contain the following items:\n
 *  	\b Required:
 *  	- to - The email address(es) to send to. Can be either a string where mutliple addresses are
 * 			comma separated or an array.
 *  	- subject - A subject for the email.
 *  	- message - The body of the email.
 *  	.
 *  	\b Optional:
 *  	- config - An array of configuration options. See 'Email Preferences' in the CodeIgniter docs.
 * 	 	- from - The email address of the sender. If not provided, will use CBEADS admin user's email
 *		address.
 *  	- reply_to - An email address to reply to. If not provided this will be the same as 'from'.
 *  	- cc - The CC email address(es) to send to. Can be specified just like with 'to'.
 * 	 	- bcc - The BCC email address(es). Can be specified just like with 'to'.
 *  	- alt_message - An alternate message when sending a message with html. This is used when the
 *   	receiver does not accept html emails.
 *  	- attachments - An array of file names to attach to the email.
 *  	.
 *
 * 	@returns boolean TRUE on success. On failure returns a string with an error message.
 */
function cbeads_send_email($args)
{
	$to = isset($args['to']) ? $args['to'] : FALSE;
	$subject = isset($args['subject']) ? $args['subject'] : FALSE;
	$message = isset($args['message']) ? $args['message'] : FALSE;
	$config = isset($args['config']) && is_array($args['config']) ? $args['config'] : array();
	$from = isset($args['from']) ? $args['from'] : FALSE;
	$reply_to = isset($args['reply_to']) ? $args['reply_to'] : FALSE;
	$cc = isset($args['cc']) ? $args['cc'] : FALSE;
	$bcc = isset($args['bcc']) ? $args['bcc'] : FALSE;
	$alt_message = isset($args['alt_message']) ? $args['alt_message'] : FALSE;
	$attachments = isset($args['attachments']) && is_array($args['attachments']) ? $args['attachments'] : array();
	$ts = time();
	
	// If no 'from' value exists, use the CBEADS admin user's email address.
	if($from === FALSE)
	{
		$admin = Doctrine::getTable('cbeads\User')->find(1);
		$from = !empty($admin->email) ? $admin->email : FALSE;
	}
	
	$CI =& get_instance();
	$log_email = $CI->config->item('email_log_path');
	
	// Ensure required values exist.
	if($to === FALSE || $from === FALSE || $subject === FALSE || $message === FALSE)
	{
		if($log_email !== FALSE)
		{
			$data = array('ts' => $ts, 'to' => $to, 'from' => $from, 'subject' => $subject, 'cc' => $cc, 'bcc' => $bcc, 'reply_to' => $reply_to, 'msg' => $message, 'attachments' => $attachments, 'success' => FALSE, 'debug' => "Unable to send email because not all required fields where provided.");
			if(cbeads_write_to_email_log($data) === FALSE)
			{
				log_message("error", "Unable to write to email log file: " . json_encode($data));
			}
		}
		return FALSE;
	}
	
	// Set email settings and values.
	$CI->email->initialize($config);
	$CI->email->to($to);
	$CI->email->from($from);
	$CI->email->subject($subject);
	$CI->email->message($message);
	if($reply_to !== FALSE) $CI->email->reply_to($reply_to);
	if($cc !== FALSE) $CI->email->cc($cc);
	if($bcc !== FALSE) $CI->email->bcc($bcc);
	if($alt_message !== FALSE) $CI->email->set_alt_message($alt_message);
	foreach($attachments as $file)
		$CI->email->attach($file);
	
	// Send the email. Log success/failure if needed.
	if($CI->email->send())
	{	
		if($log_email !== FALSE)
		{
			$data = array('ts' => $ts, 'to' => $to, 'from' => $from, 'subject' => $subject, 'cc' => $cc, 'bcc' => $bcc, 'reply_to' => $reply_to, 'msg' => $message, 'attachments' => $attachments, 'success' => TRUE);
			if(cbeads_write_to_email_log($data) === FALSE)
			{
				log_message("error", "Unable to write to email log file: " . json_encode($data));
			}
		}
		return TRUE;
	}
	else
	{
		$debug = $CI->email->print_debugger();
		if($log_email !== FALSE)
		{
			$data = array('ts' => $ts, 'to' => $to, 'from' => $from, 'subject' => $subject, 'cc' => $cc, 'bcc' => $bcc, 'reply_to' => $reply_to, 'msg' => $message, 'attachments' => $attachments, 'success' => FALSE, 'debug' => $debug);
			if(cbeads_write_to_email_log($data) === FALSE)
			{
				log_message("error", "Unable to write to email log file: " . json_encode($data));
			}
		}
		return FALSE;
	}
}

?>