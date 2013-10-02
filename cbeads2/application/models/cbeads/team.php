<?php
namespace cbeads;
/** ********************** cbeads/team.php ************************
 *
 *  This model defines the cbeads team table.
 *
 ** Changelog:
 * 
 *  2010/06/08 - Markus
 *  - Created
 *
 ******************************************************************/
class Team extends \Doctrine_Record_Ex {

    public function setTableDefinition() {
        $this->setTableName('cbeads.team');

        $CI =& get_instance();
        $CI->load->helper('MySqlTable_definitions');
        $defs = mysql_defs_getColumns('cbeads', 'team');
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
        $this->hasMany('cbeads\Application as Applications', 
                       array(
                            'local' => 'team_id',
                            'foreign' => 'application_id',
                            'refClass' => 'cbeads\Team_application'
                       )
        );
        
        $this->hasMany('cbeads\Role as Roles', 
                       array(
                            'local' => 'team_id',
                            'foreign' => 'role_id',
                            'refClass' => 'cbeads\Team_role'
                       )
        );
    }

}