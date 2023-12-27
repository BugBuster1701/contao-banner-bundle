<?php

/*
 * This file is part of a BugBuster Contao Bundle.
 *
 * @copyright  Glen Langer 2023 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Contao Banner Bundle
 * @link       https://github.com/BugBuster1701/contao-banner-bundle
 *
 * @license    LGPL-3.0-or-later
 */

use Contao\System;

/*
 * Load tl_page language definitions
 */
System::loadLanguageFile('tl_page');  // wegen banner_redirect

/*
 * Add a palette to tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['palettes']['banner'] = 'name,type,headline;banner_hideempty,banner_firstview;banner_categories,banner_template;banner_redirect;{expert_legend:hide},protected,guests,banner_useragent,cssID';

/*
 * Add fields to tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['fields']['banner_hideempty'] =
array(
	'label'         => &$GLOBALS['TL_LANG']['tl_module']['banner_hideempty'],
	'exclude'       => true,
	'inputType'     => 'checkbox',
	'sql'           => "char(1) NOT NULL default '0'"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['banner_firstview'] =
array(
	'label'         => &$GLOBALS['TL_LANG']['tl_module']['banner_firstview'],
	'exclude'       => true,
	'inputType'     => 'checkbox',
	'sql'           => "char(1) NOT NULL default '0'"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['banner_categories'] =
array(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['banner_categories'],
	'exclude'                 => true,
	'inputType'               => 'select',
	'foreignKey'              => 'tl_banner_category.title',
	'sql'                     => "varchar(255) NOT NULL default ''",
	'eval'                    => array('multiple'=>false, 'mandatory'=>true, 'tl_class'=>'w50')
);
$GLOBALS['TL_DCA']['tl_module']['fields']['banner_template'] =
array(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['banner_template'],
	'default'                 => 'mod_banner_list_all',
	'exclude'                 => true,
	'inputType'               => 'select',
	'options_callback'        => array('BugBuster\Banner\DcaModuleBanner', 'getBannerTemplates'),
	'sql'                     => "varchar(32) NOT NULL default ''",
	'eval'                    => array('tl_class'=>'w50 w50h')
);
$GLOBALS['TL_DCA']['tl_module']['fields']['banner_redirect'] =
array(
	'label'                   => &$GLOBALS['TL_LANG']['tl_page']['redirect'],
	'default'                 => 'temporary',
	'exclude'                 => true,
	'inputType'               => 'select',
	'options'                 => array('permanent', 'temporary'),
	'reference'               => &$GLOBALS['TL_LANG']['tl_page'],
	'sql'                     => "varchar(32) NOT NULL default ''",
);
$GLOBALS['TL_DCA']['tl_module']['fields']['banner_useragent'] =
array(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['banner_useragent'],
	'inputType'               => 'text',
	'search'                  => true,
	'explanation'	          => 'banner_help',
	'sql'                     => "varchar(64) NOT NULL default ''",
	'eval'                    => array('mandatory'=>false, 'maxlength'=>64, 'helpwizard'=>true)
);
