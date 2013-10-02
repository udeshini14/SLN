<?php
namespace cbeads;
/** ************************* cbeads/function_access_profile.php ******************************
 *
 *  Represents access and profile information on functions.
 *
 ** Changelog:
 *  2011/10/12 - Markus
 *  - Not using auto model generator because it will add a 'unique' constraint to each primary
 *    key field which makes it impossible to add new records if any of the primary key columns
 *    has the same value as another.
 *
 *********************************************************************************************/
class Function_access_profile extends \Doctrine_Record_Ex {

    public function setTableDefinition() 
    {
        $this->setTableName('cbeads.function_access_profile');
        //$this->auto_generate_definition();
		
		$CI =& get_instance();
        $CI->load->helper('MySqlTable_definitions');
        
        $defs = mysql_defs_getColumns('cbeads', 'function_access_profile');
        foreach($defs as $def)
        {
            $this->hasColumn($def['name'], $def['type'], $def['length'], $def['options']);
        }

    }

    public function setUp() 
    {

    }
   
}
