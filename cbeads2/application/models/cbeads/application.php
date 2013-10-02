<?php
namespace cbeads;
/** ******************* cbeads/application.php *********************
 *
 *  This model defines the cbeads application table.
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
class Application extends \Doctrine_Record_Ex {

    public function setTableDefinition() {
        $this->setTableName('cbeads.application');

        $CI =& get_instance();
        $CI->load->helper('MySqlTable_definitions');
        $defs = mysql_defs_getColumns('cbeads', 'application');
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
                            'local' => 'application_id',
                            'foreign' => 'team_id',
                            'refClass' => 'cbeads\Team_application'
                       )
        );
        $this->hasMany('cbeads\Function_ as Functions', 
                       array(
                            'local' => 'id',
                            'foreign' => 'application_id'
                       )
        );
    }

    
    public function stringify_self()
    {
        return ucfirst($this->name);
    }

}