<?php

/*
	This file was auto generated on 2013-09-26 at 02:35:59.
	
*/

namespace nsf2;

class verify_status extends \Doctrine_Record_Ex
{

	public function setTableDefinition()
	{
		$this->setTableName('nsf2.verify_status');
		$this->auto_generate_definition();
	}

	public function setUp()
	{
		$this->hasMany('nsf2\crop as Crops',
			array(
				'local' => 'id',
				'foreign' => 'verify_status_id'
			)
		);

	}

}

?>