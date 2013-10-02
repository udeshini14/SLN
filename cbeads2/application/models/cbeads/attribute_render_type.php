<?php
/** ************** cbeads/attribute_render_def.php *****************
 *
 *  This model is used for defining attribute render definitions.
 *  This model is still a work in progress.
 *
 ** Changelog:
 *
 *  2010/04/30 - Markus
 *  - Moved to inheriting from the Doctrine_Record_Ex class 
 *    because it provides the select() function which collects
 *    model data as well as other usefull functions that all
 *    models should have.
 *
 *  2010/06/04 - Markus
 *  - Removed the ondelete cascade option from the relationship
 *    as render types and attribute definitions are independent.
 *
 ******************************************************************/
namespace cbeads;

class Attribute_render_def extends \Doctrine_Record_Ex {

    public function setTableDefinition() {
        $this->setTableName('cbeads.attribute_render_def');
//         $this->hasColumn('name', 'string', 255);
//         $this->hasColumn('label', 'string', 255);
//         $this->hasColumn('validation', 'string', 255);
//         $this->hasColumn('input_type', 'string', 255);
//         $this->hasColumn('output_type', 'string', 255);
//         $this->hasColumn('width', 'integer', 4, array('notnull' => false));
//         $this->hasColumn('height', 'integer', 4);
//         $this->hasColumn('additional', 'string');
        
        
        $CI =& get_instance();
        $CI->load->helper('MySqlTable_definitions');
        $defs = mysql_defs_getColumns('cbeads', 'attribute_render_def');
        foreach($defs as $def)
        {
            $this->hasColumn($def['name'], $def['type'], $def['length'], $def['options']);
            //echo '<br>' . $def['name'] . " , " . $def['type'] . " , " . $def['length'] . " , ";
            //print_r($def['options']);
        }
    }
    
    public function setUp()
    {
        parent::setUp();
        $this->hasMany('cbeads\Attribute_definition as attribute_definition', array(
             'local' => 'id',
             'foreign' => 'render_type_id',
             )
        );
    }
    
}