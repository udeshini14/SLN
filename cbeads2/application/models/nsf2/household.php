<?php

/*
	This file was auto generated on 2013-09-09 at 23:57:38.
	
*/

namespace nsf2;

class household extends \Doctrine_Record_Ex
{

	public function setTableDefinition()
	{
		$this->setTableName('nsf2.household');
		$this->auto_generate_definition();
	}

	public function setUp()
	{
		$this->hasMany('nsf2\farm as Farms',
			array(
				'local' => 'id',
				'foreign' => 'household_id'
			)
		);
		$this->hasMany('nsf2\farm as Farms',
			array(
				'local' => 'id',
				'foreign' => 'household_id'
			)
		);
		$this->hasOne('nsf2\gn_division as Gn_divisions',
			array(
				'local' => 'gn_division_id',
				'foreign' => 'id'
			)
		);

	}

}

?>