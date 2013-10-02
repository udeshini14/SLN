<?php

/*
	This file was auto generated on 2013-09-26 at 02:35:59.
	
*/

namespace nsf2;

class crop extends \Doctrine_Record_Ex
{

	public function setTableDefinition()
	{
		$this->setTableName('nsf2.crop');
		$this->auto_generate_definition();
	}

	public function setUp()
	{
		$this->hasMany('nsf2\crop_variety as Crop_varietys',
			array(
				'local' => 'id',
				'foreign' => 'crop_id'
			)
		);
		$this->hasMany('nsf2\crop_variety as Crop_varietys',
			array(
				'local' => 'id',
				'foreign' => 'crop_id'
			)
		);
		$this->hasOne('nsf2\verify_status as Verify_status',
			array(
				'local' => 'verify_status_id',
				'foreign' => 'id'
			)
		);

	}

}

?>