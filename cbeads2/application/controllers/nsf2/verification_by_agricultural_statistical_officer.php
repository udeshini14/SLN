<?php

/**    nsf2/enter_crop_details.php
 *
 *  This function allows to enter existing crop details of a variety which is per-defined by the Agriculture statistical officer.
 *
 ******************************************************************/

class Verification_by_agricultural_statistical_officer extends CI_Controller 
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
	
       $renderer = new Renderer();
        $options = array(
            'namespace' => 'nsf2',
            'item_order' => array('crop', 'crop_status' , 'household', 'farm'),
            'items' => array(
                'crop' => array('label' => 'Verify Crop Details', 'content' => $this-> _verify_crop_details()),
                'crop_status' => array('label' => 'Verify Crop Status', 'content' => $this->_verify_crop_status()),
				'household' => array('label' => 'Verify Household Details', 'content' => $this->_verify_household_details()),
				'farm' => array('label' => 'Verify Farm Details', 'content' => $this->_verify_farm_details()),
            ),
            'output' => TRUE
        );
        $result = $renderer->render_as_menu($options);
        if($result['success'] === FALSE)
        {
            echo error_message('Error while rendering menu:<br>'.$result['msg']);
        }
        else
        {
			echo '<link href="'.base_url().'cbeads/css/cbeads_style.css" type="text/css" rel="stylesheet">';
			echo $result['output_html'];
        }
    }

	function _verify_crop_details()
	{
 return array(
            'title' => 'Verify Crop Details-Agricultural Statistical Officer',
			
            'column_order' => array( 'crop_identification_no', 'crop_scientific_name', 'crop_name', 'multiple_harvest', 'verify_status'),
            'columns' => array(
				//'id' => array('label' => 'crop id'),
                'crop_identification_no' => array('label' => 'Crop Code'),
               'crop_scientific_name' => array('label' => 'Scientific Name'),
                'crop_name' => array('label' => 'Crop Name'),
				 'multiple_harvest' => array('label' => 'Months of Harvest'),
				 'verify_status' => array( 'label' => 'Verified by'  ),
            ),
			'form_options' => array(
				
					'create' => array(
					
						'order' => array('crop_identification_no', 'crop_scientific_name', 'crop_name', 'multiple_harvest', 'verify_status'),
						'fields' => array(
							'crop_identification_no' => array('label' => 'Crop Code'),
               'crop_scientific_name' => array('label' => 'Scientific Name'),
                'crop_name' => array('label' => 'Crop Name'),
				 'multiple_harvest' => array('label' => 'Months of Harvest'),
				 'verify_status' => array( 'label' => 'Verified by','input_type'=>'select','items'=>array('Not verified','ARPA Verified','AI Verified')),
			
   
					)
				)
				)
				
				);
				
				
			
	}
	
	function _verify_crop_status()
	{
	return array (
	
	'title' => 'Verify Crop Status- Agricultural Statistical Officer',
			
            'column_order' => array('date','farm_id','crop_variety_id','planting_stage','crop_condition','damage_reason','extent_of_damage','expected_harvest', 'amount_loss', 'verify_status'),
            'columns' => array(
				//'id' => array('label' => 'crop id'),
               'date' => array('label' => 'Date'),
							'farm_id' => array('label' => 'Farm'),		
							'crop_variety_id'=>array('label' => 'Crop Variety'),
							'planting_stage'=>array('label' => 'Planting Stage'),
							'crop_condition'=>array('label' => 'Crop Condition'),
							'damage_reason' => array('label' => 'Damage Reason'),
							'extent_of_damage' => array('label' => 'Extent of Damage(ac/ha)'),
							'expected_harvest' => array('label' => 'Expected Harvest'),
							'amount_loss' => array('label' => 'Amount Loss '),
							 'verify_status' => array( 'label' => 'Verified by'  ),
							 ),
							 
							 'form_options' => array(
				
					'create' => array(
					
						'order' => array('date','farm_id','crop_variety_id','planting_stage','crop_condition','damage_reason','extent_of_damage','expected_harvest', 'amount_loss', 'verify_status'),
						'fields' => array(
						 'date' => array('label' => 'Date'),
							'farm_id' => array('label' => 'Farm'),		
							'crop_variety_id'=>array('label' => 'Crop Variety'),
							'planting_stage'=>array('label' => 'Planting Stage'),
							'crop_condition'=>array('label' => 'Crop Condition'),
							'damage_reason' => array('label' => 'Damage Reason'),
							'extent_of_damage' => array('label' => 'Extent of Damage(ac/ha)'),
							'expected_harvest' => array('label' => 'Expected Harvest'),
							'amount_loss' => array('label' => 'Amount Loss '),
							 'verify_status' => array( 'label' => 'Verified by','input_type'=>'select','items'=>array('Not verified','ARPA Verified','AI Verified')),
			
   
					)
				)
				)
							 
            )
           ;
		 
	}
	function _verify_household_details()
	{
	 return array (
				'title' => 'Verify Household Details- Agricultural Statistical Officer',
			
            'column_order' => array('household_no', 'name', 'address', 'gn_division_id' ),
            'columns' => array(
				//'id' => array('label' => 'crop id'),
             // 'id' => array('label' => 'Id'),
							'household_no' => array('label' => 'Household No'),
							'name' => array('label' => 'Name of Owner'),
							'address' => array('label' => 'Address'),
							'gn_division_id' => array('label' => 'Grama Niladhari Division ID'),
							),
							
								'form_options' => array(
				
					'create' => array(
					
						'order' => array('household_no', 'name', 'address', 'gn_division_id', 'verify_status'  ),
						'fields' => array(
							'household_no' => array('label' => 'Household No'),
							'name' => array('label' => 'Name of Owner'),
							'address' => array('label' => 'Address'),
							'gn_division_id' => array('label' => 'Grama Niladhari Division ID'),
							'verify_status' => array( 'label' => 'Verified by','input_type'=>'select','items'=>array('Not verified','ARPA Verified','AI Verified')),
           ) )));
            
	
	}
   function _verify_farm_details()
		{
	return array (
	
			 'title' => 'Verify Farm Details- Agricultural Statistical Officer',
			
            'column_order' => array( 'household_id', 'farm_name', 'farm_no','extent_of_land','gn_division_id', 'ds_division_id', ),
            'columns' => array(
				'household_id' => array('label' => 'Owner Name'),
							'farm_name' => array('label' => 'Farm Name'),
							'farm_no' => array('label' => 'Farm No'),
							'extent_of_land' => array('label' => 'Extent_of_Land_(ac)'),	
							'gn_division_id' => array('label' => 'GN Division'),
							'ds_division_id' => array('label' => 'DS Division'),
							),
							
								'form_options' => array(
				
					'create' => array(
					
						'order' => array('household_id', 'farm_name', 'farm_no','extent_of_land','gn_division_id', 'ds_division_id', 'verify_status' ),
						'fields' => array(
						'household_id' =>array('label' => 'Owner Name'),
							'farm_name' => array('label' => 'Farm Name'),
							'farm_no' => array('label' => 'Farm No'),
							'extent_of_land' => array('label' => 'Extent_of_Land_(ac)'),	
							'gn_division_id' => array('label' => 'GN Division'),
							'ds_division_id' => array('label' => 'DS Division'),
							'verify_status' => array( 'label' => 'Verified by','input_type'=>'select','items'=>array('Not verified','ARPA Verified','AI Verified')),
							)
							
            ))
			)
			;

		}
	
}


?>