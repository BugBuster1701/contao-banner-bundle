<?php

/**
 * Contao Open Source CMS, Copyright (C) 2005-2017 Leo Feyer
 *
 * Modul Banner - Backend DCA tl_banner_category
 *
 * This is the data container array for table tl_banner_category.
 *
 * PHP version 5
 * @copyright  Glen Langer 2007..2017
 * @author     Glen Langer
 * @license    LGPL
 */

/**
 * Table tl_banner_category
 */
$GLOBALS['TL_DCA']['tl_banner_category'] =
[

    // Config
    'config' =>
    [
        'dataContainer'               => 'Table',
        'ctable'                      => ['tl_banner'],
        'switchToEdit'                => true,
        'enableVersioning'            => true,
        'sql' =>
        [
            'keys' =>
            [
                'id'    => 'primary'
            ]
        ],
    ],

    // List
    'list' =>
    [
        'sorting' =>
        [
            'mode'                    => 1,
            'fields'                  => ['title'],
            'flag'                    => 1,
            'panelLayout'             => 'search,limit'
        ],
        'label' =>
        [
            //'fields'                  => array('title','banner_template','banner_protected'),
            //'format'                  => '%s <br /><span style="color:#b3b3b3;">[%s]<br />[%s]</span>'
            'fields'                  => ['tag'],
            'format'                  => '%s',
            'label_callback'		  => ['BugBuster\Banner\DcaBannerCategory', 'labelCallback'],
        ],
        'global_operations' =>
        [
            'all' =>
            [
                'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href'                => 'act=select',
                'class'               => 'header_edit_all',
                'attributes'          => 'onclick="Backend.getScrollOffset();"'
            ]
        ],
        'operations' =>
        [
            'edit' =>
            [
                'label'               => &$GLOBALS['TL_LANG']['tl_banner_category']['edit'],
                'href'                => 'table=tl_banner',
                'icon'                => 'edit.gif',
                'attributes'          => 'class="contextmenu"'
            ],
            'editheader' =>
            [
                'label'               => &$GLOBALS['TL_LANG']['tl_banner_category']['editheader'],
                'href'                => 'act=edit',
                'icon'                => 'header.gif',
                'attributes'          => 'class="edit-header"'
            ],
            'copy' =>
            [
                'label'               => &$GLOBALS['TL_LANG']['tl_banner_category']['copy'],
                'href'                => 'act=copy',
                'icon'                => 'copy.gif'
            ],
            'delete' =>
            [
                'label'               => &$GLOBALS['TL_LANG']['tl_banner_category']['delete'],
                'href'                => 'act=delete',
                'icon'                => 'delete.gif',
                'attributes'          => 'onclick="if (!confirm(\'' . ($GLOBALS['TL_LANG']['tl_banner_category']['deleteConfirm'] ?? null) . '\')) return false; Backend.getScrollOffset();"'
            ],
            'show' =>
            [
                'label'               => &$GLOBALS['TL_LANG']['tl_banner_category']['show'],
                'href'                => 'act=show',
                'icon'                => 'show.gif'
            ],
            'stat' =>
            [
                'label'               => &$GLOBALS['TL_LANG']['tl_banner_category']['stat'],
                'href'                => 'do=bannerstat',
                'icon'                => 'bundles/bugbusterbanner/iconBannerStat.gif'
            ]
        ]
    ],

    // Palettes
    'palettes' =>
    [
        '__selector__'                => ['banner_default', 'banner_protected', 'banner_numbers', 'banner_stat_protected'],
        'default'                     => '{title_legend},title;{default_legend:hide},banner_default;{number_legend:hide},banner_numbers;{protected_legend:hide},banner_protected;{protected_stat_legend:hide},banner_stat_protected;{banner_expert_legend:hide},banner_expert_debug_all'
    ],
    // Subpalettes
    'subpalettes' =>
    [
        'banner_default'              => 'banner_default_name,banner_default_url,banner_default_image,banner_default_target',
        'banner_protected'            => 'banner_groups',
        'banner_numbers'              => 'banner_limit,banner_random',
        'banner_stat_protected'       => 'banner_stat_groups,banner_stat_admins',
    ],

    // Fields
    'fields' =>
    [
        'id' =>
        [
            'sql'                     => "int(10) unsigned NOT NULL auto_increment"
        ],
        'tstamp' =>
        [
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ],
        'title' 					  =>
        [
            'label'                   => &$GLOBALS['TL_LANG']['tl_banner_category']['title'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'sql'                     => "varchar(60) NOT NULL default ''",
            'eval'                    => ['mandatory'=>true, 'maxlength'=>60, 'tl_class'=>'w50']
        ],
        'banner_default'              =>
        [
            'label'                   => &$GLOBALS['TL_LANG']['tl_banner_category']['banner_default'],
            'exclude'                 => true,
            'inputType'               => 'checkbox',
            'sql'                     => "char(1) NOT NULL default ''",
            'eval'                    => ['submitOnChange'=>true]
        ],
        'banner_default_name'         =>
        [
            'label'                   => &$GLOBALS['TL_LANG']['tl_banner_category']['banner_default_name'],
            'inputType'               => 'text',
            'search'                  => true,
            'sql'                     => "varchar(64) NOT NULL default ''",
            'eval'                    => ['mandatory'=>false, 'maxlength'=>64, 'tl_class'=>'w50']
        ],
        'banner_default_url'		  =>
        [
            'label'                   => &$GLOBALS['TL_LANG']['tl_banner_category']['banner_default_url'],
            'inputType'               => 'text',
            'explanation'	          => 'banner_help',
            'sql'                     => "varchar(128) NOT NULL default ''",
            'eval'                    => ['mandatory'=>false, 'maxlength'=>128, 'tl_class'=>'w50']
        ],
        'banner_default_image'        =>
        [
            'label'                   => &$GLOBALS['TL_LANG']['tl_banner_category']['banner_default_image'],
            'exclude'                 => true,
            'inputType'               => 'fileTree',
            //'sql'                     => "varchar(255) NOT NULL default ''",
            'sql'                     => "binary(16) NULL",
            'eval'                    => ['mandatory'=>true, 'files'=>true, 'filesOnly'=>true, 'fieldType'=>'radio', 'extensions'=>'jpg,jpe,jpeg,gif,png,webp', 'maxlength'=>255, 'helpwizard'=>false, 'tl_class'=>'clr'],
            'xlabel' =>
                        [
                                ['BugBuster\Banner\DcaBannerCategory', 'fieldLabelCallback']
                        ]

        ],
        'banner_default_target'		  =>
        [
            'label'                   => &$GLOBALS['TL_LANG']['tl_banner_category']['banner_default_target'],
            'exclude'                 => true,
            'sql'                     => "char(1) NOT NULL default ''",
            'inputType'               => 'checkbox'
        ],
        'banner_numbers'			  =>
        [
            'label'                   => &$GLOBALS['TL_LANG']['tl_banner_category']['banner_numbers'],
            'exclude'                 => true,
            'inputType'               => 'checkbox',
            'sql'                     => "char(1) NOT NULL default ''",
            'eval'                    => ['submitOnChange'=>true]
        ],
        'banner_random'				  =>
        [
            'label'                   => &$GLOBALS['TL_LANG']['tl_banner_category']['banner_random'],
            'exclude'                 => true,
            'inputType'               => 'checkbox',
            'sql'                     => "char(1) NOT NULL default ''"
        ],
        'banner_limit'				  =>
        [
            'label'                   => &$GLOBALS['TL_LANG']['tl_banner_category']['banner_limit'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'sql'                     => "int(10) unsigned NOT NULL default '0'",
            'eval'                    => ['rgxp'=>'digit', 'nospace'=>true, 'maxlength'=>10]
        ],
        'banner_protected'            =>
        [
            'label'                   => &$GLOBALS['TL_LANG']['tl_banner_category']['banner_protected'],
            'exclude'                 => true,
            'filter'                  => true,
            'inputType'               => 'checkbox',
            'sql'                     => "char(1) NOT NULL default ''",
            'eval'                    => ['submitOnChange'=>true]
        ],
        'banner_groups'               =>
        [
            'label'                   => &$GLOBALS['TL_LANG']['tl_banner_category']['banner_groups'],
            'exclude'                 => true,
            'inputType'               => 'checkbox',
            'foreignKey'              => 'tl_member_group.name',
            'sql'                     => "varchar(255) NOT NULL default ''",
            'eval'                    => ['multiple'=>true]
        ],
        'banner_stat_protected'       =>
        [
            'label'                   => &$GLOBALS['TL_LANG']['tl_banner_category']['banner_stat_protected'],
            'exclude'                 => true,
            'filter'                  => true,
            'inputType'               => 'checkbox',
            'sql'                     => "char(1) NOT NULL default ''",
            'eval'                    => ['submitOnChange'=>true]
        ],
        'banner_stat_groups'          =>
        [
            'label'                   => &$GLOBALS['TL_LANG']['tl_banner_category']['banner_stat_groups'],
            'exclude'                 => true,
            'inputType'               => 'checkbox',
            'foreignKey'              => 'tl_user_group.name',
            'sql'                     => "varchar(255) NOT NULL default ''",
            'eval'                    => ['multiple'=>true]
        ],
        'banner_stat_admins' =>
        [
            'label'                   => &$GLOBALS['TL_LANG']['tl_banner_category']['banner_stat_admins'],
            'inputType'               => 'checkbox',
            'eval'                    => ['disabled'=>true],
            'sql'				      => null,
            'load_callback' =>
            [
                ['BugBuster\Banner\DcaBannerCategory', 'getAdminCheckbox']
            ]
        ],
        'banner_expert_debug_all'=>
        [
            'label'					  => &$GLOBALS['TL_LANG']['tl_banner_category']['banner_expert_debug_all'],
            'inputType'               => 'checkbox',
            'sql'                     => "char(1) NOT NULL default ''",
            'eval'                    => ['mandatory'=>false, 'helpwizard'=>false]
        ]
    ]
];
