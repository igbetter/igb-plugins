<?php
/**
 * An array of regional rate boxes sizes for USPS
 */

return apply_filters( 'wc_usps_regional_rate_boxes', array(
	'47'  => array(
		'id'         => 'Regional Rate Box A1',
		'name'       => 'Priority Mail Regional Rate Box A1',
		'length'     => '10',
		'width'      => '7',
		'height'     => '4.75',
		'weight'     => '0.35',
		'max_weight' => '15',
	),
	'47b' => array(
		'id'         => 'Regional Rate Box A2',
		'name'       => 'Priority Mail Regional Rate Box A2',
		'length'     => '12.8125',
		'width'      => '10.9375',
		'height'     => '2.375',
		'weight'     => '0.54',
		'max_weight' => '15',
	),
	'49'  => array(
		'id'         => 'Regional Rate Box B1',
		'name'       => 'Priority Mail Regional Rate Box B1',
		'length'     => '12',
		'width'      => '10.25',
		'height'     => '5',
		'weight'     => '0.6',
		'max_weight' => '20',
	),
	'49b' => array(
		'id'         => 'Regional Rate Box B2',
		'name'       => 'Priority Mail Regional Rate Box B2',
		'length'     => '15.875',
		'width'      => '14.375',
		'height'     => '2.875',
		'weight'     => '0.87',
		'max_weight' => '20',
	),
) );
