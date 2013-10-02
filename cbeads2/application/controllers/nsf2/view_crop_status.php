<?php

/**    nsf2/update_crop_status.php
 *
 *  This function is used to update crop status to the database
 *
 ******************************************************************/

class View_crop_status extends CI_Controller 
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

    // By default, the index() function is called unless the url specifiFes a different function to use.
    function index()
    {
      
		$renderer = new Renderer();
        $result = $renderer->render_as_table(
		array(
				'title' => 'Crop Status',
				'edit' => FALSE,
				'create' => FALSE,
				'delete' => FALSE,
				'model' =>'nsf2\crop_status',
				'column_order' => array('date','farm_id','crop_variety_id','planting_stage','crop_condition','damage_reason','extent_of_damage', 'amount_loss'),
				 'columns' => array(

                'date' => array('label' => 'Date'),
							'farm_id' => array('label' => 'Farm'),		
							'crop_variety_id'=>array('label' => 'Crop Variety'),
							'planting_stage'=>array('label' => 'Planting Stage'),
							'crop_condition'=>array('label' => 'Crop Condition'),
							'damage_reason' => array('label' => 'Damage Reason'),
							'extent_of_damage' => array('label' => 'Extent of Damage(ac/ha)'),
							//'expected_harvest' => array('label' => 'Expected Harvest'),
							'amount_loss' => array('label' => 'Amount Loss '),
							
				 
            ),
				'form_options' => array(
				
					'create' => array(
					
						'order' => array('date','farm_id','crop_variety_id','planting_stage','crop_condition','damage_reason','extent_of_damage', 'amount_loss'),
						'fields' => array(
							'date' => array('label' => 'Date'),
							'farm_id' => array('label' => 'Farm'),		
							'crop_variety_id'=>array('label' => 'Crop Variety'),
							'planting_stage'=>array('label' => 'Planting Stage'),
							'crop_condition'=>array('label' => 'Crop Condition'),
							'damage_reason' => array('label' => 'Damage Reason'),
							'extent_of_damage' => array('label' => 'Extent of Damage(ac/ha)'),
							//'expected_harvest' => array('label' => 'Expected Harvest'),
							'amount_loss' => array('label' => 'Amount Loss '),
							
								
						)
					)
				),
				'output' => TRUE
			)
			);
        if($result['success'] == FALSE)
        {
            echo cbeads_error_message('There was an error rendering the menu:<br>' . $result['msg']);
        }
		else
		{
			echo '<link href="'.base_url().'cbeads/css/cbeads_style.css" type="text/css" rel="stylesheet">';
			echo $result['output_html'];
		}
    }
    

}

?>