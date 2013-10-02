<?php

/*
	This file was auto generated on 2013-09-09 at 21:46:29.
	
*/

namespace nsf2;

class season extends \Doctrine_Record_Ex
{

	public function setTableDefinition()
	{
		$this->setTableName('nsf2.season');
		$this->auto_generate_definition();
	}

	public function setUp()
	{
		$this->hasOne('nsf2\crop_variety as Crop_varietys',
			array(
				'local' => 'crop_variety_id',
				'foreign' => 'id'
			)
		);

	}

}

?>