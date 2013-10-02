<?php
namespace cbeads;
/** ********************* cbeads/function.php **********************
 *
 *  This model defines the cbeads function table.
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
class Function_ extends \Doctrine_Record_Ex {

    public function setTableDefinition() {
        $this->setTableName('cbeads.function');

        $CI =& get_instance();
        $CI->load->helper('MySqlTable_definitions');
        $defs = mysql_defs_getColumns('cbeads', 'function');
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
        $this->hasMany('cbeads\Team_role as TeamRoles', 
                       array(
                            'local' => 'function_id',
                            'foreign' => 'team_role_id',
                            'refClass' => 'cbeads\Team_role_function'
                       )
        );
        $this->hasOne('cbeads\Application as Application', 
                       array(
                            'local' => 'application_id',
                            'foreign' => 'id'
                       )
        );
    }
    
    public function stringify_self()
    {
        return ucfirst($this->name);
    }

}