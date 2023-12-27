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
use Contao\System;

class DcaBannerCategory extends Backend
{
	public function labelCallback($arrRow)
	{
		$label_1 = $arrRow['title'];
		$version_warning = '';

		$bpc = $GLOBALS['TL_LANG']['tl_banner_category']['banner_protected_catagory'];
		if (!empty($arrRow['banner_protected']) && \strlen($arrRow['banner_groups']))
		{
			$label_2 = '<img height="16" width="14" alt="' . $bpc . '" title="' . $bpc . '" src="bundles/bugbusterbanner/default/protect_.gif">';
		// $label_2 = " (".$bpc.")"; // ab Contao 3.1 fehlt das protect_.gif :-(
		}
		else
		{
			$label_2 = '';
		}

		return $label_1 . ' ' . $label_2 . $version_warning;
	}

	public function getAdminCheckbox($varValue)
	{
		return '1';
	}

	public function fieldLabelCallback($dc)
	{
		if (!$this->supportsWebp())
		{
			$GLOBALS['TL_LANG']['tl_banner_category']['banner_default_image'][1] .= ' (' . $GLOBALS['TL_LANG']['tl_banner_category']['formatsWebpNotSupported'] . ')';
		}

		return '';
	}

	/**
	 * Check if WEBP is supported
	 *
	 * @return boolean
	 */
	private function supportsWebp()
	{
		$imagine = System::getContainer()->get('contao.image.imagine');
		$imagineclass = \get_class($imagine);

		if ($imagineclass == "Imagine\\Imagick\\Imagine")
		{
			return \in_array('WEBP', \Imagick::queryFormats('WEBP'), true);
		}

		if ($imagineclass == "Imagine\\Gmagick\\Imagine")
		{
			return \in_array('WEBP', (new \Gmagick())->queryformats('WEBP'), true);
		}

		if ($imagineclass == "Imagine\\Gd\\Imagine")
		{
			return \function_exists('imagewebp');
		}

		return false;
	}
}
