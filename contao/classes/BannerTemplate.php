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

namespace BugBuster\Banner;

use Contao\Environment;
use Contao\PageModel;
use Contao\StringUtil;

/**
 * Class BannerTemplate
 */
class BannerTemplate
{
	public static function generateTemplateData($arrImageSize, $FileSrc, $picture, $objBanners, $banner_cssID, $banner_class)
	{
		$banner_target = ($objBanners->banner_target == '1') ? '' : ' target="_blank"';

		if (\strlen($objBanners->banner_comment) > 1)
		{
			$banner_comment_pos = strpos($objBanners->banner_comment, "\n", 1);
			if ($banner_comment_pos !== false)
			{
				$objBanners->banner_comment = substr($objBanners->banner_comment, 0, $banner_comment_pos);
			}
		}

		// Banner Seite als Ziel?
		if ($objBanners->banner_jumpTo > 0)
		{
			$domain = Environment::get('base');
			$objParent = PageModel::findWithDetails($objBanners->banner_jumpTo);
			if ($objParent !== null) // is null when page not exist anymore
			{
				if ($objParent->domain != '')
				{
					// $domain = (\Contao\Environment::get('ssl') ? 'https://' : 'http://') . $objParent->domain . TL_PATH . '/';
					$domain = (Environment::get('ssl') ? 'https://' : 'http://') . $objParent->domain . '/';
				}
				// old $objBanners->banner_url = $domain . \Controller::generateFrontendUrl($objParent->row(), '', $objParent->language);
				$objBanners->banner_url = $domain . BannerHelper::frontendUrlGenerator($objParent->row(), null, $objParent->language);
			}
		}

		// $arrImageSize[0]  eigene Breite
		// $arrImageSize[1]  eigene Höhe
		// $arrImageSize[3]  Breite und Höhe in der Form height="yyy" width="xxx"
		// $arrImageSize[2]
		// 1 = GIF, 2 = JPG/JPEG, 3 = PNG
		// 4 = SWF, 13 = SWC (zip-like swf file)
		// 5 = PSD, 6 = BMP, 7 = TIFF(intel byte order), 8 = TIFF(motorola byte order)
		// 9 = JPC, 10 = JP2, 11 = JPX, 12 = JB2, 13 = SWC, 14 = IFF
		// 18 = WEBP, 19 = AVIF
		switch ($arrImageSize[2])
		{
			case 1:// GIF
			case 2:// JPG
			case 3:// PNG
			case 18: // WEBP
			case 19: // AVIF
				$arrBanners[] =
				array(
					'banner_key'     => 'bid',
					'banner_wrap_id'    => $banner_cssID,
					'banner_wrap_class' => $banner_class,
					'banner_id'      => $objBanners->id,
					'banner_name'    => StringUtil::specialchars(StringUtil::ampersand($objBanners->banner_name)),
					'banner_url'     => $objBanners->banner_url,
					'banner_target'  => $banner_target,
					'banner_comment' => StringUtil::specialchars(StringUtil::ampersand($objBanners->banner_comment)),
					'src'            => StringUtil::specialchars(StringUtil::ampersand($FileSrc)), // specialchars(\Contao\StringUtil::ampersand($this->urlEncode($FileSrc))),
					'alt'            => StringUtil::specialchars(StringUtil::ampersand($objBanners->banner_name)),
					'size'           => $arrImageSize[3],
					'banner_pic'     => true,
					'banner_flash'   => false,
					'banner_text'    => false,
					'banner_empty'   => false,
					'banner_video'   => false,
					'picture'        => $picture
				);
				break;
			default:
				$arrBanners[] =
				array(
					'banner_key'     => 'bid',
					'banner_wrap_id'    => $banner_cssID,
					'banner_wrap_class' => $banner_class,
					'banner_id'      => 0,
					'banner_name'    => '',
					'banner_url'     => '',
					'banner_target'  => '',
					'banner_comment' => '',
					'src'            => '',
					'alt'            => '',
					'size'           => '',
					'banner_pic'     => true,
					'banner_video'   => false,
				);
				break;
		}// switch

		return $arrBanners;
	}
}
