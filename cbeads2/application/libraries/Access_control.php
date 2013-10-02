<?php

/** ********************************** Access_control.php ****************************************
 *  
 * This class is used by codeigniter.php to test if a user has access to the requested controller.
 * When check_access is called, the function either returns, sends the user to the home page because
 * their session has expired, or shows the user a 404 because no matching controller was found.
 *
 * ***************
 * Changelog:
 *
 * 2010/03/29 - Markus
 * - The user session can be validated using authenticate_user(). The
 *   check_access function doesn't do anything yet. It just has some
 *   code to test that database access with doctrine works.
 *
 * 2010/09/20 - Markus
 * - Moved cbeads/routines include from Renderer_helper.php to here because 
 *   this function gets run every time before any controller is loaded.
 * - Updated check_access() to finally test if the current user has access to
 *   the application and function that was requested. If not, they are shown
 *   a 404 message.
 *
 * 2010/09/21 - Markus
 * - Added 'cbeads/home' to globally viewable controllers since it gets called auto
 *   matically when someone logs in.
 *
 *  2010/11/30 - Markus
 *  - Modified authenticate_user() so that if the user is not logged in and the controller is not 
 *    a publicly available function, then the user is redirected to the public/home page. The same
 *    thing happens when no application or controller is specified as part of the URL.
 *  - Modified check_access() to allow access to any controller in the 'public' application.
 *
 *  2010/12/02 - Markus
 *  - Updated authenticate_user() so that if a user is determined to be logged in, it will set a 
 *    cookie called 'cbeads_user_state' to indicate that fact. This cookie is checked when the user
 *    is not logged in. A non logged in user that was previously logged in must mean the session has
 *    expired. In such cases a session is expired message is displayed via the show_home_page 
 *    controller.
 *
 *  2011/01/06 - Markus
 *  - The check_access function has been changed so that it now checks if the client is a logged in 
 *    user or not. It also now gets a list of publicly available functions to see if the requested
 *    controller is in the list.
 *  - The authenticate_user function has been renamed to is_logged_in. It now returns true or false
 *    to indicate if the user is logged in. It still redirects and exists the script if necessary.
 *  - Added function get_publicly_available_functions to return the list of function ids that are
 *    publicly available. Used by check_access().
 *
 *  2011/01/12 - Markus
 *  - check_access() now tests if a controller is defined in the url. If not, then the default
 *    namespace and controller is shown.
 *
 *  2011/04/13 - Markus
 *  - Deleted code no longer needed.
 *  - When a user's session has expired it is recorded in the access log.
 *
 *  2011/04/18 - Markus
 *  - Moved some code around in check_access(). Only want to allow access to globally accessible 
 *    controller to those who are logged in.
 *  - Now using functions from cbeads_helper and not the legacy helper module (in /controllers/cbeads)
 *
 *  2011/08/08 - Markus
 *  - Removed call to include cbeads/routines.php as the cbeads helper contains everything needed.
 *
 *  2011/08/21 - Markus
 *  - Updated get_publicly_available_functions() to return all functions that have the 'is_public'
 *    flag set to true.
 *  - check_access() now uses tests the requested controller id again the public ids. Public functions 
 *    can be accessed by anyone!
 *
 *  2011/11/02 - Markus
 *  - In check_access, the cbeads_user_state cookie is no longer set if a user has a session. This is
 *    now done in the public/home controller on successful login (makes more sense there).
 *  - Now checking the cbeads_routing cookie to determine where to redirect when the user's session
 *    has expired. Expiration message is sent along with the controller url.
 *
 * ***************************************************************************************************/

class Access_control
{
	function check_access()
	{
		// Get a copy of our dummy CodeIgniter super object. It has everything necessary to run the
		// the code in this class (see /system/core/CodeIgniter.php).
		$ci =& get_instance();
		$ci->load->helper(array('doctrine_loader', 'url', 'cbeads'));
		
		$namespace = $ci->uri->segment(1);
        $class = $ci->uri->segment(2);
		
		if(!$class) // If the class is not specified in the url, then direct to the default controller.
        {
            @include(APPPATH.'config/routes'.EXT);
            $route_info = ( ! isset($route) OR ! is_array($route)) ? array() : $route;
            // var_dump($route_info);
            
            if(isset($route_info['default_controller']) AND $route_info['default_controller'] != '')
            {
                $default = strtolower($route_info['default_controller']);
				redirect($default);
            }
            else
                show_404(); // cannot proceed if no default controller is provided.
        }
		
		// Functions in the public namespace automatically are allowed to be accessed.
        if($namespace == 'public') return;

        // Get the ids of the requested application and controller.
        $appctrl = cbeads_get_current_app_ctrl_func();
		
		// Functions that have the 'is_public' flag set to true can be accessed by anyone!
        $available_functions = $this->get_publicly_available_functions();
		if(in_array($appctrl['controller_id'], $available_functions))
			return TRUE;
		$available_functions = array();
		
        if($this->is_logged_in($ci))   // If a user is logged on, need to find what functions they can access.
        {
            // Get team-roles the user has access to.
            $teamroles = cbeads_get_teamrole_ids_for_user($ci->session->userdata('~id'));
            // Check what functions are accessible via the given team-roles.
            $q = Doctrine_Query::create()
                ->select('trf.function_id')
                ->from('cbeads\team_role_function trf')
                ->whereIn('trf.team_role_id', $teamroles)
                ->groupBy('trf.function_id');
            $ids = $q->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
            foreach($ids as $id)
                $available_functions[] = $id['function_id'];
            $available_functions = array_unique($available_functions);

			if(in_array($appctrl['controller_id'], $available_functions, false))
				return;

			// Function id requested could not be matched to an id that the user has access to.
			// There are some universally accessible controllers (for people that are logged in),
			// so need to test for them.
			$allowable = array('access_bar', 'menu', 'home', 'logout');
			if($appctrl['application'] == 'cbeads' && in_array($appctrl['controller'], $allowable))
				return TRUE;
        }

        // At this point there is no other choice but to show the page not found page. The script exits when calling that function.
        show_404();
	}



    // Checks if the client has access to the URL specified application and controller.
    // If the function returns, that means access was granted. If it doesn't, the 
    // script was exited because the page controller was not found (404) or the 
    // client's session has expired.
    function _check_access()
    {
        //require_once(APPPATH."controllers/cbeads/routines.php");    // Has usefull routines.
        
        $ci =& get_instance();
        $ci->load->helper(array('url', 'cbeads'));
        
        $namespace = $ci->uri->segment(1);
        $class = $ci->uri->segment(2);
        
        if(!$class) // If the class is not specified in the url, then direct to the default controller. Usually the home page.
        {
            @include(APPPATH.'config/routes'.EXT);
            $route_info = ( ! isset($route) OR ! is_array($route)) ? array() : $route;
            //nice_vardump($route_info);
            
            if(isset($route_info['default_controller']) AND $route_info['default_controller'] != '')
            {
                $default = strtolower($route_info['default_controller']);
                redirect($default);
            }
            else
                show_404(); // cannot proceed if no default controller is provided.
        }

        // Functions in the public namespace automatically are allowed to be accessed.
        if($namespace == 'public') return;

        // Get the ids of the requested application and controller.
        $appctrl = cbeads_get_current_app_ctrl_func();
		
		// Functions that have the 'is_public' flag set to true can be accessed by anyone!
        $available_functions = $this->get_publicly_available_functions();
		if(in_array($appctrl['controller_id'], $available_functions))
			return TRUE;
		$available_functions = array();
		
        if($this->is_logged_in())   // If a user is logged on, need to find what functions they can access.
        {
            // Get team-roles the user has access to.
            $teamroles = cbeads_get_teamrole_ids_for_user($ci->session->userdata('~id'));
            // Check what functions are accessible via the given team-roles.
            $q = Doctrine_Query::create()
                ->select('trf.function_id')
                ->from('cbeads\team_role_function trf')
                ->whereIn('trf.team_role_id', $teamroles)
                ->groupBy('trf.function_id');
            $ids = $q->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
            foreach($ids as $id)
                $available_functions[] = $id['function_id'];
            $available_functions = array_unique($available_functions);

			if(in_array($appctrl['controller_id'], $available_functions, false))
				return;

			// Function id requested could not be matched to an id that the user has access to.
			// There are some universally accessible controllers (for people that are logged in),
			// so need to test for them.
			$allowable = array('access_bar', 'menu', 'home', 'logout');
			if($appctrl['application'] == 'cbeads' && in_array($appctrl['controller'], $allowable))
				return TRUE;
        }

        // At this point there is no other choice but to show the page not found page. The script exits when calling that function.
        show_404();
    }
    
    // Checks if the client is currently logged into the system. If so, the function
    // returns true. If not, the function returns false. If the user's session has
    // expired, the function redirects the user to the home page and exits the script.
    private function is_logged_in($ci)
    {
        $ci->load->library('session');
        
        $logged_in = FALSE;
        $has_cookie = $ci->input->cookie($ci->session->sess_cookie_name);
        
        // Get the application and function being requested.
        $app = $ci->uri->segment(1);
        $class = $ci->uri->segment(2);
        
        // Check if a session exists for this user where the username is set.
        if($ci->session->userdata('~uname')) 
        {
            return TRUE;
        }
        else
        {
            // If the cbeads_user_state cookie is set and has a value of 1, this indicates the user's session has expired.
            if(isset($_COOKIE['cbeads_user_state']) && $_COOKIE['cbeads_user_state'] == 1)
            {
                // Record the session has expired.
                $ts = time();
                $event = "session_expired";
                $data = array('event' => $event, 'ts' => $ts, 'ip' => $_SERVER['REMOTE_ADDR']);
                cbeads_write_to_access_log($data);
                setcookie('cbeads_user_state', 0, 0, '/');
				if(isset($_COOKIE['cbeads_routing']))
				{
					$data = unserialize($_COOKIE['cbeads_routing']);
					if(!empty($data['login']))
						redirect($data['login'] . '/action_expired/Your session has expired. Please log in again.');
				}
                //redirect('public/home/show_home_page/1');
				echo '<script>window.parent.location.href="' . site_url('public/home/index/action_expired/Your session has expired. Please log in again.') . '";</script>';
				exit();
            }
            else    // Was not logged in previously
            {
                return FALSE;
            }
        }
    }
    
    // Returns a list of function ids that are publicly available (their is_public field is set to true).
	// Note that CBEADS functions cannot be made public.
    function get_publicly_available_functions()
    {
        $ids = array();
		$q = Doctrine_Query::create()
				->select('f.id')
				->from('cbeads\Function_ f, f.Application a')
				->where('f.is_public = ?', TRUE)
				->andWhere('a.name != ?', 'CBEADS');	// Cannot allow public CBEADS functions.
		$funcs = $q->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
		foreach($funcs as $func)
			$ids[] = $func['id'];

        return $ids;
    }
    
}