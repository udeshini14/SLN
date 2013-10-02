<?php

/**    sboml/editable_models.php
 *
 *  List of editable databases/ namespaces
 *
 ** Changelog:
 *
 *  2011/03/02 - Markus
 *  - Moved to using the Renderer class.
 *
 ******************************************************************/

class Editable_models extends CI_Controller 
{

    function __construct()
    {
        parent::__construct();
        $this->load->helper(array('url'));
        include_once(APPPATH . 'libraries/Renderer.php');
    }

    function index()
    {
		$R = new Renderer();
        $result = $R->render_as_table(array(
			'model' =>'sboml\editable_model',
			'description' => 'This list indicates what databases can be edited using the SBOML tool.',
			'output' => TRUE
		));

		if($result['success'] == FALSE)
		{
			error_message("There was an error while rendering the table:<br>" . $result["msg"]);
		}
		else
		{
			echo '<link href="'.base_url().'cbeads/css/cbeads_style.css" type="text/css" rel="stylesheet">';
			echo $result['output_html'];
		}
    }

}

?>