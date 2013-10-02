<?php
namespace cbeads;
/** ******************************* cbeads/menu_group.php *******************************
 *
 *  This model is for the cbeads::menu_group table. The menu_group table stores the 
 *  order of the groups (applications and custom groups) as they should appear in the 
 *  menu. Each group can have many menu_items.
 *
 ** Changelog:
 *  2012/03/21 - Markus
 *  - created model
 *
 ***************************************************************************************/
class Menu_group extends \Doctrine_Record_Ex {

    public function setTableDefinition()
    {
        $this->setTableName('cbeads.menu_group');
        $this->auto_generate_definition();
    }

    public function setUp()
    {
        $this->hasOne('cbeads\Team as Team', array(
                        'local' => 'team_id',
                        'foreign' => 'id'
                     )
        );
        $this->hasOne('cbeads\Role as Role', array(
                        'local' => 'role_id',
                        'foreign' => 'id'
                     )
        );
        $this->hasOne('cbeads\Application as Application', array(
                        'local' => 'application_id',
                        'foreign' => 'id'
                     )
        );
        $this->hasMany('cbeads\Menu_item as Items', array(
                        'local' => 'id',
                        'foreign' => 'menu_group_id'
                      )
        );

    }

}

?>