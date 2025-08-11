<?php

/*
 * This file is part of a BugBuster Contao Bundle.
 *
 * @copyright  Glen Langer 2025 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Contao Banner Bundle
 * @link       https://github.com/BugBuster1701/contao-banner-bundle
 *
 * @license    LGPL-3.0-or-later
 */

use Contao\BackendUser;
use Contao\DC_Table;
use Contao\System;

/*
 * Table tl_banner
 */
$GLOBALS['TL_DCA']['tl_banner'] =
array(
	// Config
	'config' => array(
		'dataContainer'               => DC_Table::class,
		'ptable'                      => 'tl_banner_category',
		'enableVersioning'            => true,
		'sql' => array(
			'keys' => array(
				'id'    => 'primary',
				'pid'   => 'index'
			)
		),
	),

	// List
	'list' => array(
		'sorting' => array(
			'mode'                    => 4,
			'filter'                  => true,
			'fields'                  => array('sorting'),
			'panelLayout'             => 'filter;search,limit',
			'headerFields'            => array('title', 'banner_protected', 'tstamp', 'id'),
			'header_callback'         => array('BugBuster\Banner\DcaBanner', 'addHeader'),
			'child_record_callback'   => array('BugBuster\Banner\DcaBanner', 'listBanner')
		),
		'global_operations' => array(
			'all' => array(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset();"'
			)
		),
		'operations' => array(
			'edit' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_banner']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.gif'
			),
			'copy' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_banner']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.svg'
			),
			'delete' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_banner']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if (!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\')) return false; Backend.getScrollOffset();"'
			),
			'toggle' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_banner']['toggle'],
				'href'                => 'act=toggle&amp;field=banner_published',
				'icon'                => 'visible.svg',
				// 'attributes'          => 'onclick="Backend.getScrollOffset(); return AjaxRequest.toggleVisibility(this, %s);"',
				'button_callback'     => array('BugBuster\Banner\DcaBanner', 'toggleIcon')
			),
			'show' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_banner']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			)
		)
	),

	// Palettes
	'palettes' => array(
		'__selector__'                => array('banner_type', 'banner_until'),
		'default'                     => 'banner_type',
		'banner_image'                => 'banner_type;{title_legend},banner_name,banner_weighting;{comment_legend},banner_comment;banner_overwritemeta;{destination_legend},banner_url,banner_jumpTo,banner_target;{image_legend},banner_image,banner_imgSize;{filter_legend:hide},banner_domain;{expert_legend:hide},banner_cssid;{publish_legend},banner_published,banner_start,banner_stop,banner_until',
		'banner_image_extern'         => 'banner_type;{title_legend},banner_name,banner_weighting;{comment_legend},banner_comment;{destination_legend},banner_url,banner_target;{image_legend},banner_image_extern,banner_imgSize;{filter_legend:hide},banner_domain;{expert_legend:hide},banner_cssid;{publish_legend},banner_published,banner_start,banner_stop,banner_until',
		'banner_text'                 => 'banner_type;{title_legend},banner_name,banner_weighting;{comment_legend},banner_comment;{destination_legend},banner_url,banner_jumpTo,banner_target;{filter_legend:hide},banner_domain;{expert_legend:hide},banner_cssid;{publish_legend},banner_published,banner_start,banner_stop,banner_until',
		'banner_video'                => 'banner_type;{title_legend},banner_name,banner_weighting;{video_source_legend},banner_playerSRC;{player_legend},banner_playerSize,banner_playerStart,banner_playerStop;{poster_legend:hide},banner_posterSRC;{comment_legend},banner_comment;{destination_legend},banner_url,banner_jumpTo,banner_target;{filter_legend:hide},banner_domain;{expert_legend:hide},banner_cssid;{publish_legend},banner_published,banner_start,banner_stop,banner_until'
	),
	// Subpalettes
	'subpalettes' => array(
		'banner_until'                => 'banner_views_until,banner_clicks_until'
	),

	// Fields
	'fields' => array(
		'id' => array(
			'sql'           => "int(10) unsigned NOT NULL auto_increment"
		),
		'pid' => array(
			'sql'           => "int(10) unsigned NOT NULL default '0'"
		),
		'sorting' => array(
			'sql'           => "int(10) unsigned NOT NULL default '0'"
		),
		'tstamp' => array(
			'sql'           => "int(10) unsigned NOT NULL default '0'"
		),
		'banner_type' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_type'],
			'default'                 => 'default',
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'select',
			'options'                 => array('default', 'banner_image', 'banner_image_extern', 'banner_text', 'banner_video'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_banner_type'],
			'sql'                     => "varchar(32) NOT NULL default 'banner_image'",
			'eval'                    => array('helpwizard'=>false, 'submitOnChange'=>true)
		),
		'banner_name' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_name'],
			'inputType'               => 'text',
			'search'                  => true,
			'explanation'	          => 'banner_help',
			'sql'                     => "varchar(64) NOT NULL default ''",
			'eval'                    => array('mandatory'=>false, 'maxlength'=>64, 'helpwizard'=>true, 'tl_class'=>'w50')
		),
		'banner_weighting' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_weighting'],
			'default'                 => '2',
			'inputType'               => 'select',
			'options'                 => array('1', '2', '3'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_banner'],
			'explanation'	          => 'banner_help',
			'sql'                     => "tinyint(1) NOT NULL default '2'",
			'eval'                    => array('mandatory'=>false, 'maxlength'=>1, 'rgxp'=>'prcnt', 'helpwizard'=>true, 'tl_class'=>'w50')
		),
		'banner_overwritemeta' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_overwriteMeta'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true, 'tl_class'=>'clr'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'banner_url' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_url'],
			'inputType'               => 'text',
			'explanation'	          => 'banner_help',
			'sql'                     => "varchar(255) NOT NULL default ''",
			'eval'                    => array('mandatory'=>false, 'rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>255, 'helpwizard'=>true)
		),
		'banner_jumpTo' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_jumpTo'],
			'exclude'                 => true,
			'inputType'               => 'pageTree',
			'explanation'             => 'banner_help',
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
			'eval'                    => array('fieldType'=>'radio', 'helpwizard'=>true)
		),
		'banner_target' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_target'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'banner_image' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_image'],
			'explanation'	          => 'banner_help',
			'inputType'               => 'fileTree',
			'sql'                     => "binary(16) NULL",
			'eval'                    => array('mandatory'=>true, 'files'=>true, 'filesOnly'=>true, 'fieldType'=>'radio', 'extensions'=>'jpg,jpe,jpeg,gif,png,webp,avif', 'maxlength'=>255, 'helpwizard'=>true),
			'xlabel' => array(
				array('BugBuster\Banner\DcaBanner', 'fieldLabelCallback')
			)
		),
		'banner_image_extern' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_image_extern'],
			'inputType'               => 'text',
			'explanation'	          => 'banner_help',
			'sql'                     => "varchar(255) NOT NULL default ''",
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>255, 'helpwizard'=>true)
		),
		'banner_imgSize' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_imgSize'],
			'exclude'                 => true,
			'inputType'               => 'imageSize',
			'options_callback' => static function () {
				return System::getContainer()->get('contao.image.sizes')->getOptionsForUser(BackendUser::getInstance());
			},
			// 'options'                 =>  array('contao.listener.image_size_options', '__invoke'), // will nicht so wie in contao/calendar-bundle/contao/dca/tl_calendar_feed.php
			// 'options'                 => \Contao\System::getContainer()->get('contao.listener.image_size_options')->__invoke(), //geht auch aber unschön
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'sql'                     => "varchar(255) NOT NULL default ''",
			'eval'                    => array('rgxp'=>'natural', 'includeBlankOption'=>true, 'nospace'=>true)
		),
		'banner_comment' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_comment'],
			'inputType'               => 'textarea',
			'explanation'             => 'banner_help',
			'sql'                     => "text NULL",
			'eval'                    => array('mandatory'=>false, 'preserveTags'=>true, 'helpwizard'=>true)
		),
		'banner_published' => array(
			// 'exclude'                 => true,
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_published'],
			'filter'                  => true,
			'sql'                     => "char(1) NOT NULL default ''",
			'inputType'               => 'checkbox',
			'toggle'                  => true
		),
		'banner_start' => array(
			'exclude'                 => true,
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_start'],
			'inputType'               => 'text',
			'explanation'	          => 'banner_help',
			'sql'                     => "varchar(10) NOT NULL default ''",
			'eval'                    => array('maxlength'=>20, 'rgxp'=>'datim', 'datepicker'=>true, 'helpwizard'=>true, 'tl_class'=>'w50 wizard')
		),
		'banner_stop' => array(
			'exclude'                 => true,
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_stop'],
			'inputType'               => 'text',
			'explanation'	          => 'banner_help',
			'sql'                     => "varchar(10) NOT NULL default ''",
			'eval'                    => array('maxlength'=>20, 'rgxp'=>'datim', 'datepicker'=>true, 'helpwizard'=>true, 'tl_class'=>'w50 wizard')
		),
		'banner_until'  => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_until'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'sql'                     => "char(1) NOT NULL default ''",
			'eval'                    => array('submitOnChange'=>true, 'tl_class'=>'clr')
		),
		'banner_views_until' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_views_until'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'explanation'	          => 'banner_help',
			'sql'                     => "varchar(10) NOT NULL default ''",
			'eval'                    => array('nospace'=>true, 'maxlength'=>10, 'rgxp'=>'digit', 'helpwizard'=>true, 'tl_class'=>'w50')
		),
		'banner_clicks_until' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_clicks_until'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'explanation'	          => 'banner_help',
			'sql'                     => "varchar(10) NOT NULL default ''",
			'eval'                    => array('nospace'=>true, 'maxlength'=>10, 'rgxp'=>'digit', 'helpwizard'=>true, 'tl_class'=>'w50')
		),
		'banner_domain' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_domain'],
			'inputType'               => 'text',
			'explanation'	          => 'banner_help',
			'sql'                     => "varchar(255) NOT NULL default ''",
			'eval'                    => array('mandatory'=>false, 'maxlength'=>255, 'helpwizard'=>true)
		),
		'banner_cssid' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_banner']['banner_cssid'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('multiple'=>true, 'size'=>2),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'banner_playerSRC' => array(
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array(
				'multiple'   => true,
				'fieldType'  => 'checkbox',
				'files'      => true,
				'mandatory'  => true,
				'extensions' => 'mp4,m4v,mov,wmv,webm,ogv',
			),
			'sql'                     => "blob NULL"
		),
		'banner_posterSRC' => array(
			'exclude'                 => true,
			'inputType'               => 'fileTree',
			'eval'                    => array('filesOnly'=>true, 'fieldType'=>'radio'),
			'sql'                     => "binary(16) NULL"
		),
		'banner_playerSize' => array(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('multiple'=>true, 'size'=>2, 'rgxp'=>'natural', 'nospace'=>true, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) COLLATE ascii_bin NOT NULL default ''"
		),
		'banner_playerStart' => array(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'natural', 'nospace'=>true, 'tl_class'=>'w50 clr'),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'banner_playerStop' => array(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'natural', 'nospace'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
	)
);
