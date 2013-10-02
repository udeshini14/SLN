<?php

/*
	This file was auto generated on 2013-09-09 at 21:48:04.
	
*/

namespace nsf2;

class climate_condition extends \Doctrine_Record_Ex
{

	public function setTableDefinition()
	{
		$this->setTableName('nsf2.climate_condition');
		$this->auto_generate_definition();
	}

	public function setUp()
	{
		$this->hasMany('nsf2\crop_variety as Crop_varietys',
			array(
				'local' => 'nsf2_climate_condition_id',
				'foreign' => 'nsf2_crop_variety_id',
				'refClass' => 'nsf2\crop_variety_climate_condition_map'
			)
		);

	}

}

?>