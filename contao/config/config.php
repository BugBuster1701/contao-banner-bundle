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

use Contao\System;
use Symfony\Component\HttpFoundation\Request;

define('BANNER_VERSION', '1.8');
define('BANNER_BUILD', '2');

/*
 * -------------------------------------------------------------------------
 * BACK END MODULES
 * -------------------------------------------------------------------------
 */
$GLOBALS['BE_MOD']['content']['banner'] =
array(
	'tables'     => array('tl_banner_category', 'tl_banner'),
	'icon'       => 'bundles/bugbusterbanner/iconBanner.gif',
	'stylesheet' => 'bundles/bugbusterbanner/mod_banner_be.css'
);

$GLOBALS['BE_MOD']['system']['bannerstat'] =
array(
	'callback'   => 'BugBuster\BannerStatistics\ModuleBannerStatistics',
	'icon'       => 'bundles/bugbusterbanner/iconBannerStat.gif',
	'stylesheet' => 'bundles/bugbusterbanner/mod_banner_be.css'
);

/*
 * -------------------------------------------------------------------------
 * FRONT END MODULES
 * -------------------------------------------------------------------------
 */
$GLOBALS['FE_MOD']['miscellaneous']['banner'] = 'BugBuster\Banner\ModuleBanner';

/*
 * -------------------------------------------------------------------------
 * HOOKS
 * -------------------------------------------------------------------------
 */
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = array('BugBuster\Banner\BannerInsertTag', 'replaceInsertTagsBanner');

/*
 * CSS
 */
if (
	System::getContainer()->get('contao.routing.scope_matcher')
	->isBackendRequest(System::getContainer()->get('request_stack')
	->getCurrentRequest() ?? Request::create(''))
) {
	$GLOBALS['TL_CSS'][] = 'bundles/bugbusterbanner/backend.css';
}
