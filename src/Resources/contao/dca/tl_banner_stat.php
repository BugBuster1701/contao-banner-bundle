<?php

/**
 * Contao Open Source CMS, Copyright (C) 2005-2022 Leo Feyer
 *
 * Modul Banner - Backend DCA tl_banner_stat
 *
 * This is the data container array for table tl_banner_stat.
 *
 * @copyright  Glen Langer 2007..2022 <http://contao.ninja>
 * @author     Glen Langer
 * @license    LGPL
 */

/**
 * Table tl_banner_stat
 */
$GLOBALS['TL_DCA']['tl_banner_stat'] =
[

    // Config
    'config' => [
        'dataContainer'               => Contao\DC_Table::class,
        'sql' => [
            'keys' => [
                'id'    => 'primary'
            ]
        ],
    ],
    // Fields
    'fields' => [
        'id' => [
                'sql'           => "int(10) unsigned NOT NULL default '0'"
        ],
        'tstamp' => [
                'sql'           => "int(10) unsigned NOT NULL default '0'"
        ],
        'banner_views' => [
                'sql'           => "int(10) unsigned NOT NULL default '0'"
        ],
        'banner_clicks' => [
                'sql'           => "int(10) unsigned NOT NULL default '0'"
        ],
    ]
];
