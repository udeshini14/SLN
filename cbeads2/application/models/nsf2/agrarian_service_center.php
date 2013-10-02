<?php

/*
	This file was auto generated on 2013-09-09 at 23:57:25.
	
*/

namespace nsf2;

class agrarian_service_center extends \Doctrine_Record_Ex
{

	public function setTableDefinition()
	{
		$this->setTableName('nsf2.agrarian_service_center');
		$this->auto_generate_definition();
	}

	public function setUp()
	{
		$this->hasMany('nsf2\GN_division as Gn_divisions',
			array(
				'local' => 'id',
				'foreign' => 'agrarian_service_center_id'
			)
		);

	}

}

?>