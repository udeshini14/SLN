<?php
namespace cbeads;
/** ******************* cbeads/team_role.php *********************
 *
 *  This model defines the cbeads team role mapping table.
 *
 ** Changelog:
 * 
 *  2010/06/08 - Markus
 *  - Created
 *
 *  2010/06/17 - Markus
 *  - Added custom stringify function.
 *
 ******************************************************************/
class Team_role extends \Doctrine_Record_Ex {

    public function setTableDefinition() {
        $this->setTableName('cbeads.team_role_map');

        $CI =& get_instance();
        $CI->load->helper('MySqlTable_definitions');
        $defs = mysql_defs_getColumns('cbeads', 'team_role_map');
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
        $this->hasMany('cbeads\User as Users', 
                        array(
                            'local' => 'team_role_id',
                            'foreign' => 'user_id',
                            'refClass' => 'cbeads\User_team_role'
                        )
            );
        $this->hasMany('cbeads\Function_ as Functions', 
                        array(
                            'local' => 'team_role_id',
                            'foreign' => 'function_id',
                            'refClass' => 'cbeads\Team_role_function'
                        )
            );
        $this->hasOne('cbeads\Team as Team', 
                        array(
                            'local' => 'team_id',
                            'foreign' => 'id'
                        )
            );
        $this->hasOne('cbeads\Role as Role', 
                        array(
                            'local' => 'role_id',
                            'foreign' => 'id'
                        )
            );
    }
    
    public function stringify_self()
    {
        $role = $this->Role->stringify_self();
        $team = $this->Team->stringify_self();
        return $team . '-' . $role;
    }
    
}
