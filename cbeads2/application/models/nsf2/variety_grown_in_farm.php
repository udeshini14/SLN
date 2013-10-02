<?php

/*
	This file was auto generated on 2013-09-20 at 10:24:05.
	
*/

namespace nsf2;

class variety_grown_in_farm extends \Doctrine_Record_Ex
{

	public function setTableDefinition()
	{
		$this->setTableName('nsf2.variety_grown_in_farm');
		$this->auto_generate_definition();
	}

	public function setUp()
	{
		$this->hasMany('nsf2\farm as Farms',
			array(
				'local' => 'id',
				'foreign' => 'variety_grown_in_farm_id'
			)
		);
		$this->hasOne('nsf2\crop_variety as Crop_variety',
			array(
				'local' => 'crop_variety_id',
				'foreign' => 'id'
			)
		);
		$this->hasOne('nsf2\crop_variety as Crop_variety',
			array(
				'local' => 'crop_variety_id',
				'foreign' => 'id'
			)
		);
		$this->hasOne('nsf2\farm as Farms',
			array(
				'local' => 'farm_id',
				'foreign' => 'id'
			)
		);

	}

}

?>