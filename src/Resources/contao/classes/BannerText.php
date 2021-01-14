<?php
/**
 * Extension for Contao Open Source CMS, Copyright (C) 2005-2017 Leo Feyer
 *
 * BannerText - Frontend Helper Class
 *
 * @copyright  Glen Langer 2017 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @licence    LGPL
 * @filesource
 * @see	       https://github.com/BugBuster1701/contao-banner-bundle
 */

namespace BugBuster\Banner;

use BugBuster\Banner\BannerHelper;

/**
 * Class BannerText
 *
 * @copyright  Glen Langer 2017 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @license    LGPL
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
            $domain = \Environment::get('base');
            $objParent = \PageModel::findWithDetails($this->objBanners->banner_jumpTo);
            if ($objParent !== null) // is null when page not exist anymore
            {
                if ($objParent->domain != '')
                {
                    $domain = (\Environment::get('ssl') ? 'https://' : 'http://') . $objParent->domain . TL_PATH . '/';
                }
                //old $this->objBanners->banner_url = $domain . \Controller::generateFrontendUrl($objParent->row(), '', $objParent->language);
                $this->objBanners->banner_url = $domain . BannerHelper::frontendUrlGenerator($objParent->row(), null, $objParent->language);
            }
        }

        // Kurz URL (nur Domain)
        $treffer = parse_url(BannerHelper::decodePunycode($this->objBanners->banner_url)); // #79
        $banner_url_kurz = $treffer['host'];
        if (isset($treffer['port']))
        {
            $banner_url_kurz .= ':'.$treffer['port'];
        }

        $arrBanners[] = array
                        (
                            'banner_key'     => 'bid',
                            'banner_wrap_id'    => $this->banner_cssID,
                            'banner_wrap_class' => $this->banner_class,
                            'banner_id'      => $this->objBanners->id,
                            'banner_name'    => \StringUtil::specialchars(ampersand($this->objBanners->banner_name)),
                            'banner_url'     => $this->objBanners->banner_url,
                            'banner_url_kurz'=> $banner_url_kurz,
                            'banner_target'  => $banner_target,
                            'banner_comment' => ampersand(nl2br($this->objBanners->banner_comment)),
                            'banner_pic'     => false,
                            'banner_flash'   => false,
                            'banner_text'    => true,
                            'banner_empty'   => false	// issues 733
                        );

        return $arrBanners;
    }
}
