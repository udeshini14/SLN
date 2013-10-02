<?php

/** ***********************************   cbeads/access_logs.php  *************************************
 *
 *  Displays information stored in the access logs.
 *
 ** Changlog:
 *  2011/08/09 - Markus
 *  - Now only asking for log ins and log outs when getting individual user stats.
 *  - The user stats now properly report the last log in time. Cannot assume the logs are in the 
 *    correct time order. Also added check to set the last log in time only when the event type is
 *    a log in.
 *  - Added a check so make sure the user id is valid (not a empty value like 0, false, ''). But what
 *    happens with records for people that have been deleted from the system?
 *  - The overall stats now include unique log ins (the number of different people) for each day of a
 *    month and overall for the month. Made associated changes to the access_logs view file to display
 *    this data.
 *
 *  2013/09/02 - Markus
 *  - Now getting access log path from the config file. Changed default access log path to 
 *    /application/logs (for new version of CodeIgniter).
 *
 *****************************************************************************************************/

class Access_logs extends CI_Controller 
{

	const ACTION_LOGIN_SUCCESS = 1;
	const ACTION_LOGIN_FAILURE = 2;
	const ACTION_LOGOUT = 4;
	const ACTION_EXPIRED = 8;
	const ACTION_ALL = 255;

    function __construct()
    {
		header('Server: ');
        parent::__construct();
        $this->load->helper(array('url', 'html'));
        include_once(APPPATH . 'libraries/Renderer.php');
    }

    function index()
    {
		$R = new Renderer();
		$R->render_as_menu(array(
			'item_order' => array('overall_stats', 'user_stats'),
			'items' => array(
				'overall_stats' => array(
					'label' => 'Overall Statistics',
					'content' => array($this, 'overall_stats')
				),
				'user_stats' => array(
					'label' => 'User Statistics',
					'content' => array($this, 'user_stats')
				)
			)
		));
    }

	public function overall_stats()
	{
		$data['logs'] = json_encode($this->get_available_logs());
		$this->load->view('cbeads/access_logs', $data);
	}
	
	// Collects stats for individual users.
	public function user_stats()
	{
		$events = $this->get_access_log_data(NULL, self::ACTION_LOGIN_SUCCESS | self::ACTION_LOGOUT);
		$records = Doctrine::getTable('cbeads\User')->findAll();
		$all_users = array();	// List of all users in the system and list of users that haven't logged in. Assume no one has logged in yet.
		foreach($records as $user)
		{
			$all_users[$user->id] = array('name' => $user->firstname . ' ' . $user->lastname . ' (' . $user->uid . ')');
		}
		$users = array();	// stores users that have used the system and their stats.
		$event_map = array('login_success' => 'login', 'login_failure' => 'login_fail', 'logout' => 'logout');
		foreach($events as $year => $months)
		{
			foreach($months as $month => $month_data)
			{
				foreach($month_data as $event)
				{
					//cbeads_nice_vardump($event);
					$id = $event['uid'];
					if(!empty($id))	// Need a valid id. What about users that have been deleted?
					{
						$day = date('Y-m-d', $event['ts']);
						if(!isset($users[$id]))
						{
							$users[$id] = array('last_login' => $day);
							$users[$id]['name'] = $all_users[$id]['name'];
							$users[$id]['login'] = 0;
							$users[$id]['logout'] = 0;
							unset($all_users[$id]);	// Remove this user as they have logged in.
						}
						$event_type = $event_map[$event['event']];
						//if(!isset($users[$id][$event_type])) $users[$id][$event_type] = 0;
						$users[$id][$event_type]++;
						if($event_type == 'login' && $users[$id]['last_login'] < $day)	// In case the recorded data is not in the right time order.
							$users[$id]['last_login'] = $day;
					}
				}
			}
		}
		//cbeads_nice_vardump($all_users);
		//cbeads_nice_vardump($users);
		//echo count($users) / (count($users) + count($all_users)) * 100;
		$users_never = array();	// stores users that have never used the system.
		$data = array();
		$data['users'] = $users;
		$data['never_logged_in_users'] = $all_users;
		$data['login_count'] = count($users);
		$data['no_login_count'] = count($all_users) - $data['login_count'];
		$data['login_percent'] = round((count($users) / (count($users) + count($all_users)) * 100), 1);
		$data['no_login_percent'] = 100 - $data['login_percent'];
		$this->load->view('cbeads/access_logs_users', $data);
	}
	
	
	private function get_access_log_data($dates = NULL, $actions = self::ACTION_ALL)
	{
		$dates = $this->read_logs($dates);
		//cbeads_nice_vardump($dates);
		
		$events = array();
		
		foreach($dates as $date => $data)
		{
			$year = substr($date, 0, 4);
			$month = substr($date, 4, 2);
			if(!isset($events[$year])) $events[$year] = array();
			$events[$year][(int)$month] = array();
			foreach($data as $d)
			{
				$d = json_decode($d, TRUE);
				if($d !== NULL)
				{
					// Only include events that are requested.
					if($d['event'] == 'login_success' && $actions & self::ACTION_LOGIN_SUCCESS)
						$events[$year][(int)$month][] = $d;
					if($d['event'] == 'login_failure' && $actions & self::ACTION_LOGIN_FAILURE)
						$events[$year][(int)$month][] = $d;
					elseif($d['event'] == 'logout' && $actions & self::ACTION_LOGOUT)
						$events[$year][(int)$month][] = $d;
					elseif($d['event'] == 'session_expired' && $actions & self::ACTION_EXPIRED)
						$events[$year][(int)$month][] = $d;
				}
			}
		}
		
		//cbeads_nice_vardump($years);
		return $events;
	}
	
	// Find all months for which access logs are available.
	// Returns an associative array where the keys are years and the values array of months.
	private function get_available_logs()
	{
		$curdir = getcwd();		// Remember directory currently in use.
		
		$data = array();
		
		$path = $this->config->item('access_log_path');
		// See if logging was disabled.
		if($path === FALSE) return $data;
		// If no custom path, use default.
		if(empty($path)) $path = APPPATH . 'logs';
		
		if(is_dir($path))
		{
			if($dh = opendir($path))
			{
				while(($file = readdir($dh)) !== FALSE)
				{
					if(preg_match('/^access-log_[0-9]{4}-[0-9]{2}\.log$/', $file))
					{
						preg_match('/([0-9]{4})-([0-9]{2})/', $file, $matches);
						$data[$matches[1]][] = (int)$matches[2];
					}
				}
			}
			else
			{
				echo cbeads_error_message("Unable to open directory: $path");
			}
		}
		else
		{
			echo cbeads_error_message('Invalid directory: ' . $path);
			return FALSE;
		}
		
		
		chdir($curdir);		// Revert back to used directory.
		
		return $data;
	}
	
	private function read_logs($dates = NULL)
	{
		$curdir = getcwd();		// Remember directory currently in use.
		
		if(!is_array($dates) || empty($dates)) $dates = NULL;
		$data = array();
		
		$path = $this->config->item('access_log_path');
		// See if logging was disabled.
		if($path === FALSE) return $data;
		// If no custom path, use default.
		if(empty($path)) $path = APPPATH . 'logs';
		
		if(is_dir($path))
		{
			if($dh = opendir($path))
			{
				while(($file = readdir($dh)) !== FALSE)
				{
					if(preg_match('/^access-log_[0-9]{4}-[0-9]{2}\.log$/', $file))
					{
						preg_match('/([0-9]{4})-([0-9]{2})/', $file, $matches);
						if($dates == NULL || in_array($matches[1].$matches[2], $dates))
						{
							$data[$matches[1].$matches[2]] = file($path .'/'.$file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
						}
					}
				}
			}
			else
			{
				echo cbeads_error_message("Unable to open directory: $path");
			}
		}
		else
		{
			echo cbeads_error_message('Invalid directory: ' . $path);
			return FALSE;
		}
		
		
		chdir($curdir);		// Revert back to used directory.
		
		return $data;
	}
	
	// Returns events per month for a given year.
	// year: the year to retrieve the month events for.
	// months: optional, an array specifying which months to get. If left as default, all months are retrieved.
	private function _get_monthly_events_for_year($year, $months = array())
	{
		for($i = 1; $i < 13; $i++)
		{
			if(empty($months) || in_array($i, $months))
				$dates[] = $year . (($i < 11) ? "0$i" : $i);	
		}
		$data = $this->get_access_log_data($dates);
		//cbeads_nice_vardump($data);
		return $data;
	}
	
	// Returns events per day for a given year and month.
	private function _get_daily_events_for_month($year, $month)
	{
		if($month < 10) $month = "0$month";
		$data = $this->get_access_log_data(array($year.$month));
		//cbeads_nice_vardump($data);
		$event_map = array('login_success' => 'login', 'login_failure' => 'login_fail', 'logout' => 'logout', 'session_expired' => 'expired');
		
		$events = array('login_unique' => array(), 'login' => array(), 'login_fail' => array(), 'logout' => array(), 'expired' => array());
		foreach($data as $year => $months)
		{
			foreach($months as $month => $month_data)
			{
				$month_logged_in = array();	// ids of users that have already logged in this month
				$day_logged_in = array();	// ids of users that have already logged for a day
				foreach($month_data as $event)
				{
					$day = date('j', $event['ts']);
					$event_type = $event_map[$event['event']];
					// Record unique log ins on a daily and monthly basis.
					if($event_type == 'login' && !empty($event['uid']) && !isset($day_logged_in[$day][$event['uid']]))
					{
						$day_logged_in[$day][$event['uid']] = TRUE;
						if(!isset($month_logged_in[$event['uid']])) $month_logged_in[$event['uid']] = TRUE;
					}
					if(!isset($events[$event_type][$day])) $events[$event_type][$day] = 0;
					$events[$event_type][$day]++;
				}
				// Add unique log in info.
				$events['login_unique_month_total'] = count($month_logged_in);
				foreach($day_logged_in as $day => $users)
				{
					$events['login_unique'][$day] = count($users);
				}
			}
		}
		return json_encode($events);
		//cbeads_nice_vardump($events);
	}
	
	public function get_daily_events_for_month()
	{
		$year = isset($_POST['year']) ? (int)$_POST['year'] : FALSE;
		$month = isset($_POST['month']) ? (int)$_POST['month'] : FALSE;
		if($year === FALSE || $month === FALSE)
			echo json_encode(array('success' => FALSE, 'msg' => 'year and month must be provided!'));
		else
			echo $this->_get_daily_events_for_month($year, $month);
	}
	
	public function get_monthly_events_for_year()
	{
		$year = isset($_POST['year']) ? (int)$_POST['year'] : FALSE;
		$months = isset($_POST['months']) ? (array)$_POST['months'] : array();
		if($year === FALSE)
			echo json_encode(array('success' => FALSE, 'msg' => 'year must be provided!'));
		else
		{
			$data = $this->_get_monthly_events_for_year($year, $months);
			$data = !empty($data[$year]) ? $data[$year] : array();
			$event_map = array('login_unique' => array(), 'login_success' => 'login', 'login_failure' => 'login_fail', 'logout' => 'logout', 'session_expired' => 'expired');
			$events = array();
			foreach($months as $month)
			{
				$events[$month] = array('login_unique' => array(), 'login_unique_month_total' => 0, 'login' => array(), 'login_fail' => array(), 'logout' => array(), 'expired' => array());
				if(isset($data[$month]))
				{
					$month_data = $data[$month];
					$month_logged_in = array();	// ids of users that have already logged in this month
					$day_logged_in = array();	// ids of users that have already logged for a day
					foreach($month_data as $event)
					{
						$day = date('j', $event['ts']);
						$event_type = $event_map[$event['event']];
						// Record unique log ins on a daily and monthly basis.
						if($event_type == 'login' && !empty($event['uid']) && !isset($day_logged_in[$day][$event['uid']]))
						{
							$day_logged_in[$day][$event['uid']] = TRUE;
							if(!isset($month_logged_in[$event['uid']])) $month_logged_in[$event['uid']] = TRUE;
						}
						//if(!isset($events[$event_type])) $events[$event_type] = array();
						if(!isset($events[$month][$event_type][$day])) $events[$month][$event_type][$day] = 0;
						$events[$month][$event_type][$day]++;
					}
					// Add unique log in info.
					$events[$month]['login_unique_month_total'] = count($month_logged_in);
					foreach($day_logged_in as $day => $users)
					{
						$events[$month]['login_unique'][$day] = count($users);
					}
				}
			}
			echo json_encode($events);
		}
	}

}

?>