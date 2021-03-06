<?php

/*
	This file was auto generated on 2013-09-20 at 10:24:05.
	
*/

namespace nsf2;

class farm extends \Doctrine_Record_Ex
{

	public function setTableDefinition()
	{
		$this->setTableName('nsf2.farm');
		$this->auto_generate_definition();
	}

	public function setUp()
	{
		$this->hasMany('nsf2\crop_status as Crop_status',
			array(
				'local' => 'id',
				'foreign' => 'farm_id'
			)
		);
		$this->hasOne('nsf2\household as Households',
			array(
				'local' => 'household_id',
				'foreign' => 'id'
			)
		);
		$this->hasOne('nsf2\household as Households',
			array(
				'local' => 'household_id',
				'foreign' => 'id'
			)
		);
		$this->hasOne('nsf2\variety_grown_in_farm as Variety_grown_in_farm',
			array(
				'local' => 'variety_grown_in_farm_id',
				'foreign' => 'id'
			)
		);
		$this->hasOne('nsf2\gn_division as Gn_division',
			array(
				'local' => 'gn_division_id',
				'foreign' => 'id'
			)
		);
		$this->hasOne('nsf2\ds_division as Ds_division',
			array(
				'local' => 'ds_division_id',
				'foreign' => 'id'
			)
		);
		$this->hasMany('nsf2\variety_grown_in_farm as Variety_grown_in_farms',
			array(
				'local' => 'id',
				'foreign' => 'farm_id'
			)
		);

	}

}

?>