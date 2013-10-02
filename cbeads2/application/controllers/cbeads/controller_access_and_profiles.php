<?php

/*************************   cbeads/controller_access_and_profiles.php   *****************************
 *
 *  Lets one view data collected on controller accesses and profiling information such as 
 *  execution times and memory usage.
 *
 ** Changelog:
 *
 *  2011/10/19 - Markus
 *  - First version which can be used to see what controllers have been used or not used and also to
 *    see what individual users have used based on all the usage data available.
 *
 ****************************************************************************************************/

class Controller_access_and_profiles extends CI_Controller 
{
    function __construct()
    {
        parent::__construct();
        $this->load->helper(array('url', 'html'));
        include_once(APPPATH . 'libraries/Renderer.php');   
    }

/*	General Overview:
	
	Can select time period for these Qs?:
	
		What controllers have been used?
		What controllers have not been used?
		For used controllers, list people that have used them? Show percentage?
		View controller usage by user?
	
	Access Time Charts:
	
	Again, select time period for these: Y M D
	
	Usage of app/controller/function (can drill down) over time
	
	
	Profiling:
	
	Again, select time period for these: Y M D
	
	Memory
	- List controllers (by app) memory usage (avg, min, max) - can sort
	
	Execution Time
	- List controllers (by app) execution time (avg, min, max) - can sort
*/
	
    function index()
    {
        echo doctype();
        echo '<link href="'.base_url().'cbeads/css/cbeads_style.css" type="text/css" rel="stylesheet">'; 
		
        $options = array(
			'item_order' => array('access_overview', 'access_charts', 'profiling'),
			'items' => array(
				'access_overview' => array('content' => array($this, '_generate_overview_page')),
				'access_charts' => array('content' => array($this, '_generate_access_time_charts_page')),
				'profiling' => array('content' => array($this, '_generate_profiling_page')),
			)
        );
        $renderer = new Renderer();
        $result = $renderer->render_as_menu($options);
        if($result['success'] == FALSE)
        {
            echo cbeads_error_message('There was an error rendering the menu:<br>' . $result['msg']);
        }
    }

	// Generates the overview page. This is used to see 
	function _generate_overview_page()
	{
		$data = array();
		// Need to provide all dates where a record was accessed.
		$q = Doctrine_Query::create()
			->select('f.day')
			->from('cbeads\Function_access_profile f')
			->groupBy('f.day');
		$results = $q->fetchArray();
		$dates = array();
		foreach($results as $date)
		{
			list($year, $month, $day) = preg_split('/\-/', $date['day']);
			$dates[$year][$month][] = $day;
		}
		$data['available_dates'] = json_encode($dates);
		
		// Only users that exist will be supplied.
		$q = Doctrine_Query::create()
			->select('u.id, u.uid')
			->from('cbeads\User u');
		$results = $q->fetchArray();
		$users = array();
		foreach($results as $r)
		{
			$users[$r['id']] = array('uname' => $r['uid']);
		}
		$data['users'] = json_encode($users);

		// Only applications and controllers that exist will be supplied. Need to also find functions that 
		// have been used.
		$q = Doctrine_Query::create()
			->select('a.id, a.name, f.id, f.name')
			->from('cbeads\Application a, a.Functions f');
		$a_results = $q->fetchArray();
		$q = Doctrine_Query::create()
			->select('f.ctrl_id, f.func_name')
			->from('cbeads\Function_access_profile f')
			->groupBy('f.ctrl_id, f.func_name');
		$f_results = $q->fetchArray();
		$functions = array();
		foreach($f_results as $r)
		{
			$functions[$r['ctrl_id']][] = $r['func_name'];
		}
		$apps = array();
		foreach($a_results as $r)
		{
			$controllers = array();
			foreach($r['Functions'] as $ctrl)
			{
				$funcs = isset($functions[$ctrl['id']]) ? $functions[$ctrl['id']] : array();
				$controllers[$ctrl['id']] = array('name' => $ctrl['name'], 'funcs' => $funcs);
			}
		
			$apps[$r['id']] = array('name' => $r['name'], 'ctrls' => array());
			foreach($r['Functions'] as $c)
			{
				$apps[$r['id']]['ctrls'] = $controllers;
			}
		}
		//echo cbeads_nice_vardump(json_encode($apps));
		$data['apps'] = json_encode($apps);
		
		$this->load->view('cbeads/controller_access_overview', $data);
	}
	
	
	
	
	
	
	
	function _generate_access_time_charts_page()
	{
		echo "TODO: Usage Time charts";
		// $data = array();
		// $this->load->view('cbeads/controller_access_charts', $data);
	}
	
	
	
	function _generate_profiling_page()
	{
		echo "TODO: Memory and running time info";
		// $data = array();
		// $this->load->view('cbeads/controller_profiling', $data);
	}
	
	
	
	
	
	
	// Gets which app/controller/functions each user used over all the years.
	function get_allyears_usage_data()
	{
		$users = array();
		
		$q = Doctrine_Query::create()
			->select('f.user_id, f.app_id, f.ctrl_id, f.func_name, SUM(f.hits) hits')
			->from('cbeads\Function_access_profile f')
			->groupBy('f.user_id, f.app_id, f.ctrl_id, f.func_name');
		$results = $q->fetchArray();
		foreach($results as $r)
		{
			$users[$r['user_id']][$r['app_id'] . ' ' . $r['ctrl_id'] . ' '. $r['func_name']] = $r['hits'];
		}
		echo json_encode($users);
	}
	
	function get_year_usage_data()
	{
		$year = $_POST['year'];
		$date = new DateTime();
		$date->setDate($year, 1, 1);
		$from = $date->format('Y-m-d');
		$date->add(new DateInterval('P1Y'));
		$to = $date->format('Y-m-d');
		$this->get_usage_data($from, $to);
	}
	
	function get_month_usage_data()
	{
		$year = $_POST['year'];
		$month = $_POST['month'];
		$date = new DateTime();
		$date->setDate($year, $month, 1);
		$from = $date->format('Y-m-d');
		$date->add(new DateInterval('P1M'));
		$to = $date->format('Y-m-d');
		$this->get_usage_data($from, $to);
	}
	
	function get_day_usage_data()
	{
		$year = $_POST['year'];
		$month = $_POST['month'];
		$day = $_POST['day'];
		$date = new DateTime();
		$date->setDate($year, $month, $day);
		$from = $date->format('Y-m-d');
		$date->add(new DateInterval('P1D'));
		$to = $date->format('Y-m-d');
		$this->get_usage_data($from, $to);
	}
	
	
	function get_usage_data($from, $to)
	{
		$users = array();
		
		$q = Doctrine_Query::create()
			->select('f.user_id, f.app_id, f.ctrl_id, f.func_name, SUM(f.hits) hits')
			->from('cbeads\Function_access_profile f')
			->where('f.day >= ? AND f.day < ?', array($from, $to))
			->groupBy('f.user_id, f.app_id, f.ctrl_id, f.func_name');
		$results = $q->fetchArray();
		foreach($results as $r)
		{
			$users[$r['user_id']][$r['app_id'] . ' ' . $r['ctrl_id'] . ' '. $r['func_name']] = $r['hits'];
		}
		echo json_encode($users);
	}
	
	
	
	
	
	
	
	
	
	
	
}

?>