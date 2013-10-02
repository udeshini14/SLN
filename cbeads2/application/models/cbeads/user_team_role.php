<?php
namespace cbeads;
/** **************** cbeads/user_team_role.php ******************
 *
 *  This model defines the cbeads user to team-role mapping table.
 *
 ** Changelog:
 * 
 *  2010/06/08 - Markus
 *  - Created
 *
 ******************************************************************/
class User_team_role extends \Doctrine_Record_Ex {

    public function setTableDefinition() {
        $this->setTableName('cbeads.user_team_role_map');

        $CI =& get_instance();
        $CI->load->helper('MySqlTable_definitions');
        $defs = mysql_defs_getColumns('cbeads', 'user_team_role_map');
        foreach($defs as $def)
        {
            $this->hasColumn($def['name'], $def['type'], $def['length'], $def['options']);
            //echo '<br>' . $def['name'] . " , " . $def['type'] . " , " . $def['length'] . " , ";
            //print_r($def['options']);
        }
    }
}
