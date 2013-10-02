<?php
namespace cbeads;
/** ********************** cbeads/role.php ************************
 *
 *  This model defines the cbeads role table.
 *
 ** Changelog:
 * 
 *  2010/06/08 - Markus
 *  - Created
 *
 ******************************************************************/
class Role extends \Doctrine_Record_Ex {

    public function setTableDefinition() {
        $this->setTableName('cbeads.role');

        $CI =& get_instance();
        $CI->load->helper('MySqlTable_definitions');
        $defs = mysql_defs_getColumns('cbeads', 'role');
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
        $this->hasMany('cbeads\Team as Teams', 
                       array(
                            'local' => 'role_id',
                            'foreign' => 'team_id',
                            'refClass' => 'cbeads\Team_role'
                       )
        );
    }

}