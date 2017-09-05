<?php 

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2012 Leo Feyer
 *
 * @link http://www.contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 * 
 * Modul Banner - Backend DCA tl_banner_category
 *
 * This is the data container array for table tl_banner_category.
 *
 * PHP version 5
 * @copyright  Glen Langer 2007..2015
 * @author     Glen Langer 
 * @package    Banner
 * @license    LGPL
 */

/**
 * Table tl_banner_category 
 */
$GLOBALS['TL_DCA']['tl_banner_category'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'ctable'                      => array('tl_banner'),
		'switchToEdit'                => true,
		'enableVersioning'            => true,
        'sql' => array
        (
            'keys' => array
            (
                'id'    => 'primary'
            )
        ),
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 1,
			'fields'                  => array('title'),
			'flag'                    => 1,
			'panelLayout'             => 'search,limit'
		),
		'label' => array
		(
			//'fields'                  => array('title','banner_template','banner_protected'),
			//'format'                  => '%s <br /><span style="color:#b3b3b3;">[%s]<br />[%s]</span>'
			'fields'                  => array('tag'),
			'format'                  => '%s',
			'label_callback'		  => array('BugBuster\Banner\DcaBannerCategory', 'labelCallback'),
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
				'label'               => &$GLOBALS['TL_LANG']['tl_banner_category']['edit'],
				'href'                => 'table=tl_banner',
				'icon'                => 'edit.gif',
				'attributes'          => 'class="contextmenu"'
			),
			'editheader' => array
			(
		        'label'               => &$GLOBALS['TL_LANG']['tl_banner_category']['editheader'],
		        'href'                => 'act=edit',
		        'icon'                => 'header.gif',
		        'attributes'          => 'class="edit-header"'
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_banner_category']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.gif'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_banner_category']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['tl_banner_category']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_banner_category']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			),
			'stat' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_banner_category']['stat'],
				'href'                => 'do=bannerstat',
				'icon'                => 'bundles/bugbusterbanner/iconBannerStat.gif'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
	    '__selector__'                => array('banner_default', 'banner_protected', 'banner_numbers', 'banner_stat_protected'), 
		'default'                     => '{title_legend},title;{default_legend:hide},banner_default;{number_legend:hide},banner_numbers;{protected_legend:hide},banner_protected;{protected_stat_legend:hide},banner_stat_protected;{banner_expert_legend:hide},banner_expert_debug_tag,banner_expert_debug_helper,banner_expert_debug_image,banner_expert_debug_referrer'
	),
	// Subpalettes
	'subpalettes' => array
	(
		'banner_default'              => 'banner_default_name,banner_default_url,banner_default_image,banner_default_target',
		'banner_protected'            => 'banner_groups',
		'banner_numbers'              => 'banner_limit,banner_random',
		'banner_stat_protected'       => 'banner_stat_groups,banner_stat_admins',      
	),

	// Fields
	'fields' => array
	(
    	'id' => array
    	(
    	    'sql'                     => "int(10) unsigned NOT NULL auto_increment"
    	),
    	'tstamp' => array
    	(
    	    'sql'                     => "int(10) unsigned NOT NULL default '0'"
    	),
		'title' 					  => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner_category']['title'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'sql'                     => "varchar(60) NOT NULL default ''",
			'eval'                    => array('mandatory'=>true, 'maxlength'=>60, 'tl_class'=>'w50')
		),
		'banner_default'              => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner_category']['banner_default'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'sql'                     => "char(1) NOT NULL default ''",
			'eval'                    => array('submitOnChange'=>true)
		),
		'banner_default_name'         => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner_category']['banner_default_name'],
			'inputType'               => 'text',
			'search'                  => true,
			'sql'                     => "varchar(64) NOT NULL default ''",
			'eval'                    => array('mandatory'=>false, 'maxlength'=>64, 'tl_class'=>'w50')
		),
		'banner_default_url'		  => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner_category']['banner_default_url'],
			'inputType'               => 'text',
			'explanation'	          => 'banner_help',
			'sql'                     => "varchar(128) NOT NULL default ''",
			'eval'                    => array('mandatory'=>false, 'maxlength'=>128, 'tl_class'=>'w50')
		),
		'banner_default_image'        => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner_category']['banner_default_image'],
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			//'sql'                     => "varchar(255) NOT NULL default ''",
			'sql'                     => "binary(16) NULL",
			'eval'                    => array('mandatory'=>true, 'files'=>true, 'filesOnly'=>true, 'fieldType'=>'radio', 'extensions'=>'jpg,jpe,gif,png', 'maxlength'=>255, 'helpwizard'=>false, 'tl_class'=>'clr')
		),
		'banner_default_target'		  => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner_category']['banner_default_target'],
			'exclude'                 => true,
			'sql'                     => "char(1) NOT NULL default ''",
			'inputType'               => 'checkbox'
		),
		'banner_numbers'			  => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner_category']['banner_numbers'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'sql'                     => "char(1) NOT NULL default ''",
			'eval'                    => array('submitOnChange'=>true)
		),
		'banner_random'				  => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner_category']['banner_random'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'banner_limit'				  => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner_category']['banner_limit'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
			'eval'                    => array('rgxp'=>'digit', 'nospace'=>true, 'maxlength'=>10)
		),
		'banner_protected'            => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner_category']['banner_protected'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'sql'                     => "char(1) NOT NULL default ''",
			'eval'                    => array('submitOnChange'=>true)
		),
		'banner_groups'               => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner_category']['banner_groups'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'sql'                     => "varchar(255) NOT NULL default ''",
			'eval'                    => array('multiple'=>true)
		),
		'banner_stat_protected'       => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner_category']['banner_stat_protected'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'sql'                     => "char(1) NOT NULL default ''",
			'eval'                    => array('submitOnChange'=>true)
		),
		'banner_stat_groups'          => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner_category']['banner_stat_groups'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_user_group.name',
			'sql'                     => "varchar(255) NOT NULL default ''",
			'eval'                    => array('multiple'=>true)
		),
		'banner_stat_admins' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner_category']['banner_stat_admins'],
	        'inputType'               => 'checkbox',
			'eval'                    => array('disabled'=>true),
			'load_callback' => array
			(
			    array('BugBuster\Banner\DcaBannerCategory', 'getAdminCheckbox')
			)
		),
		'banner_expert_debug_tag'=> array
		(
		    'label'					  => &$GLOBALS['TL_LANG']['tl_banner_category']['banner_expert_debug_tag'],
		    'inputType'               => 'checkbox',
		    'sql'                     => "char(1) NOT NULL default ''",
		    'eval'                    => array('mandatory'=>false, 'helpwizard'=>false)
		),
		'banner_expert_debug_helper'=> array
		(
		    'label'					  => &$GLOBALS['TL_LANG']['tl_banner_category']['banner_expert_debug_helper'],
		    'inputType'               => 'checkbox',
		    'sql'                     => "char(1) NOT NULL default ''",
		    'eval'                    => array('mandatory'=>false, 'helpwizard'=>false)
		),
		'banner_expert_debug_image'=> array
		(
		    'label'					  => &$GLOBALS['TL_LANG']['tl_banner_category']['banner_expert_debug_image'],
		    'inputType'               => 'checkbox',
		    'sql'                     => "char(1) NOT NULL default ''",
		    'eval'                    => array('mandatory'=>false, 'helpwizard'=>false)
		),
		'banner_expert_debug_referrer'=> array
		(
		    'label'					  => &$GLOBALS['TL_LANG']['tl_banner_category']['banner_expert_debug_referrer'],
		    'inputType'               => 'checkbox',
		    'sql'                     => "char(1) NOT NULL default ''",
		    'eval'                    => array('mandatory'=>false, 'helpwizard'=>false)
		),
		'banner_expert_debug_logic'=> array
		(
		    'label'					  => &$GLOBALS['TL_LANG']['tl_banner_category']['banner_expert_debug_logic'],
		    'inputType'               => 'checkbox',
		    'sql'                     => "char(1) NOT NULL default ''",
		    'eval'                    => array('mandatory'=>false, 'helpwizard'=>false)
		)
	)
);
