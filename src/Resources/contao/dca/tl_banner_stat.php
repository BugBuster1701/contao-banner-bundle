<?php

/**
 * Contao Open Source CMS, Copyright (C) 2005-2017 Leo Feyer
 *
 * Modul Banner - Backend DCA tl_banner_stat
 * 
 * This is the data container array for table tl_banner_stat.
 *
 * PHP version 5
 * @copyright  Glen Langer 2007..2017
 * @author     Glen Langer
 * @license    LGPL
 */

/**
 * Table tl_banner_stat
 */
$GLOBALS['TL_DCA']['tl_banner_stat'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
        'sql' => array
        (
            'keys' => array
            (
                'id'    => 'primary'
            )
        ),
	),
	// Fields
	'fields' => array
	(
    	'id' => array
    	(
    	        'sql'           => "int(10) unsigned NOT NULL default '0'"
    	),
    	'tstamp' => array
    	(
    	        'sql'           => "int(10) unsigned NOT NULL default '0'"
    	),
        'banner_views' => array
        (
                'sql'           => "int(10) unsigned NOT NULL default '0'"
        ),
        'banner_clicks' => array
        (
                'sql'           => "int(10) unsigned NOT NULL default '0'"
        ),
	)
);

