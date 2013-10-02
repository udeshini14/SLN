<?php

/**    nsf2/register_users.php
 *
 *  Register new users to the system
 *
 ******************************************************************/

class Register_users extends CI_Controller 
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
	{
		$obj = new nsf2\register_user();
		$model_data = $obj->select();
        $options = array(
		'title' => 'Enter Crop Details',
   		'type' => 'create',
		'order' => array('first_name','last_name','email','designation','ds_division','gn_division','supervisor'),
		'fields' => array(
		'first_name' => array('label' => 'First Name'),
							'last_name' => array('label' => 'Last Name','input_type'=> 'textbox'),		
							'email'=>array('label' => 'Email'),
							'designation'=>array('label' => 'Designation'),
							'gn_division_id' => array('label' => 'GN Division'),
							'ds_division_id' => array('label' => 'DS Division'),
							'supervisor' => array('label' => 'Supervisor'),
							
				
		'output' => TRUE,
				),
		
    
	
				

	);
		$renderer = new Renderer();
        
		$result = $renderer->render_as_form($options, $model_data);
        if($result['success'] === FALSE)
        {
            echo cbeads_error_message('There was an error rendering the menu:<br>' . $result['msg']);
        }
		
		
    }
	

}
      
		
			
       // if($result['success'] == FALSE)
        //{
        //    echo cbeads_error_message('There was an error rendering the menu:<br>' . $result['msg']);
        // }
		//else
		//{
			//echo '<link href="'.base_url().'cbeads/css/cbeads_style.css" type="text/css" rel="stylesheet">';
			//echo $result['output_html'];
		//}
    
}

?>