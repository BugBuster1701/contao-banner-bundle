<?php

/**
 * Contao Open Source CMS, Copyright (C) 2005-2020 Leo Feyer
 *
 * Modul Banner - Backend DCA tl_banner
 *
 * This is the data container array for table tl_banner.
 *
 * PHP version 5
 * @copyright  Glen Langer 2007..2020
 * @author     Glen Langer
 * @license    LGPL
 */

/**
 * Table tl_banner
 */
$GLOBALS['TL_DCA']['tl_banner'] =
[

    // Config
    'config' => [
        'dataContainer'               => 'Table',
        'ptable'                      => 'tl_banner_category',
        'enableVersioning'            => true,
        'sql' => [
            'keys' => [
                'id'    => 'primary',
                'pid'   => 'index'
            ]
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode'                    => 4,
            'filter'                  => true,
            'fields'                  => ['sorting'],
            'panelLayout'             => 'filter;search,limit',
            'headerFields'            => ['title', 'banner_protected', 'tstamp', 'id'],
            'header_callback'         => ['BugBuster\Banner\DcaBanner', 'addHeader'],
            'child_record_callback'   => ['BugBuster\Banner\DcaBanner', 'listBanner']
        ],
        'global_operations' => [
            'all' => [
                'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href'                => 'act=select',
                'class'               => 'header_edit_all',
                'attributes'          => 'onclick="Backend.getScrollOffset();"'
            ]
        ],
        'operations' => [
            'edit' => [
                'label'               => &$GLOBALS['TL_LANG']['tl_banner']['edit'],
                'href'                => 'act=edit',
                'icon'                => 'edit.gif'
            ],
            'copy' => [
                'label'               => &$GLOBALS['TL_LANG']['tl_banner']['copy'],
                'href'                => 'act=copy',
                'icon'                => 'copy.svg'
            ],
            'delete' => [
                'label'               => &$GLOBALS['TL_LANG']['tl_banner']['delete'],
                'href'                => 'act=delete',
                'icon'                => 'delete.svg',
                'attributes'          => 'onclick="if (!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\')) return false; Backend.getScrollOffset();"'
            ],
            'toggle' => [
                'label'               => &$GLOBALS['TL_LANG']['tl_banner']['toggle'],
                'icon'                => 'visible.svg',
                'attributes'          => 'onclick="Backend.getScrollOffset(); return AjaxRequest.toggleVisibility(this, %s);"',
                'button_callback'     => ['BugBuster\Banner\DcaBanner', 'toggleIcon']
            ],
            'show' => [
                'label'               => &$GLOBALS['TL_LANG']['tl_banner']['show'],
                'href'                => 'act=show',
                'icon'                => 'show.svg'
            ]
        ]
    ],

    // Palettes
    'palettes' => [
          '__selector__'                => ['banner_type', 'banner_until'],
          'default'                     => 'banner_type',
          'banner_image'                => 'banner_type;{title_legend},banner_name,banner_weighting;{comment_legend},banner_comment;banner_overwritemeta;{destination_legend},banner_url,banner_jumpTo,banner_target;{image_legend},banner_image,banner_imgSize;{filter_legend:hide},banner_domain;{expert_legend:hide},banner_cssid;{publish_legend},banner_published,banner_start,banner_stop,banner_until',
          'banner_image_extern'         => 'banner_type;{title_legend},banner_name,banner_weighting;{comment_legend},banner_comment;{destination_legend},banner_url,banner_target;{image_legend},banner_image_extern,banner_imgSize;{filter_legend:hide},banner_domain;{expert_legend:hide},banner_cssid;{publish_legend},banner_published,banner_start,banner_stop,banner_until',
          'banner_text'                 => 'banner_type;{title_legend},banner_name,banner_weighting;{comment_legend},banner_comment;{destination_legend},banner_url,banner_jumpTo,banner_target;{filter_legend:hide},banner_domain;{expert_legend:hide},banner_cssid;{publish_legend},banner_published,banner_start,banner_stop,banner_until',
          'banner_video'                => 'banner_type;{title_legend},banner_name,banner_weighting;{video_source_legend},banner_playerSRC;{player_legend},banner_playerSize,banner_playerStart,banner_playerStop;{poster_legend:hide},banner_posterSRC;{comment_legend},banner_comment;{destination_legend},banner_url,banner_jumpTo,banner_target;{filter_legend:hide},banner_domain;{expert_legend:hide},banner_cssid;{publish_legend},banner_published,banner_start,banner_stop,banner_until'
    ],
    // Subpalettes
    'subpalettes' => [
        'banner_until'                => 'banner_views_until,banner_clicks_until'
    ],

    // Fields
    'fields' => [
        'id' => [
                'sql'           => "int(10) unsigned NOT NULL auto_increment"
        ],
        'pid' => [
                'sql'           => "int(10) unsigned NOT NULL default '0'"
        ],
        'sorting' => [
                'sql'           => "int(10) unsigned NOT NULL default '0'"
        ],
        'tstamp' => [
                'sql'           => "int(10) unsigned NOT NULL default '0'"
        ],
        'banner_type' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_type'],
            'default'                 => 'default',
            'exclude'                 => true,
            'filter'                  => true,
            'inputType'               => 'select',
            'options'                 => ['default', 'banner_image', 'banner_image_extern', 'banner_text', 'banner_video'],
            'reference'               => &$GLOBALS['TL_LANG']['tl_banner_type'],
            'sql'                     => "varchar(32) NOT NULL default 'banner_image'",
            'eval'                    => ['helpwizard'=>false, 'submitOnChange'=>true]
        ],
        'banner_name' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_name'],
            'inputType'               => 'text',
            'search'                  => true,
            'explanation'	          => 'banner_help',
            'sql'                     => "varchar(64) NOT NULL default ''",
            'eval'                    => ['mandatory'=>false, 'maxlength'=>64, 'helpwizard'=>true, 'tl_class'=>'w50']
        ],
        'banner_weighting' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_weighting'],
            'default'                 => '2',
            'inputType'               => 'select',
            'options'                 => ['1', '2', '3'],
            'reference'               => &$GLOBALS['TL_LANG']['tl_banner'],
            'explanation'	          => 'banner_help',
            'sql'                     => "tinyint(1) NOT NULL default '2'",
            'eval'                    => ['mandatory'=>false, 'maxlength'=>1, 'rgxp'=>'prcnt', 'helpwizard'=>true, 'tl_class'=>'w50']
        ],
        'banner_overwritemeta' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_overwriteMeta'],
            'exclude'                 => true,
            'inputType'               => 'checkbox',
            'eval'                    => ['submitOnChange'=>true, 'tl_class'=>'clr'],
            'sql'                     => "char(1) NOT NULL default ''"
        ],
        'banner_url' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_url'],
            'inputType'               => 'text',
            'explanation'	          => 'banner_help',
            'sql'                     => "varchar(255) NOT NULL default ''",
            'eval'                    => ['mandatory'=>false, 'rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>255, 'helpwizard'=>true]
        ],
        'banner_jumpTo' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_jumpTo'],
            'exclude'                 => true,
            'inputType'               => 'pageTree',
            'explanation'             => 'banner_help',
            'sql'                     => "int(10) unsigned NOT NULL default '0'",
            'eval'                    => ['fieldType'=>'radio', 'helpwizard'=>true]
        ],
        'banner_target' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_target'],
            'exclude'                 => true,
            'inputType'               => 'checkbox',
            'sql'                     => "char(1) NOT NULL default ''"
        ],
        'banner_image' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_image'],
            'explanation'	          => 'banner_help',
            'inputType'               => 'fileTree',
            'sql'                     => "binary(16) NULL",
            'eval'                    => ['mandatory'=>true, 'files'=>true, 'filesOnly'=>true, 'fieldType'=>'radio', 'extensions'=>'jpg,jpe,jpeg,gif,png,webp', 'maxlength'=>255, 'helpwizard'=>true],
            'xlabel' => [
                    ['BugBuster\Banner\DcaBanner', 'fieldLabelCallback']
            ]
        ],
        'banner_image_extern' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_image_extern'],
            'inputType'               => 'text',
            'explanation'	          => 'banner_help',
            'sql'                     => "varchar(255) NOT NULL default ''",
            'eval'                    => ['mandatory'=>true, 'rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>255, 'helpwizard'=>true]
        ],
        'banner_imgSize' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_imgSize'],
            'exclude'                 => true,
            'inputType'               => 'imageSize',
            //'options'                 => System::getContainer()->get('contao.image.image_sizes')->getAllOptions(),
            'options_callback' => function () {
                return System::getContainer()->get('contao.image.image_sizes')->getOptionsForUser(BackendUser::getInstance());
            },
            'reference'               => &$GLOBALS['TL_LANG']['MSC'],
            'sql'                     => "varchar(255) NOT NULL default ''",
            //'eval'                    => array('rgxp'=>'digit', 'nospace'=>true)
            'eval'                    => ['rgxp'=>'natural', 'includeBlankOption'=>true, 'nospace'=>true]
        ],
        'banner_comment' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_comment'],
            'inputType'               => 'textarea',
            'explanation'             => 'banner_help',
            'sql'                     => "text NULL",
            'eval'                    => ['mandatory'=>false, 'preserveTags'=>true, 'helpwizard'=>true]
        ],
        'banner_published' => [
            'exclude'                 => true,
            'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_published'],
            'filter'                  => true,
            'sql'                     => "char(1) NOT NULL default ''",
            'inputType'               => 'checkbox'
        ],
        'banner_start' => [
            'exclude'                 => true,
            'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_start'],
            'inputType'               => 'text',
            'explanation'	          => 'banner_help',
            'sql'                     => "varchar(10) NOT NULL default ''",
            'eval'                    => ['maxlength'=>20, 'rgxp'=>'datim', 'datepicker'=>true, 'helpwizard'=>true, 'tl_class'=>'w50 wizard']
        ],
        'banner_stop' => [
            'exclude'                 => true,
            'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_stop'],
            'inputType'               => 'text',
            'explanation'	          => 'banner_help',
            'sql'                     => "varchar(10) NOT NULL default ''",
            'eval'                    => ['maxlength'=>20, 'rgxp'=>'datim', 'datepicker'=>true, 'helpwizard'=>true, 'tl_class'=>'w50 wizard']
        ],
        'banner_until'  => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_until'],
            'exclude'                 => true,
            'inputType'               => 'checkbox',
            'sql'                     => "char(1) NOT NULL default ''",
            'eval'                    => ['submitOnChange'=>true, 'tl_class'=>'clr']
        ],
        'banner_views_until' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_views_until'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'explanation'	          => 'banner_help',
            'sql'                     => "varchar(10) NOT NULL default ''",
            'eval'                    => ['nospace'=>true, 'maxlength'=>10, 'rgxp'=>'digit', 'helpwizard'=>true, 'tl_class'=>'w50']
        ],
        'banner_clicks_until' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_clicks_until'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'explanation'	          => 'banner_help',
            'sql'                     => "varchar(10) NOT NULL default ''",
            'eval'                    => ['nospace'=>true, 'maxlength'=>10, 'rgxp'=>'digit', 'helpwizard'=>true, 'tl_class'=>'w50']
        ],
        'banner_domain' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_domain'],
            'inputType'               => 'text',
            'explanation'	          => 'banner_help',
            'sql'                     => "varchar(255) NOT NULL default ''",
            'eval'                    => ['mandatory'=>false, 'maxlength'=>255, 'helpwizard'=>true]
        ],
        'banner_cssid' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_cssid'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => ['multiple'=>true, 'size'=>2],
            'sql'                     => "varchar(255) NOT NULL default ''"
        ],
        'banner_playerSRC' => [
            'exclude'                 => true,
            'inputType'               => 'fileTree',
            'eval'                    => [
                'multiple'   => true,
                'fieldType'  => 'checkbox',
                'files'      => true,
                'mandatory'  => true,
                'extensions' => 'mp4,m4v,mov,wmv,webm,ogv',
            ],
            'sql'                     => "blob NULL"
        ],
        'banner_posterSRC' => [
            'exclude'                 => true,
            'inputType'               => 'fileTree',
            'eval'                    => ['filesOnly'=>true, 'fieldType'=>'radio'],
            'sql'                     => "binary(16) NULL"
        ],
        'banner_playerSize' => [
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => ['multiple'=>true, 'size'=>2, 'rgxp'=>'natural', 'nospace'=>true, 'tl_class'=>'w50'],
            'sql'                     => "varchar(64) COLLATE ascii_bin NOT NULL default ''"
        ],
        'banner_playerStart' => [
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => ['rgxp'=>'natural', 'nospace'=>true, 'tl_class'=>'w50 clr'],
            'sql'                     => "int(10) unsigned NOT NULL default 0"
        ],
        'banner_playerStop' => [
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => ['rgxp'=>'natural', 'nospace'=>true, 'tl_class'=>'w50'],
            'sql'                     => "int(10) unsigned NOT NULL default 0"
        ],
    ]
];
