<?php

/*
	This file was auto generated on 2013-09-21 at 08:38:14.
	
*/

namespace nsf2;

class register_user extends \Doctrine_Record_Ex
{

	public function setTableDefinition()
	{
		$this->setTableName('nsf2.register_user');
		$this->auto_generate_definition();
	}

	public function setUp()
	{
		$this->hasOne('nsf2\ds_division as Ds_division',
			array(
				'local' => 'ds_division_id',
				'foreign' => 'id'
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