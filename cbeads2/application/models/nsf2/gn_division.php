<?php

/*
	This file was auto generated on 2013-09-22 at 00:30:42.
	
*/

namespace nsf2;

class gn_division extends \Doctrine_Record_Ex
{

	public function setTableDefinition()
	{
		$this->setTableName('nsf2.gn_division');
		$this->auto_generate_definition();
	}

	public function setUp()
	{
		$this->hasMany('nsf2\farm as Farms',
			array(
				'local' => 'id',
				'foreign' => 'gn_division_id'
			)
		);
		$this->hasOne('nsf2\agrarian_service_center as Agrarian_service_centers',
			array(
				'local' => 'agrarian_service_center_id',
				'foreign' => 'id'
			)
		);
		$this->hasOne('nsf2\agrarian_service_center as Agrarian_service_centers',
			array(
				'local' => 'agrarian_service_center_id',
				'foreign' => 'id'
			)
		);
		$this->hasMany('nsf2\household as Households',
			array(
				'local' => 'id',
				'foreign' => 'gn_division_id'
			)
		);
		$this->hasMany('nsf2\household as Households',
			array(
				'local' => 'id',
				'foreign' => 'gn_division_id'
			)
		);
		$this->hasMany('nsf2\register_user as Register_users',
			array(
				'local' => 'id',
				'foreign' => 'gn_division_id'
			)
		);
		$this->hasOne('nsf2\ds_division as Ds_divisions',
			array(
				'local' => 'ds_division_id',
				'foreign' => 'id'
			)
		);

	}

}

?>