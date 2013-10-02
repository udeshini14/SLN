<?php

/*
	This file was auto generated on 2013-09-21 at 08:29:22.
	
*/

namespace nsf2;

class crop_variety extends \Doctrine_Record_Ex
{

	public function setTableDefinition()
	{
		$this->setTableName('nsf2.crop_variety');
		$this->auto_generate_definition();
	}

	public function setUp()
	{
		$this->hasMany('nsf2\crop_status as Crop_status',
			array(
				'local' => 'id',
				'foreign' => 'crop_variety_id'
			)
		);
		$this->hasOne('nsf2\crop as Crops',
			array(
				'local' => 'crop_id',
				'foreign' => 'id'
			)
		);
		$this->hasOne('nsf2\crop as Crops',
			array(
				'local' => 'crop_id',
				'foreign' => 'id'
			)
		);
		$this->hasMany('nsf2\climate_condition as Climate_conditions',
			array(
				'local' => 'nsf2_crop_variety_id',
				'foreign' => 'nsf2_climate_condition_id',
				'refClass' => 'nsf2\crop_variety_climate_condition_map'
			)
		);
		$this->hasMany('nsf2\climate_condition as Climate_conditions',
			array(
				'local' => 'nsf2_crop_variety_id',
				'foreign' => 'nsf2_climate_condition_id',
				'refClass' => 'nsf2\crop_variety_climate_condition_map'
			)
		);
		$this->hasMany('nsf2\climate_condition as Climate_conditions',
			array(
				'local' => 'nsf2_crop_variety_id',
				'foreign' => 'nsf2_climate_condition_id',
				'refClass' => 'nsf2\crop_variety_climate_condition_map'
			)
		);
		$this->hasMany('nsf2\climate_condition as Climate_conditions',
			array(
				'local' => 'nsf2_crop_variety_id',
				'foreign' => 'nsf2_climate_condition_id',
				'refClass' => 'nsf2\crop_variety_climate_condition_map'
			)
		);
		$this->hasMany('nsf2\grown_areas as Grown_areas',
			array(
				'local' => 'nsf2_crop_variety_id',
				'foreign' => 'nsf2_grown_areas_id',
				'refClass' => 'nsf2\crop_variety_grown_areas_map'
			)
		);
		$this->hasMany('nsf2\grown_areas as Grown_areas',
			array(
				'local' => 'nsf2_crop_variety_id',
				'foreign' => 'nsf2_grown_areas_id',
				'refClass' => 'nsf2\crop_variety_grown_areas_map'
			)
		);
		$this->hasMany('nsf2\grown_areas as Grown_areas',
			array(
				'local' => 'nsf2_crop_variety_id',
				'foreign' => 'nsf2_grown_areas_id',
				'refClass' => 'nsf2\crop_variety_grown_areas_map'
			)
		);
		$this->hasMany('nsf2\grown_areas as Grown_areas',
			array(
				'local' => 'nsf2_crop_variety_id',
				'foreign' => 'nsf2_grown_areas_id',
				'refClass' => 'nsf2\crop_variety_grown_areas_map'
			)
		);
		$this->hasMany('nsf2\soil_type as Soil_types',
			array(
				'local' => 'nsf2_crop_variety_id',
				'foreign' => 'nsf2_soil_type_id',
				'refClass' => 'nsf2\crop_variety_soil_type_map'
			)
		);
		$this->hasMany('nsf2\soil_type as Soil_types',
			array(
				'local' => 'nsf2_crop_variety_id',
				'foreign' => 'nsf2_soil_type_id',
				'refClass' => 'nsf2\crop_variety_soil_type_map'
			)
		);
		$this->hasMany('nsf2\soil_type as Soil_types',
			array(
				'local' => 'nsf2_crop_variety_id',
				'foreign' => 'nsf2_soil_type_id',
				'refClass' => 'nsf2\crop_variety_soil_type_map'
			)
		);
		$this->hasMany('nsf2\soil_type as Soil_types',
			array(
				'local' => 'nsf2_crop_variety_id',
				'foreign' => 'nsf2_soil_type_id',
				'refClass' => 'nsf2\crop_variety_soil_type_map'
			)
		);
		$this->hasMany('nsf2\harvest as Harvests',
			array(
				'local' => 'id',
				'foreign' => 'crop_variety_id'
			)
		);
		$this->hasMany('nsf2\season as Seasons',
			array(
				'local' => 'id',
				'foreign' => 'crop_variety_id'
			)
		);
		$this->hasMany('nsf2\season as Seasons',
			array(
				'local' => 'id',
				'foreign' => 'crop_variety_id'
			)
		);
		$this->hasMany('nsf2\variety_grown_in_farm as Variety_grown_in_farms',
			array(
				'local' => 'id',
				'foreign' => 'crop_variety_id'
			)
		);
		$this->hasMany('nsf2\variety_grown_in_farm as Variety_grown_in_farms',
			array(
				'local' => 'id',
				'foreign' => 'crop_variety_id'
			)
		);

	}

}

?>