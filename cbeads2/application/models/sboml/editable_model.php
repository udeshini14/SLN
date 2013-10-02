<?php
namespace sboml;

class Editable_model extends \Doctrine_Record_Ex {

	public function setTableDefinition() {
		$this->setTableName('sboml.editable_model');

		$CI =& get_instance();
		$CI->load->helper('MySqlTable_definitions');
		$defs = mysql_defs_getColumns('sboml', 'editable_model');
		foreach($defs as $def)
		{
			$this->hasColumn($def['name'], $def['type'], $def['length'], $def['options']);
		}
	}
}
