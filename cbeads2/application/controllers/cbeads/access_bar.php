<?php
/** ********************************* cbeads/access_bar.php ***********************************
 *
 *  This controller creates the access bar at the top of the screen (above the content area).
 *  It allows a user to logout and to team-roles which will change what functions can be
 *  accessed in the menu.
 *
 ** Changelog:
 *
 *  2010/06/17 - Markus
 *  - Created file. For now it only has the logout action.
 *
 *  2010/06/20 - Markus
 *  - When calling index(), the team-roles available to the user are passed to the view.
 *  - Added the function changedTeamRole which gets called when the user changes their
 *    team-role.
 *
 *  2010/07/19 - Markus
 *  - Added doctype to the start of the generated html.
 *  
 *  2010/08/24 - Markus
 *  - No longer sending username to view file, as the menu now  displays the user's username.
 *
 *  2011/08/08 - Markus
 *  - Removed old code.
 *  - Removed include for the cbeads/routines and updated functions called to use those in
 *    cbeads helper.
 *
 ******************************************************************/

class Access_bar extends CI_Controller 
{
    // Default action generates the access bar using the access_bar view file.
    function index()
    {
        $this->load->helper(array('url', 'html'));
        echo doctype();
        
        //require_once(APPPATH."controllers/cbeads/routines.php");
        
        // Get all team-roles for this user and the current team role.
        $cur_teamrole = $this->session->userdata('~team-role');
        $id = $this->session->userdata('~id');
        $data = array('team_roles' => array(), 'cur_team_role' => $cur_teamrole);
        
        $user = Doctrine::getTable('cbeads\user')->find($id);
        if($user === FALSE)
        {
            cbeads_error_message('Could not find user with id \''. $id .'\'');
        }
        
        $team_roles = $user->TeamRoles;
        foreach($team_roles as $team_role)
        {
            $data['team_roles'][$team_role->id] = $team_role->stringify_self();
        }
        
        $this->load->view('cbeads/access_bar', $data);
    }
    
    // This function gets called when the user changes their team role via the access bar.
    // Need to make sure the team role id received is valid before switching the user to it.
    function changedTeamRole($new_id)
    {
        $cur_teamrole = $this->session->userdata('~team-role');
        $id = $this->session->userdata('~id');
        
        // Make sure this new team role is valid.
        $user = Doctrine::getTable('cbeads\user')->find($id);
        if($user === FALSE)
        {
            echo 'Could not find user with id \''. $id .'\'';
            return;
        }
        
        $team_roles = $user->TeamRoles;
        $found = FALSE;
        foreach($team_roles as $team_role)
        {
            if($team_role->id == $new_id) $found = TRUE;
        }
        
        if($found == FALSE)
        {
            echo 'This team role does not exist!';
        }
        else
        {
            $this->session->set_userdata('~team-role', $new_id);
            echo 'success';
        }
        
    }
    
}