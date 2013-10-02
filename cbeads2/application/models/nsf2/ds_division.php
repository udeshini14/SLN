<?php

/*
	This file was auto generated on 2013-09-22 at 00:30:42.
	
*/

namespace nsf2;

class ds_division extends \Doctrine_Record_Ex
{

	public function setTableDefinition()
	{
		$this->setTableName('nsf2.ds_division');
		$this->auto_generate_definition();
	}

	public function setUp()
	{
		$this->hasOne('nsf2\district as Districts',
			array(
				'local' => 'district_id',
				'foreign' => 'id'
			)
		);
		$this->hasOne('nsf2\district as Districts',
			array(
				'local' => 'district_id',
				'foreign' => 'id'
			)
		);
		$this->hasMany('nsf2\farm as Farms',
			array(
				'local' => 'id',
				'foreign' => 'ds_division_id'
			)
		);
		$this->hasMany('nsf2\register_user as Register_users',
			array(
				'local' => 'id',
				'foreign' => 'ds_division_id'
			)
		);
		$this->hasMany('nsf2\gn_division as Gn_divisions',
			array(
				'local' => 'id',
				'foreign' => 'ds_division_id'
			)
		);

	}

}

?>