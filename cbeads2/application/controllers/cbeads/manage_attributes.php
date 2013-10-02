<?php
/** ********************************* cbeads/manage_attribtues.php *************************************
 *
 *  This controller is used for managing attribute definitions and
 *  render types that those attributes can use.
 *
 *
 ** Changelog:
 *
 *  2010/05/01 - Markus
 *  - Some changes to the input and output type lists.
 *
 *  2010/07/14 - Markus
 *  - Fixed some problems such as the lists of values for data_type,
 *    input_types and output_types not being specified in the form
 *    options.
 *  
 *  2010/07/19 - Markus
 *  - Added line to print out cbeads_style.css
 *
 *  2010/08/04 - Markus
 *  - Deleted old functions no longer needed.
 *
 *  2010/08/05 - Markus
 *  - Added special data types FILE and PFILE. These can be thought
 *    of as meta-datatype. FILE indicates that the field is used to
 *    store the file name of a file on the server and PFILE is for
 *    files that are stored in the database using a Blob data type.
 *    PFILE is intended to be used for files that are private (ie 
 *    shouldn't be accessible via the web folder on the server).
 *  - The datatypes list now has an empty value so that no datatype
 *    can be selected. However this should only be done for attributes
 *    that are aliases.
 *
 *  2010/11/23 - Markus
 *  - Moved to using Renderer class instead of renderer help
 *
 *  2011/03/02 - Markus
 *  - Set the output flag to true when rendering the UI to avoid header already printed warnings.
 *
 *  2011/08/18 - Markus
 *  - Added yesno and truefalse to the output types list supported by the Renderer. Should this not
 *    be stored in the Renderer class and then retrieved?
 *
 *  2011/09/21 - Markus
 *  - Now getting the list of input and output controls straight from the Renderer instance. This way
 *    the lists will always show what controls are available for the current Renderer class version.
 *  - Removed variables no longer needed.
 *
 ******************************************************************************************************/


class Manage_attributes extends CI_Controller 
{

    // List of datatypes available.
    private $_data_types = array(
        '', 'TINYINT', 'SMALLINT', 'MEDIUMINT', 'INT', 'BIGINT', 
        'FLOAT', 'DOUBLE', 'DECIMAL', 'DATE', 'DATETIME', 
        'TIMESTAMP', 'TIME', 'YEAR', 'CHAR', 'VARCHAR', 
        'TINYBLOB', 'TINYTEXT', 'BLOB', 'TEXT','MEDIUMBLOB',
        'MEDIUMTEXT','LONGBLOB','LONGTEXT', 'ENUM', 'SET', 
        'FILE', 'PFILE'
    );
    
    // Constructor. Loads things that are used by many functions.
    function __construct()
    {
        parent::__construct();
        $this->load->helper(array('url'));
        include_once(APPPATH . 'libraries/Renderer.php');
    }
    
    // Default function.
    function index()
    {
		$R = new Renderer();
        $options = array(
            'namespace' => 'cbeads',
            'item_order' => array('attribute_definition', 'attribute_render_def', 'validation_type'),
            'items' => array(
                'attribute_definition' => array(
                    'label' => 'Attribute Definitions',
                    'content' => array(
                        'title' => 'Manage Attribute Definitions',
                        'form_options' => array(
                            'create' => array(
                                'fields' => array(
                                    'db_type' => array('input_type' => 'select', 'items' => $this->_data_types)
                                )
                            ),
                            'edit' => array(
                                'fields' => array(
                                    'db_type' => array('input_type' => 'select', 'items' => $this->_data_types)
                                )
                            )
                        )
                    )
                ),
                'attribute_render_def' => array(
                    'label' => 'Render Definitions',
                    'content' => array(
                        'title' => 'Manage Render Definitions',
                        'form_options' => array(
							'default' => array(
								'fields' => array(
                                    'input_type' => array('input_type' => 'select', 'items' => array_keys($R->_input_controls)),
                                    'output_type' => array('input_type' => 'select', 'items' => array_keys($R->_output_controls))
                                )
							)
                        )
                    ),
                ),
                'validation_type' => array(
                    'label' => 'Validation Types',
                    'content' => array(
                        'title' => 'Manage Validation Types'
                    )
                )
            ),
            'output' => TRUE
        );
        
        $result = $R->render_as_menu($options);
        if($result['success'] === FALSE)
        {
            echo cbeads_error_message('Error while rendering menu for attribute management:<br>'.$result['msg']);
        }
        else
        {
            echo '<link href="'.base_url().'cbeads/css/cbeads_style.css" type="text/css" rel="stylesheet">';
            echo $result['output_html'];
        }
        
    }
    
}