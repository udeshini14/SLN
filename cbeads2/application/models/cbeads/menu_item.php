<?php
namespace cbeads;
/** ******************************* cbeads/menu_item.php *******************************
 *
 *  This model is for the cbeads/menu_item table. The menu_item table stores the order
 *  in which items (functions or custom links) will appear in the associated parent 
 *  group. Each item belongs to a menu group.
 *
 ** Changelog:
 *  2012/03/22 - Markus
 *  - created model
 *
 ***************************************************************************************/
class Menu_item extends \Doctrine_Record_Ex {

    public function setTableDefinition()
    {
        $this->setTableName('cbeads.menu_item');
        $this->auto_generate_definition();
    }

    public function setUp()
    {
        $this->hasOne('cbeads\Menu_group as Group', array(
                        'local' => 'menu_group_id',
                        'foreign' => 'id'
                     )
        );
        
        $this->hasOne('cbeads\Function_ as Function', array(
                        'local' => 'function_id',
                        'foreign' => 'id'
                     )
        );

    }

}

?>