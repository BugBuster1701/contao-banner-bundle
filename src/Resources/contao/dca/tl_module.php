<?php

/**
 * Contao Open Source CMS, Copyright (C) 2005-2017 Leo Feyer
 * 
 * Modul Banner - Backend DCA tl_module
 *
 * This file modifies the data container array of table tl_module.
 *
 * @copyright  Glen Langer 2007..2017
 * @author     Glen Langer
 * @license    LGPL
 */

/**
 * Load tl_page language definitions
 */
\System::loadLanguageFile('tl_page');  //wegen banner_redirect

/**
 * Add a palette to tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['palettes']['banner'] = 'name,type,headline;banner_hideempty,banner_firstview;banner_categories,banner_template;banner_redirect;{expert_legend:hide},protected,guests,banner_useragent,cssID';

/**
 * Add fields to tl_module
 */ 
$GLOBALS['TL_DCA']['tl_module']['fields']['banner_hideempty'] = 
[
	'label'         => &$GLOBALS['TL_LANG']['tl_module']['banner_hideempty'],
	'exclude'       => true,
	'inputType'     => 'checkbox',
    'sql'           => "char(1) NOT NULL default '0'"
];
$GLOBALS['TL_DCA']['tl_module']['fields']['banner_firstview'] = 
[
    'label'         => &$GLOBALS['TL_LANG']['tl_module']['banner_firstview'],
    'exclude'       => true,
    'inputType'     => 'checkbox',
    'sql'           => "char(1) NOT NULL default '0'"
];
$GLOBALS['TL_DCA']['tl_module']['fields']['banner_categories'] = 
[
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['banner_categories'],
	'exclude'                 => true,
	'inputType'               => 'select',
	'foreignKey'              => 'tl_banner_category.title',
    'sql'                     => "varchar(255) NOT NULL default ''",
	'eval'                    => ['multiple'=>false, 'mandatory'=>true, 'tl_class'=>'w50']
];
$GLOBALS['TL_DCA']['tl_module']['fields']['banner_template'] = 
[
    'label'                   => &$GLOBALS['TL_LANG']['tl_module']['banner_template'],
    'default'                 => 'mod_banner_list_all',
    'exclude'                 => true,
    'inputType'               => 'select',
    'options_callback'        => ['BugBuster\Banner\DcaModuleBanner', 'getBannerTemplates'],
    'sql'                     => "varchar(32) NOT NULL default ''",
    'eval'                    => ['tl_class'=>'w50 w50h']
];
$GLOBALS['TL_DCA']['tl_module']['fields']['banner_redirect'] = 
[
	'label'                   => &$GLOBALS['TL_LANG']['tl_page']['redirect'],
	'default'                 => 'temporary',
	'exclude'                 => true,
	'inputType'               => 'select',
	'options'                 => ['permanent', 'temporary'],
	'reference'               => &$GLOBALS['TL_LANG']['tl_page'],
    'sql'                     => "varchar(32) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['banner_useragent'] = 
[
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['banner_useragent'],
	'inputType'               => 'text',
	'search'                  => true,
	'explanation'	          => 'banner_help',
    'sql'                     => "varchar(64) NOT NULL default ''",
	'eval'                    => ['mandatory'=>false, 'maxlength'=>64, 'helpwizard'=>true]
];
