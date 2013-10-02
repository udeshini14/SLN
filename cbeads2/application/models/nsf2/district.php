<?php

/*
	This file was auto generated on 2013-09-09 at 21:48:42.
	
*/

namespace nsf2;

class district extends \Doctrine_Record_Ex
{

	public function setTableDefinition()
	{
		$this->setTableName('nsf2.district');
		$this->auto_generate_definition();
	}

	public function setUp()
	{
		$this->hasMany('nsf2\DS_division as Ds_divisions',
			array(
				'local' => 'id',
				'foreign' => 'district_id'
			)
		);

	}

}

?>