<?php
/**
 * @brief       GD Master Catalog — Install routine
 * @package     IPS Community Suite
 * @subpackage  GD Master Catalog
 * @since       12 Apr 2026
 *
 * Runs after schema.json tables are created.
 * Seeds the six distributor feed records and the category taxonomy.
 */

/* Seed the six distributor feed records with priority hierarchy (Section 2.2.1) */
$distributors = [
	['feed_name' => 'RSR Group Primary',        'distributor' => 'rsr_group',     'priority' => 1],
	['feed_name' => 'Sports South Primary',      'distributor' => 'sports_south',  'priority' => 2],
	['feed_name' => "Davidson's Primary",        'distributor' => 'davidsons',     'priority' => 3],
	['feed_name' => "Lipsey's Primary",          'distributor' => 'lipseys',       'priority' => 4],
	['feed_name' => 'Zanders Sporting Goods',    'distributor' => 'zanders',       'priority' => 5],
	['feed_name' => 'Bill Hicks Primary',        'distributor' => 'bill_hicks',    'priority' => 6],
];

/* Default conflict detection fields per Section 2.11.2 */
$defaultConflictFields = json_encode([
	'restricted_states' => true,
	'nfa_item'          => true,
	'requires_ffl'      => true,
	'caliber'           => true,
	'rounds_per_box'    => true,
	'category'          => false,
	'manufacturer'      => false,
	'description'       => false,
]);

foreach ( $distributors as $dist )
{
	\IPS\Db::i()->insert( 'gd_distributor_feeds', [
		'feed_name'                => $dist['feed_name'],
		'distributor'              => $dist['distributor'],
		'priority'                 => $dist['priority'],
		'feed_url'                 => '',
		'feed_format'              => 'xml',
		'auth_type'                => 'none',
		'auth_credentials'         => NULL,
		'field_mapping'            => NULL,
		'category_mapping'         => NULL,
		'import_schedule'          => '6hr',
		'conflict_detection_fields'=> $defaultConflictFields,
		'active'                   => 0,
		'last_run'                 => NULL,
		'last_record_count'        => 0,
		'last_run_status'          => NULL,
	]);
}

/* Seed category taxonomy (Section 2.4) */
$categories = [
	'Handguns'               => ['Pistols', 'Revolvers', 'Derringers'],
	'Rifles'                 => ['Semi-Automatic', 'Bolt-Action', 'Lever-Action', 'Single-Shot', 'Muzzleloaders'],
	'Shotguns'               => ['Semi-Automatic', 'Pump-Action', 'Break-Action', 'Over/Under', 'Side-by-Side'],
	'Ammunition'             => ['Handgun Ammo', 'Rifle Ammo', 'Shotgun Ammo', 'Rimfire', 'Specialty/Exotic'],
	'NFA Items'              => ['Suppressors', 'Short-Barreled Rifles', 'Short-Barreled Shotguns', 'Machine Guns', 'AOW'],
	'Magazines'              => ['Handgun', 'Rifle', 'Shotgun', 'Drum'],
	'Optics'                 => ['Red Dots', 'Rifle Scopes', 'LPVOs', 'Prism Scopes', 'Night Vision', 'Thermal', 'Magnifiers'],
	'Parts & Accessories'    => ['Barrels', 'Triggers', 'Stocks', 'Grips', 'Rails', 'Handguards', 'Muzzle Devices'],
	'Holsters & Carry'       => ['IWB', 'OWB', 'Shoulder', 'Ankle', 'Appendix', 'Duty', 'Vehicle'],
	'Storage & Safety'       => ['Gun Safes', 'Hard Cases', 'Soft Cases', 'Lock Boxes', 'Trigger Locks'],
	'Cleaning & Maintenance' => ['Cleaning Kits', 'Lubricants', 'Solvents', 'Bore Snakes', 'Patches'],
	'Tactical Gear'          => ['Weapon Lights', 'Lasers', 'Bipods', 'Slings', 'Foregrips', 'Vertical Grips'],
	'Hunting Gear'           => ['Game Calls', 'Scent Control', 'Blinds', 'Feeders', 'Trail Cameras'],
];

$position = 0;
foreach ( $categories as $parentName => $children )
{
	$slug = mb_strtolower( preg_replace( '/[^a-z0-9]+/i', '-', $parentName ) );
	$slug = trim( $slug, '-' );

	$parentId = \IPS\Db::i()->insert( 'gd_categories', [
		'parent_id'     => 0,
		'name'          => $parentName,
		'slug'          => $slug,
		'position'      => $position++,
		'product_count' => 0,
	]);

	$childPos = 0;
	foreach ( $children as $childName )
	{
		$childSlug = $slug . '-' . mb_strtolower( preg_replace( '/[^a-z0-9]+/i', '-', $childName ) );
		$childSlug = trim( $childSlug, '-' );

		\IPS\Db::i()->insert( 'gd_categories', [
			'parent_id'     => $parentId,
			'name'          => $childName,
			'slug'          => $childSlug,
			'position'      => $childPos++,
			'product_count' => 0,
		]);
	}
}
