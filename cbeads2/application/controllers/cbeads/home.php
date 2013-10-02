<?php
/** ********************** cbeads/home.php *************************
 *
 *  This controller displays the home page for the user. For now it
 *  just shows the user name. In the future it may display user
 *  specific pages. 
 *
 ** Changelog:
 *
 *  2010/08/12 - Markus
 *  - Page now displays team currently in.
 *
 *  2010/12/13 - Markus
 *  - Now using the cbeads style sheet.
 *
 *  2011/08/09 - Markus
 *  - Fixed typo (missing /) in style sheet url.
 *
 ******************************************************************/

class Home extends CI_Controller {

    function index()
    {
        echo '<link href="'.$this->config->item('base_web_url').'/cbeads/css/cbeads_style.css" type="text/css" rel="stylesheet">';
        echo "<div style='text-align: center;'>Welcome " . $this->session->userdata('~fullname');
        echo '<br>';
        echo 'You are currently in team: ';
        $tr_id = $this->session->userdata('~team-role');
        $tr = Doctrine::getTable('cbeads\Team_role')->find($tr_id);
        if($tr !== FALSE)
        {
            echo $tr->Team->name;
        }
        echo '</div>';
    }
    
}