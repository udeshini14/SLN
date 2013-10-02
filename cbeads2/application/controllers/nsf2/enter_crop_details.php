<?php

/**    nsf2/enter_crop_details.php
 *
 *  This function allows to enter existing crop details of a variety which is per-defined by the Agriculture statistical officer.
 *
 ******************************************************************/

class Enter_crop_details extends CI_Controller 
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
            'item_order' => array('crop', 'crop_variety'),
            'items' => array(
                'crop' => array('label' => 'Crop', 'content' => $this->_crop_table_options()),
                'crop_variety' => array('label' => 'Crop Variety', 'content' => $this->_crop_variety_table_options())
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

	function _crop_table_options()
    {
        return array(
            'title' => 'Enter Crop Details',
			
            'column_order' => array( 'crop_identification_no', 'crop_scientific_name', 'crop_name', 'multiple_harvest'),
            'columns' => array(
				//'id' => array('label' => 'crop id'),
                'crop_identification_no' => array('label' => 'Crop Code'),
               'crop_scientific_name' => array('label' => 'Scientific Name'),
                'crop_name' => array('label' => 'Crop Name'),
				 'multiple_harvest' => array('label' => 'Months of Harvest'),
            ),
            'form_options' => array(
                'create' => array(
                    'order' => array('crop_identification_no', 'crop_scientific_name', 'crop_name', 'multiple_harvest'),
                    'fields' => array(
				'crop_identification_no' => array('label' => 'Crop Code'),
                'crop_scientific_name' => array('label' => 'Scientific Name'),
                'crop_name' => array('label' => 'Crop Name'),
				 'multiple_harvest' => array('label' => 'Months of Multiple Harvest'),
                    )                    
                )
            )
        );
    }
	
	function _crop_variety_table_options()
    {
        return array(
            'title' => 'Enter Crop Variety Details',
			//'delete'=>FALSE,
            'column_order' => array( 'crop_id', 'crop_variety_name','crop_variety_identification_no','crop_international_identical'),
            'columns' => array(

                'crop_id' => array('label' => 'Crop Variety Code'),
                'crop_variety_name' => array('label' => 'Crop Variety Name'),
				'crop_variety_identification_no'=>array('label' => 'Crop Variety Identification Code'),
                'crop_variety_international_identical'=> array('label' => 'Crop Variety International Identical'),
				 
            ),
            'form_options' => array(
                'create' => array(
                    'order' => array( 'crop_id', 'crop_variety_name','crop_variety_identification_no','crop_international_identical'),
					//array('crop_id', 'crop_variety_name','crop_variety_International_identical'),
                    'fields' => array(
				          //'crop_id' => array('label' => 'crop code'),
                //'crop_variety_name' => array('label' => 'Crop Variety Name'),
                //'crop_variety_International_identical'=> array('label' => 'Crop Variety International Identical'),
				'crop_id' => array('label' => 'Crop Id'),
                'crop_variety_name' => array('label' => 'Crop Variety Name'),
				'crop_variety_identification_no'=>array('label' => 'Crop Variety Identification Code'),
                'crop_variety_international_identical'=> array('label' => 'Crop Variety International Identical'),
                    )                    
                )
            )
        );
    }
	
}

?>