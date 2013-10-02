<?php

/**    nsf2/enter_household_details.php
 *
 *  This function is used to initiate the household details
 *
 ******************************************************************/

class Enter_household_details extends CI_Controller 
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
        $result = $renderer->render_as_table(
		array(
				'title' => 'Enter Househod Details',
				'model' =>'nsf2\household',
				'column_order' => array('gn_division_id','id', 'household_no', 'name', 'address'  ),
				'form_options' => array(
					'update' => array(
						'order' => array('gn_division_id','id', 'household_no', 'name', 'address'  ),
						'fields' => array(
							'gn_division_id' => array('label' => 'Grama Niladhari Division ID'),
							'id' => array('label' => 'Id'),
							'household_no' => array('label' => 'Household No'),
							'name' => array('label' => 'Name of Owner'),
							'address' => array('label' => 'Address'),
							
								
						)
					)
				),
				'output' => TRUE
			)
			);
		
        if($result['success'] == FALSE)
        {
            echo cbeads_error_message('There was an error rendering the table:<br>' . $result['msg']);
        }
		else
		{
			echo '<link href="'.base_url().'cbeads/css/cbeads_style.css" type="text/css" rel="stylesheet">';
			echo $result['output_html'];
		}
    }

}

?>