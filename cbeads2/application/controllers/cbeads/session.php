<?php
/** *********************** cbeads/session.php *************************
 * 
 *  This controller will inform the user who is online and if there are
 *  session records that are outdated. These can then be removed.
 *
 ** Changelog:
 *
 *  2010/03/31 - Markus
 *  - Created the controller and a function for displaying active users,
 *    guests and sessions that are expired.
 *  - Also created a function that will delete expired sessions.
 *
 *  2010/04/21 - Markus
 *  - Added tables for showing who is logged in and sessions that have
 *    expired. Plus a nice button to replace the link for clearing
 *    sessions.
 *
 ***********************************************************************/

class Session extends CI_Controller {

    // The expiry time of sessions. This should match what is defined in
    // application/config/config.php in the Session Variables section.
    // Not sure how to directly access that value!
    var $expiry_time = 601;
    
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(array('form','url'));
        $this->load->library('table');
    }
    
    // Calling this function will display a list of all active users,
    // users that have sessions but have not logged in (ie guests)
    // and a list of expired sessions. There is also a link to remove
    // those expired sessions.
    public function index() {

        //print "<pre>";
        $now = time();
        print "Unix time is now: $now<br>";
        $sessions = Doctrine::getTable('cbeads\Session')->findAll();

        $users = array();
        $guests = array();
        $expired = array();
        foreach($sessions as $session)
        {
            //print_r($session->user_data);
            if($session->user_data != NULL)
            {
                // Check the last activity. Entries that are expired should not be in the users list.
                if(($now - $session->last_activity) > $this->expiry_time)
                {
                    $expired[$session->session_id] = $session->last_activity;
                }
                else
                {
                    // Example of stored data format: a:3:{s:3:"~id";s:1:"1";s:6:"~uname";s:5:"admin";s:9:"~fullname";s:27:"Administrator Administrator";}
                    $elements = preg_split('/[{,}]/', $session->user_data, -1);
                    $elements = preg_split('/;/', $elements[1], -1, PREG_SPLIT_NO_EMPTY);
                    $user_data = array();
                    for($i = 0; $i < count($elements); $i+=2)
                    {
                        $key = $this->getValueFromSessionString($elements[$i]);
                        $val = $this->getValueFromSessionString($elements[$i + 1]);
                        $user_data[$key] = $val;
                    }
                    $user_data['last_activity'] = $session->last_activity;
                    $users[] = $user_data;
                }
            }
            else
            { 
                // Check if a session has expired, or if it is still valid (but guest is not logged in).
                if(($now - $session->last_activity) > $this->expiry_time)
                {
                    $expired[$session->session_id] = $session->last_activity;
                }
                else
                {
                    $guests[$session->session_id] = $session->last_activity;
                }
            }
        }
        
        print "<h3>Number of guests: " . count($guests) . "</h3>";
        print "<h3>Logged in users: </h3>";
        
        $tmpl = array ('table_open' => '<table border="1px" cellpadding="4" cellspacing="0">');
        $this->table->set_template($tmpl);
        $this->table->set_heading(array('ID', 'Username', 'Full Name', 'Last Activity'));
        foreach($users as $user)
        {
            $this->table->add_row($user['~id'], $user['~uname'], $user['~fullname'], $user['last_activity']);
        }
        echo $this->table->generate();
        
        if(count($expired) > 0)
        {
            print "<h3>Sessions that have expired: </h3>";
            
            $this->table->clear();
            $this->table->set_heading(array('Session ID'));
            foreach($expired as $session => $last)
            {
                $this->table->add_row($last);
            }
            echo $this->table->generate();
            
            print '<br>';
            $js = 'onclick="window.location.href=\''.site_url('cbeads/session/removeExpired').'\'"';
            echo form_button(array('name' => 'create'), "Remove Expired sessions", $js);
        }
    }   

    // Calling this function will remove any session whose last activity
    // field is older than Now - expiry time. The index function is then
    // called for displaying the list of users and expired sessions,etc.
    public function removeExpired()
    {
        $cutoff = time() - $this->expiry_time;
        $q = Doctrine_Query::create()
            ->delete('cbeads\Session s')
            ->where("s.last_activity < '$cutoff'");

        $count = $q->execute();
        print "<h3>Deleted $count expired sessions!</h3>";
        print "<br>";
        $this->index();
    }


    // Extracts the string from one of the components in the session user data
    // string. 
    // $component - the session component with the data to extract.
    //              It will be something like: s:10:"Some value".
    // Returns the extracted string data, or the string passed in case
    // it is NULL or empty.
    private function getValueFromSessionString($component)
    {
        if($component == NULL || $component == "") return $component;
        $val = strstr($component, '"');
        return substr($val, 1, strlen($val) - 2);
    }
}
