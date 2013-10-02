<?php
namespace cbeads;
/** *************** cbeads/team_role_function.php *****************
 *
 *  This model defines the cbeads team-role to function mapping
 *  table.
 *
 ** Changelog:
 * 
 *  2010/06/08 - Markus
 *  - Created
 *
 ******************************************************************/
class Team_role_function extends \Doctrine_Record_Ex {

    public function setTableDefinition() {
        $this->setTableName('cbeads.team_role_function_map');

        $CI =& get_instance();
        $CI->load->helper('MySqlTable_definitions');
        $defs = mysql_defs_getColumns('cbeads', 'team_role_function_map');
        foreach($defs as $def)
        {
            $this->hasColumn($def['name'], $def['type'], $def['length'], $def['options']);
            //echo '<br>' . $def['name'] . " , " . $def['type'] . " , " . $def['length'] . " , ";
            //print_r($def['options']);
        }
    }
    
    public function setUp()
    {
//         $this->hasOne('cbeads\Function_ as Function', array(
//                         'local' => 'function_id',
//                         'foreign' => 'id'
//                       )
//         );
//         $this->hasOne('cbeads\Team_role as TeamRole', array(
//                         'local' => 'team_role_id',
//                         'foreign' => 'id'
//                       )
//         );
        
    }

}