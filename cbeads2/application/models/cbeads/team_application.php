<?php
namespace cbeads;
/** **************** cbeads/team_application.php ******************
 *
 *  This model defines the cbeads team application mapping table.
 *
 ** Changelog:
 * 
 *  2010/06/08 - Markus
 *  - Created
 *
 ******************************************************************/
class Team_application extends \Doctrine_Record_Ex {

    public function setTableDefinition() {
        $this->setTableName('cbeads.team_application_map');

        $CI =& get_instance();
        $CI->load->helper('MySqlTable_definitions');
        $defs = mysql_defs_getColumns('cbeads', 'team_application_map');
        foreach($defs as $def)
        {
            $this->hasColumn($def['name'], $def['type'], $def['length'], $def['options']);
            //echo '<br>' . $def['name'] . " , " . $def['type'] . " , " . $def['length'] . " , ";
            //print_r($def['options']);
        }
    }
}
