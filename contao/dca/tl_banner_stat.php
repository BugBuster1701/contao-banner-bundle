<?php

/*
 * This file is part of a BugBuster Contao Bundle.
 *
 * @copyright  Glen Langer 2024 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Contao Banner Bundle
 * @link       https://github.com/BugBuster1701/contao-banner-bundle
 *
 * @license    LGPL-3.0-or-later
 */

use Contao\DC_Table;

/*
 * Table tl_banner_stat
 */
$GLOBALS['TL_DCA']['tl_banner_stat'] =
array(
	// Config
	'config' => array(
		'dataContainer'               => DC_Table::class,
		'sql' => array(
			'keys' => array(
				'id'    => 'primary'
			)
		),
	),
	// Fields
	'fields' => array(
		'id' => array(
			'sql'           => "int(10) unsigned NOT NULL default '0'"
		),
		'tstamp' => array(
			'sql'           => "int(10) unsigned NOT NULL default '0'"
		),
		'banner_views' => array(
			'sql'           => "int(10) unsigned NOT NULL default '0'"
		),
		'banner_clicks' => array(
			'sql'           => "int(10) unsigned NOT NULL default '0'"
		),
	)
);
