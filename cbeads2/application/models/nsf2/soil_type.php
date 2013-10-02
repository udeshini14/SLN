<?php

/*
	This file was auto generated on 2013-09-09 at 21:47:24.
	
*/

namespace nsf2;

class soil_type extends \Doctrine_Record_Ex
{

	public function setTableDefinition()
	{
		$this->setTableName('nsf2.soil_type');
		$this->auto_generate_definition();
	}

	public function setUp()
	{
		$this->hasMany('nsf2\crop_variety as Crop_varietys',
			array(
				'local' => 'nsf2_soil_type_id',
				'foreign' => 'nsf2_crop_variety_id',
				'refClass' => 'nsf2\crop_variety_soil_type_map'
			)
		);

	}

}

?>