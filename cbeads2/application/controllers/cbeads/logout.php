<?php
/** *********************************** cbeads/logout.php *************************************
 *
 *  This controller performs a user logout from the system and redirects to the public home 
 *  page. Very simple.
 *
 ** Changelog:
 *
 *  2010/11/30 - Markus
 *  - Changed redirect to 'public/home/user_logout' from 'cbeads/login'. The  public home 
 *    controller is now the default one. Of course this redirect can be changed to whatever 
 *    controller is desired.
 *
 *  2010/12/02 - Markus
 *  - Now setting the cbeads_user_state cookie to a value of 0, indicating
 *    the user is no longer logged in.
 *
 *  2011/04/14 - Markus
 *  - When the user logs out it is now recorded in the access log.
 *
 *  2011/07/25 - Markus
 *  - Now saves the team-role in use so that it can be used as the active team-role when 
 *    logging in.
 *
 *  2011/11/02 - Markus
 *  - Now checking if there is a need to redirect to a custom 'logout' destination controller
 *    or to use default controller (public/home).
 *  - Instead of setting a cookie indicating that a logout happened, a message is passed to
 *    the destination controller.
 *
 *********************************************************************************************/
class Logout extends CI_Controller {

    function index()
    {
        // Record the logout and save the team role currently in use.
        $ts = time();
        $user_id = $this->session->userdata('~id');
		$teamrole = $this->session->userdata('~team-role');
		$u = Doctrine::getTable('cbeads\User')->find($user_id);
		if($u !== FALSE)
		{
			$status = json_decode($u->status, TRUE);
			if($status === NULL) $status = array();
			$status['last_team-role'] = $teamrole;
			$encoded =  json_encode($status);
			if($encoded !== NULL)
			{
				$u->status = $encoded;
				$u->save();
			}
		}
		
        $event = "logout";
        $data = array('event' => $event, 'uid' => $user_id, 'ts' => $ts, 'ip' => $_SERVER['REMOTE_ADDR']);
        cbeads_write_to_access_log($data);
        
        // Set the cbeads_user_state cookie to 0, indicating the user is no longer logged in.
        setcookie('cbeads_user_state', 0, 0, '/');
		// The routing cookie is no longer required.
		setcookie ('cbeads_routing', '', time() - 3600);
        // Destroy the session of this user and send the user back to the public home controller.
        $this->session->sess_destroy();
		// If a custom logout destination controller was specified, then use that, otherwise redirect to the
		// default logout controller. Any custom logout controller is expected to be publicly available.
		$msg = '/action_logged_out/You have logged out from the system';
		if(isset($_COOKIE['cbeads_routing']))
		{
			$data = unserialize($_COOKIE['cbeads_routing']);
			//echo cbeads_nice_vardump($data);
			if(!empty($data['logout']))
				redirect($data['logout'] . $msg);
		}
        redirect('/public/home/index' . $msg);
    }
    
}