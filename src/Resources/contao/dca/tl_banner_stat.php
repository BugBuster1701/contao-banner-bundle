<?php 
/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2012 Leo Feyer
 *
 * @link http://www.contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 * 
 * Modul Banner - Backend DCA tl_banner_stat
 * 
 * This is the data container array for table tl_banner_stat.
 *
 * PHP version 5
 * @copyright  Glen Langer 2007..2015
 * @author     Glen Langer
 * @package    Banner
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


