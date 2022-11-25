<?php
/**
 * Extension for Contao Open Source CMS, Copyright (C) 2005-2017 Leo Feyer
 *
 * BannerTemplate - Frontend Helper Class
 *
 * @copyright  Glen Langer 2017 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @licence    LGPL
 * @filesource
 * @see	       https://github.com/BugBuster1701/contao-banner-bundle
 */

namespace BugBuster\Banner;

use BugBuster\Banner\BannerHelper;

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
            $domain = \Environment::get('base');
            $objParent = \PageModel::findWithDetails($objBanners->banner_jumpTo);
            if ($objParent !== null) // is null when page not exist anymore
            {
                if ($objParent->domain != '')
                {
                    $domain = (\Environment::get('ssl') ? 'https://' : 'http://') . $objParent->domain . TL_PATH . '/';
                }
                // old $objBanners->banner_url = $domain . \Controller::generateFrontendUrl($objParent->row(), '', $objParent->language);
                $objBanners->banner_url = $domain . BannerHelper::frontendUrlGenerator($objParent->row(), null, $objParent->language);
            }
        }

        //$arrImageSize[0]  eigene Breite
        //$arrImageSize[1]  eigene Höhe
        //$arrImageSize[3]  Breite und Höhe in der Form height="yyy" width="xxx"
        //$arrImageSize[2]
        // 1 = GIF, 2 = JPG/JPEG, 3 = PNG
        // 4 = SWF, 13 = SWC (zip-like swf file)
        // 5 = PSD, 6 = BMP, 7 = TIFF(intel byte order), 8 = TIFF(motorola byte order)
        // 9 = JPC, 10 = JP2, 11 = JPX, 12 = JB2, 13 = SWC, 14 = IFF
        // 18 = WEBP
        switch ($arrImageSize[2])
        {
            case 1:// GIF
            case 2:// JPG
            case 3:// PNG
            case 18: // WEBP
                $arrBanners[] = 
                [
                'banner_key'     => 'bid',
                'banner_wrap_id'    => $banner_cssID,
                'banner_wrap_class' => $banner_class,
                'banner_id'      => $objBanners->id,
                'banner_name'    => \StringUtil::specialchars(ampersand($objBanners->banner_name)),
                'banner_url'     => $objBanners->banner_url,
                'banner_target'  => $banner_target,
                'banner_comment' => \StringUtil::specialchars(ampersand($objBanners->banner_comment)),
                'src'            => \StringUtil::specialchars(ampersand($FileSrc)), //specialchars(ampersand($this->urlEncode($FileSrc))),
                'alt'            => \StringUtil::specialchars(ampersand($objBanners->banner_name)),
                'size'           => $arrImageSize[3],
                'banner_pic'     => true,
                'banner_flash'   => false,
                'banner_text'    => false,
                'banner_empty'   => false,
                'banner_video'   => false,
                'picture'        => $picture
                ];
                break;
            default:
                $arrBanners[] = 
                [
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
                ];
                break;
        }//switch

        return $arrBanners;
    }
}
