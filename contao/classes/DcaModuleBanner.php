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

/**
 * Run in a custom namespace, so the class can be replaced
 */

namespace BugBuster\Banner;

use Contao\Backend;

/**
 * DCA Helper Class DcaModuleBanner
 *
 * @copyright  Glen Langer 2012..2022 <http://contao.ninja>
 */
class DcaModuleBanner extends Backend
{
	public function getBannerTemplates()
	{
		return $this->getTemplateGroup('mod_banner_list_');
	}
}
