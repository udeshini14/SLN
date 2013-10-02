<?php

/**    nsf2/enter_crop_details.php
 *
 *  This function allows to enter existing crop details of a variety which is per-defined by the Agriculture statistical officer.
 *
 ******************************************************************/

class Generate_statistical_report extends CI_Controller 
{
    // Instantiates the controller class.
    function __construct()
    {
        parent::__construct();
        // Helper modules can be loaded.
        $this->load->helper(array('url', 'html'));
        // Includes the Renderer class needed for rendering menus/tables/forms/etc
        include_once(APPPATH . 'libraries/Renderer.php');   
    }

    // By default, the index() function is called unless the url specifies a different function to use.
    function index()
	
    {
      $q = Doctrine_Query::create()
    ->select('b.crop_variety_id','b.planting_stage','b.expected_yield')        // select the book IDs.
    ->from('nsf2\crop_status b') ;      // select the book model (alias 'b')
   // ->where('b.price > 30')     // set bottom price boundary
    //->andWhere('b.price < 70'); // set top price boundary

			$R = new Renderer();
			$options = array(
				'model' => 'nsf2\crop_status',
				'title' => FALSE,
				'description' => 'Shows all  hat have a price between 30 and 70',
				'output' => TRUE,
				'column_order'=>array('crop_variety_id','planting_stage','expected_yield'),
				'columns' => array(
				'crop_variety_id' => array('label' => 'Crop Variety'),
				'planting_stage' => array('label' => 'Planting Stage'),
				'expected_yield' => array('label' => 'Expected Harvest'),
				),
				'create' => FALSE, 'edit' => FALSE, 'view' => FALSE, 'delete' => FALSE, 'search' => FALSE,
				// $q->getDql() converts the query object into a dql query string which is used by the Renderer.
				'filter_by_dql' => $q->getDql()
			);
		$result = $R->render_as_table($options);

echo doctype();
echo '<link href="'.base_url().'cbeads/css/cbeads_style.css" type="text/css" rel="stylesheet">';  

			if($result['success'] == FALSE){
				echo cbeads_error_message('There was an error rendering the table:' . $result['msg']);
				}
			else{
				echo $result['output_html'];
				}
    }

	
	
}

?>