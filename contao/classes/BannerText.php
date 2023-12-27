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

namespace BugBuster\Banner;

use Contao\Environment;
use Contao\PageModel;
use Contao\StringUtil;

/**
 * Class BannerText
 */
class BannerText
{
	protected $objBanners;

	protected $banner_cssID;

	protected $banner_class;

	public function __construct($objBanners, $banner_cssID, $banner_class)
	{
		$this->objBanners   = $objBanners;
		$this->banner_cssID = $banner_cssID;
		$this->banner_class = $banner_class;
	}

	public function generateTemplateData()
	{
		$banner_target = ($this->objBanners->banner_target == '1') ? '' : ' target="_blank"';

		// Banner Seite als Ziel?
		if ($this->objBanners->banner_jumpTo > 0)
		{
			$domain = Environment::get('base');
			$objParent = PageModel::findWithDetails($this->objBanners->banner_jumpTo);
			if ($objParent !== null) // is null when page not exist anymore
			{
				if ($objParent->domain != '')
				{
					// $domain = (\Contao\Environment::get('ssl') ? 'https://' : 'http://') . $objParent->domain . TL_PATH . '/';
					$domain = (Environment::get('ssl') ? 'https://' : 'http://') . $objParent->domain . '/';
				}
				// old $this->objBanners->banner_url = $domain . \Controller::generateFrontendUrl($objParent->row(), '', $objParent->language);
				$this->objBanners->banner_url = $domain . BannerHelper::frontendUrlGenerator($objParent->row(), null, $objParent->language);
			}
		}

		// Kurz URL (nur Domain)
		$treffer = parse_url(BannerHelper::decodePunycode($this->objBanners->banner_url)); // #79
		$banner_url_kurz = $treffer['host'];
		if (isset($treffer['port']))
		{
			$banner_url_kurz .= ':' . $treffer['port'];
		}

		$arrBanners[] =
						array(
							'banner_key'     => 'bid',
							'banner_wrap_id'    => $this->banner_cssID,
							'banner_wrap_class' => $this->banner_class,
							'banner_id'      => $this->objBanners->id,
							'banner_name'    => StringUtil::specialchars(StringUtil::ampersand($this->objBanners->banner_name)),
							'banner_url'     => $this->objBanners->banner_url,
							'banner_url_kurz'=> $banner_url_kurz,
							'banner_target'  => $banner_target,
							'banner_comment' => StringUtil::ampersand(nl2br($this->objBanners->banner_comment)),
							'banner_pic'     => false,
							'banner_flash'   => false,
							'banner_text'    => true,
							'banner_video'   => false,
							'banner_empty'   => false	// issues 733
						);

		return $arrBanners;
	}
}
