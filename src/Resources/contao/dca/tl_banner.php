<?php

/**
 * Contao Open Source CMS, Copyright (C) 2005-2017 Leo Feyer
 *
 * Modul Banner - Backend DCA tl_banner
 * 
 * This is the data container array for table tl_banner.
 *
 * PHP version 5
 * @copyright  Glen Langer 2007..2017
 * @author     Glen Langer
 * @license    LGPL
 */

/**
 * Table tl_banner
 */
$GLOBALS['TL_DCA']['tl_banner'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'ptable'                      => 'tl_banner_category',
		'enableVersioning'            => true,
        'sql' => array
        (
            'keys' => array
            (
                'id'    => 'primary',
                'pid'   => 'index'
            )
        ),
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 4,
			'filter'                  => true,
			'fields'                  => array('sorting'),
			'panelLayout'             => 'filter;search,limit',
			'headerFields'            => array('title', 'banner_protected', 'tstamp', 'id'),
			'header_callback'         => array('BugBuster\Banner\DcaBanner', 'addHeader'),
			'child_record_callback'   => array('BugBuster\Banner\DcaBanner', 'listBanner')
		),		
		'global_operations' => array
		(
			'all' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset();"'
			)
		),
		'operations' => array
		(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_banner']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.gif'
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_banner']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.svg'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_banner']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
			),
			'toggle' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_banner']['toggle'],
				'icon'                => 'visible.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset(); return AjaxRequest.toggleVisibility(this, %s);"',
				'button_callback'     => array('BugBuster\Banner\DcaBanner', 'toggleIcon')
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_banner']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
	      '__selector__'                => array('banner_type', 'banner_until'),
		  'default'                     => 'banner_type',
		  'banner_image'                => 'banner_type;{title_legend},banner_name,banner_weighting;{destination_legend},banner_url,banner_jumpTo,banner_target;{image_legend},banner_image,banner_imgSize;{comment_legend},banner_comment;{filter_legend:hide},banner_domain;{expert_legend:hide},banner_cssid;{publish_legend},banner_published,banner_start,banner_stop,banner_until',
		  'banner_image_extern'         => 'banner_type;{title_legend},banner_name,banner_weighting;{destination_legend},banner_url,banner_target;{image_legend},banner_image_extern,banner_imgSize;{comment_legend},banner_comment;{filter_legend:hide},banner_domain;{expert_legend:hide},banner_cssid;{publish_legend},banner_published,banner_start,banner_stop,banner_until',
		  'banner_text'                 => 'banner_type;{title_legend},banner_name,banner_weighting;{destination_legend},banner_url,banner_jumpTo,banner_target;{comment_legend},banner_comment;{filter_legend:hide},banner_domain;{expert_legend:hide},banner_cssid;{publish_legend},banner_published,banner_start,banner_stop,banner_until'
	),
    // Subpalettes
	'subpalettes' => array
	(
		'banner_until'                => 'banner_views_until,banner_clicks_until'
	),

	// Fields
	'fields' => array
	(
    	'id' => array
    	(
    	        'sql'           => "int(10) unsigned NOT NULL auto_increment"
    	),
    	'pid' => array
    	(
    	        'sql'           => "int(10) unsigned NOT NULL default '0'"
    	),
    	'sorting' => array
    	(
    	        'sql'           => "int(10) unsigned NOT NULL default '0'"
    	),
    	'tstamp' => array
    	(
    	        'sql'           => "int(10) unsigned NOT NULL default '0'"
    	),
	    'banner_type' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_type'],
			'default'                 => 'default',
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'select',
			'options'                 => array('default', 'banner_image', 'banner_image_extern', 'banner_text'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_banner_type'],
			'sql'                     => "varchar(32) NOT NULL default 'banner_image'",
			'eval'                    => array('helpwizard'=>false, 'submitOnChange'=>true)
		),
		'banner_name' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_name'],
			'inputType'               => 'text',
			'search'                  => true,
			'explanation'	          => 'banner_help',
			'sql'                     => "varchar(64) NOT NULL default ''",
			'eval'                    => array('mandatory'=>false, 'maxlength'=>64, 'helpwizard'=>true, 'tl_class'=>'w50')
		),
		'banner_weighting' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_weighting'],
			'default'                 => '2',
			'inputType'               => 'select',
			'options'                 => array('1', '2', '3'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_banner'],
			'explanation'	          => 'banner_help',
			'sql'                     => "tinyint(1) NOT NULL default '2'",
			'eval'                    => array('mandatory'=>false, 'maxlength'=>1, 'rgxp'=>'prcnt', 'helpwizard'=>true, 'tl_class'=>'w50')
		),
		'banner_url' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_url'],
			'inputType'               => 'text',
			'explanation'	          => 'banner_help',
			'sql'                     => "varchar(255) NOT NULL default ''",
			'eval'                    => array('mandatory'=>false, 'rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>255, 'helpwizard'=>true)
		),
		'banner_jumpTo' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_jumpTo'],
			'exclude'                 => true,
			'inputType'               => 'pageTree',
			'explanation'             => 'banner_help',
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
			'eval'                    => array('fieldType'=>'radio', 'helpwizard'=>true)
		), 
		'banner_target' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_target'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'banner_image' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_image'],
			'explanation'	          => 'banner_help',
			'inputType'               => 'fileTree',
			'sql'                     => "binary(16) NULL",
			'eval'                    => array('mandatory'=>true, 'files'=>true, 'filesOnly'=>true, 'fieldType'=>'radio', 'extensions'=>'jpg,jpe,gif,png', 'maxlength'=>255, 'helpwizard'=>true)
		),
		'banner_image_extern' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_image_extern'],
			'inputType'               => 'text',
			'explanation'	          => 'banner_help',
			'sql'                     => "varchar(255) NOT NULL default ''",
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>255, 'helpwizard'=>true)
		),
        'banner_imgSize' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_imgSize'],
			'exclude'                 => true,
			'inputType'               => 'imageSize',
			//'options'                 => System::getContainer()->get('contao.image.image_sizes')->getAllOptions(),
		    'options_callback' => function ()
		    {
		        return System::getContainer()->get('contao.image.image_sizes')->getOptionsForUser(BackendUser::getInstance());
		    },
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'sql'                     => "varchar(255) NOT NULL default ''",
			//'eval'                    => array('rgxp'=>'digit', 'nospace'=>true)
		    'eval'                    => array('rgxp'=>'natural', 'includeBlankOption'=>true, 'nospace'=>true)
		),
        'banner_comment' => array
        (
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_comment'],
			'inputType'               => 'textarea',
			'explanation'             => 'banner_help',
			'sql'                     => "text NULL",
			'eval'                    => array('mandatory'=>false, 'preserveTags'=>true, 'helpwizard'=>true)
        ),
		'banner_published' => array
		(
			'exclude'                 => true,
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_published'],
			'filter'                  => true,
			'sql'                     => "char(1) NOT NULL default ''",
			'inputType'               => 'checkbox'
		),
		'banner_start' => array
		(
			'exclude'                 => true,
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_start'],
			'inputType'               => 'text',
			'explanation'	          => 'banner_help',
			'sql'                     => "varchar(10) NOT NULL default ''",
			'eval'                    => array('maxlength'=>20, 'rgxp'=>'datim', 'datepicker'=>true, 'helpwizard'=>true, 'tl_class'=>'w50 wizard')
		),
		'banner_stop' => array
		(
			'exclude'                 => true,
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_stop'],
			'inputType'               => 'text',
			'explanation'	          => 'banner_help',
			'sql'                     => "varchar(10) NOT NULL default ''",
			'eval'                    => array('maxlength'=>20, 'rgxp'=>'datim', 'datepicker'=>true, 'helpwizard'=>true, 'tl_class'=>'w50 wizard')
		),
		'banner_until'  => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_until'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'sql'                     => "char(1) NOT NULL default ''",
			'eval'                    => array('submitOnChange'=>true, 'tl_class'=>'clr')
		),
		'banner_views_until' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_views_until'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'explanation'	          => 'banner_help',
			'sql'                     => "varchar(10) NOT NULL default ''",
			'eval'                    => array('nospace'=>true, 'maxlength'=>10, 'rgxp'=>'digit', 'helpwizard'=>true, 'tl_class'=>'w50')
		),
		'banner_clicks_until' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_clicks_until'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'explanation'	          => 'banner_help',
			'sql'                     => "varchar(10) NOT NULL default ''",
			'eval'                    => array('nospace'=>true, 'maxlength'=>10, 'rgxp'=>'digit', 'helpwizard'=>true, 'tl_class'=>'w50')
		),
		'banner_domain' => array
		(
	        'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_domain'],
	        'inputType'               => 'text',
	        'explanation'	          => 'banner_help',
	        'sql'                     => "varchar(255) NOT NULL default ''",
	        'eval'                    => array('mandatory'=>false, 'maxlength'=>255, 'helpwizard'=>true)
		),
		'banner_cssid' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_cssid'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('multiple'=>true, 'size'=>2),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
	)
);

