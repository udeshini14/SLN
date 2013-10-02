<?php
/** ****************** cbeads/validation_type.php ******************
 *
 *  Model for working with validation types.
 *
 ** Changelog:
 *
 *  2010/04/16 - Markus
 *  - Created file.
 *
 *  2010/04/30 - Markus
 *  - Moved to inheriting from the Doctrine_Record_Ex class 
 *    because it provides the select() function which collects
 *    model data as well as other usefull functions that all
 *    models should have.
 *
 ******************************************************************/
namespace cbeads;

class Validation_type extends \Doctrine_Record_Ex {

    public function setTableDefinition() {
        $this->setTableName('cbeads.validation_type');
        
        $CI =& get_instance();
        $CI->load->helper('MySqlTable_definitions');
        $defs = mysql_defs_getColumns('cbeads', 'validation_type');
        foreach($defs as $def)
        {
            $this->hasColumn($def['name'], $def['type'], $def['length'], $def['options']);
        }
    }

}