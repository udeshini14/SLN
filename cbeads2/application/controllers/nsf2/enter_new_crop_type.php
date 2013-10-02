<?php

/**    nsf2/enter_new_crop_type.php
 *
 *  This function is used to enter new crop data for the database
 *
 ******************************************************************/

class Enter_new_crop_type extends CI_Controller 
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
        // Some browsers require a doctype to be set to have css rendered properly. It is good to include this.
        echo doctype();
		
		// Just do a test to ensure the namespace exists and there are models defined for it (only done because this is auto generated code).
		if(count(cbeads_get_loaded_model_names_for_db('nsf2')) == 0)
		{
			echo "This is a auto generated controller.";
			return;
		}
		
        // The styling to use for the cbeads menu/table/forms
        echo '<link href="'.base_url().'cbeads/css/cbeads_style.css" type="text/css" rel="stylesheet">'; 
		
        // The menu options. The namespace indicates the database name to use for rendering the menu and is the
        // only option value required.
        $options = array(
            'namespace' => 'nsf2'
        );
        // Create a instance of the Renderer class.
        $renderer = new Renderer();
        // This will render a menu where each tab corresponds to a different model. The 'application/models/nsf2' folder
        // must contain at least one model file for this to work.
        $result = $renderer->render_as_menu($options);
        // It is good to check the success of the call.
        if($result['success'] == FALSE)
        {
            echo cbeads_error_message('There was an error rendering the menu:<br>' . $result['msg']);
        }
    }

}

?>