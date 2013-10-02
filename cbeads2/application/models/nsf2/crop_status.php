<?php

/*
	This file was auto generated on 2013-09-20 at 10:14:48.
	
*/

namespace nsf2;

class crop_status extends \Doctrine_Record_Ex
{

	public function setTableDefinition()
	{
		$this->setTableName('nsf2.crop_status');
		$this->auto_generate_definition();
	}

	public function setUp()
	{
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