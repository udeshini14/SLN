<?php

/**    nsf2/enter_crop_details.php
 *
 *  This function allows to enter existing crop details of a variety which is per-defined by the Agriculture statistical officer.
 *
 ******************************************************************/

class Enter_farm_details extends CI_Controller 
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
            'item_order' => array('farm', 'variety_grown_in_farm'),
            'items' => array(
                'farm' => array('label' => 'Farm', 'content' => $this->_farm_table_options()),
                'variety_grown_in_farm' => array('label' => 'Variety Grown in Farm', 'content' => $this->_variety_grown_in_farm_table_options())
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

	function _farm_table_options()
    {
        return array(
            'title' => 'Enter Farm Details',
				//'model' =>'nsf2\farm',
				'column_order' => array('ds_division_id','gn_division_id', 'household_id', 'farm_name', 'farm_no','extent_of_land'),
				'columns' => array(
							'ds_division_id' => array('label' => 'DS Division'),
							'gn_division_id' => array('label' => 'GN Division'),
							'household_id' => array('label' => 'Owner Name'),
							'farm_name' => array('label' => 'Farm Name','output_type'=>'text'),
							'farm_no' => array('label' => 'Farm No'),
							'extent_of_land' => array('label' => 'Extent_of_Land_(ac)'),	
							
				),
				'form_options' => array(
					'create' => array(
						'order' => array('ds_division_id','gn_division_id', 'household_id', 'farm_name', 'farm_no','extent_of_land'),
						'fields' => array(
						'ds_division_id' => array('label' => 'DS Division','onchange' => 'changed_name(this)'),
						'gn_division_id' => array('label' => 'GN Division','id'=>'gn'),
						'household_id' => array('label' => 'Owner Name'),
							'farm_name' => array('label' => 'Farm Name','input_type'=>'textbox','validate' => '/^[a-zA-Z ]+$/'),
							'farm_no' => array('label' => 'Farm No','validate' => '/^[0-9]+$/'),
							'extent_of_land' => array('label' => 'Extent of Land(ac)','validate' => '/^[0-9]+$/'),	
							
								
						),
						// 'validation' => array($this, 'validate_create_uni'),
						'javascript' => get_js(),
						'pre_init_javascript' => '$(“#gn”).change(changed_address)',




					)
				),
				
				
        );
    }		
function get_js()
{
    return <<<JS
    function changed_name(obj)
    {
        alert('The name is now: “' + obj.value + '”');
    }
    function changed_address(obj)
    {
        alert('The address is now: “' + obj.value + '”');
    }
JS;
}
/*	function validate_create_uni($data)
{
    $errors = array();
    if(strlen($data['farm_name']) < 10) return array('name' => 'Name must be at least 10 characters long');
    $uni = new nsf2\farm();
    $uni->farm_name = $data['farm_name'];
    $uni->farm_no = $data['farm_no'];
    $uni->extent_of_land = $date['extent_of_land'];
	//$uni->extent_of_land = $date['extent_of_land'];
    if(!$obj->isValid())
    {
        $errors_ = $obj->getErrorStack();
        foreach($errors_ as $fieldName => $errorCodes) 
            $errors[$fieldName] = $errorCodes;
    }
    return $errors;
}*/

function _variety_grown_in_farm_table_options()
    {
        return array(
            'title' => 'Enter Varieties Grown in Farm',
			//'delete'=>FALSE,
            'column_order' => array( 'farm_id', 'crop_variety_id','extend_variety_grown','planting_date','expected_harvest_date','expected_harvest'),
            'columns' => array(

                'farm_id' => array('label' => 'Farm'),
                'crop_variety_id' => array('label' => 'Crop Variety Name'),
				'extend_variety_grown'=>array('label' => 'Extent Grown'),
                'planting_date'=> array('label' => 'Planting Date'),
				'expected_harvest_date' => array('label' => 'Harvest Date'),
				'expected_harvest' => array('label' => 'Expected Harvest'),
				 
            ),
            'form_options' => array(
                'create' => array(
                    'order' => array( 'farm_id', 'crop_variety_id','extend_variety_grown','planting_date','expected_harvest_date','expected_harvest'),
                    'fields' => array(
				'farm_id' => array('label' => 'Farm'),
                'crop_variety_id' => array('label' => 'Crop Variety Name'),
				'extend_variety_grown'=>array('label' => 'Extent Grown'),
                'planting_date'=> array('label' => 'Planting Date'),
				'expected_harvest_date' => array('label' => 'Expected Harvest Date'),
				'expected_harvest' => array('label' => 'Expected Harvest'),
                    )                    
                )
            )
        );
    }
	
}

?>	