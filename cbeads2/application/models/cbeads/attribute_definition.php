<?php
/** ************** cbeads/attribute_definition.php *****************
 *
 *  This model defines attributes that the system automatically
 *  recognises when it comes to generating tables and selecting
 *  the render type to use for creating forms/tables.
 *  This class is a work in progress.
 *
 ** Changelog:
 *
 *  2010/04/30 - Markus
 *  - Moved to inheriting from the Doctrine_Record_Ex class 
 *    because it provides the select() function which collects
 *    model data as well as other usefull functions that all
 *    models should have.
 *
 ******************************************************************/
namespace cbeads;

class Attribute_definition extends \Doctrine_Record_Ex {

    public function setTableDefinition() {
        $this->setTableName('cbeads.attribute_definition');
//         $this->hasColumn('name', 'string', 255);
//         $this->hasColumn('db_type', 'string', 255);
//         $this->hasColumn('render_type_id', 'integer', 4);
//         $this->hasColumn('comment', 'string', 255);
//         $this->hasColumn('additional', 'string');
        
        $CI =& get_instance();
        $CI->load->helper('MySqlTable_definitions');
        $defs = mysql_defs_getColumns('cbeads', 'attribute_definition');
        foreach($defs as $def)
        {
            $this->hasColumn($def['name'], $def['type'], $def['length'], $def['options']);
            // print '<code>$this->hasColumn(\''.$def['name'].'\',\''.$def['type'].'\',\''.$def['length'].'\','.mysql_defs_varExport($def['options']).');</code><br>';
        }
        //mysql_defs_getColumns('level_a', 'employee');
    }
    
    public function setUp()
    {
        parent::setUp();
        $this->hasOne('cbeads\Attribute_render_def as render_type', array(
             'local' => 'render_type_id',
             'foreign' => 'id')
        );
        
        $this->hasOne('cbeads\Attribute_definition as alias', array(
            'local' => 'alias_for',
            'foreign' => 'id'
        ));
    }

}