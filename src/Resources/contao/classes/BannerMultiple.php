<?php
/**
 * Extension for Contao Open Source CMS, Copyright (C) 2005-2022 Leo Feyer
 *
 * BannerMultiple - Frontend Helper Class
 *
 * @copyright  Glen Langer 2022 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @licence    LGPL
 * @filesource
 * @see	       https://github.com/BugBuster1701/contao-banner-bundle
 */

namespace BugBuster\Banner;

use BugBuster\Banner\BannerCount;
use BugBuster\Banner\BannerImage;
use BugBuster\Banner\BannerLog;

/**
 * Class BannerMultiple
 *
 * @copyright  Glen Langer 2022 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @license    LGPL
 */
class BannerMultiple extends \Frontend
{
    /**
     * Banner intern
     * @var string
     */
    public const BANNER_TYPE_INTERN = 'banner_image';

    /**
     * Banner extern
     * @var string
     */
    public const BANNER_TYPE_EXTERN = 'banner_image_extern';

    /**
     * Banner text
     * @var string
     */
    public const BANNER_TYPE_TEXT   = 'banner_text';

    protected $arrCategoryValues  = [];
    protected $banner_template;
    protected $strTemplate;
    protected $Template;
    protected $arrAllBannersBasic = [];
    protected $BannerImage;

    public function __construct($arrCategoryValues, $banner_template, $strTemplate, $objTemplate, $arrAllBannersBasic)
    {
        $this->arrCategoryValues  = $arrCategoryValues;
        $this->banner_template    = $banner_template;
        $this->strTemplate        = $strTemplate;
        $this->Template           = $objTemplate;
        $this->arrAllBannersBasic = $arrAllBannersBasic;

        // Static URLs Controller::setStaticUrls
        $this->setStaticUrls();
    }

    public function getMultiBanner($module_id)
    {
        $arrResults = [];
        $boolFirstBanner = false;

        /* $this->arrCategoryValues[...]
         * banner_random
         * banner_limit         - 0 all, other:max
         */

        reset($this->arrAllBannersBasic); //sicher ist sicher

        BannerLog::writeLog(__METHOD__, __LINE__, 'arrAllBannersBasic: '. print_r($this->arrAllBannersBasic, true));
        //RandomBlocker entfernen falls möglich und nötig

        // einer muss mindestens übrig bleiben
        if (\count($this->arrAllBannersBasic) >1
            // bei Alle Banner anzeigen (0) nichts entfernen
            && $this->arrCategoryValues['banner_limit'] >0
            // nur wenn mehr Banner übrig als per limit festgelegt
            && (\count($this->arrAllBannersBasic) > $this->arrCategoryValues['banner_limit'])
        ) {
            $objBannerLogic = new BannerLogic();
            $intRandomBlockerID = $objBannerLogic->getRandomBlockerId($module_id);
            if (isset($this->arrAllBannersBasic[$intRandomBlockerID])) {
                unset($this->arrAllBannersBasic[$intRandomBlockerID]);
            }
            $objBannerLogic = null;
            unset($objBannerLogic);
        }

        if ($this->arrCategoryValues['banner_random'] == 1) {
            $this->shuffleAssoc($this->arrAllBannersBasic);
            BannerLog::writeLog(__METHOD__, __LINE__, 'arrAllBannersBasic shuffled: '. print_r($this->arrAllBannersBasic, true));
        }

        //wenn limit gesetzt, array arrAllBannersBasic dezimieren
        if ($this->arrCategoryValues['banner_limit'] >0) {
            $del = \count($this->arrAllBannersBasic) - $this->arrCategoryValues['banner_limit'];
            for ($i = 0; $i < $del; $i++) {
                array_pop($this->arrAllBannersBasic);
            }
        }

        //Rest soll nun angezeigt werden.
        //Schleife
        foreach ($this->arrAllBannersBasic as $banner_id => $banner_weigth) {
            unset($banner_weigth);
            $objBanners  = \Database::getInstance()
                                ->prepare(
                                    "SELECT
                                                TLB.*
                                           FROM
                                	            tl_banner AS TLB
                                           WHERE
                                                TLB.`id`=?"
                                )
                                ->limit(1)
                                ->execute($banner_id);
            $intRows = $objBanners->numRows;
            //Banner vorhanden?
            if ($intRows > 0) {
                $arrBanners = [];
                $objBanners->next();
                BannerHelper::$arrBannerSeen[] = $objBanners->id;
                //CSS-ID/Klasse(n) je Banner, für den wrapper
                $banner_cssID   = '';
                $banner_class   = '';
                $banner_classes = '';
                $_cssID = \StringUtil::deserialize($objBanners->banner_cssid);
                if (\is_array($_cssID)) {
                    if ($_cssID[0] != '') {
                        $banner_cssID   = ' id="banner_'.$_cssID[0].'"';
                    }
                    if ($_cssID[1] != '') {
                        $banner_classes = explode(" ", $_cssID[1]);
                        foreach ($banner_classes as $banner_classone) {
                            $banner_class .= ' banner_'.$banner_classone;
                        }
                    }
                }

                //den ersten Banner für den nächsten Aufruf blockieren
                if (false === $boolFirstBanner) {
                    $objBannerLogic = new BannerLogic();
                    $objBannerLogic->setRandomBlockerId($banner_id, $module_id);
                    $objBannerLogic = null;
                    unset($objBannerLogic);
                    $boolFirstBanner = true;
                }

                switch ($objBanners->banner_type) {
                    case self::BANNER_TYPE_INTERN:
                        $objBannerInternal = new BannerInternal($objBanners, $banner_cssID, $banner_class);
                        $objImageData = $objBannerInternal->generateImageData();

                        $FileSrc = $objImageData->FileSrc;
                        $picture = $objImageData->Picture;
                        $arrImageSize = $objImageData->ImageSize;

                        $arrBanners = $objBannerInternal->generateTemplateData($arrImageSize, $FileSrc, $picture);
                        $arrResults[] = $arrBanners[0];

                        //$this->arrBannerData = $arrBanners; //wird von setStatViewUpdate genutzt
                        $this->setStatViewUpdate($arrResults, $module_id, $objBanners->banner_useragent);

                        break;
                    case self::BANNER_TYPE_EXTERN:
                        $objBannerExternal = new BannerExternal($objBanners, $banner_cssID, $banner_class);
                        $objImageData = $objBannerExternal->generateImageData();

                        $FileSrc = $objImageData->FileSrc;
                        $picture = $objImageData->Picture;
                        $arrImageSize = $objImageData->ImageSize;

                        $arrBanners = $objBannerExternal->generateTemplateData($arrImageSize, $FileSrc, $picture);

                        $arrResults[] = $arrBanners[0];

                        //$this->arrBannerData = $arrBanners; //wird von setStatViewUpdate genutzt
                        $this->setStatViewUpdate($arrResults, $module_id, $objBanners->banner_useragent);

                        break;
                    case self::BANNER_TYPE_TEXT:
                        $arrImageSize = false;
                        break;
                }

                // Text Banner
                if ($objBanners->banner_type == 'banner_text') {
                    $objBannerText = new BannerText($objBanners, $banner_cssID, $banner_class);
                    $arrBanners = $objBannerText->generateTemplateData();

                    $arrResults[] = $arrBanners[0];

                    //$this->arrBannerData = $arrBanners; //wird von setStatViewUpdate genutzt
                    $this->setStatViewUpdate($arrResults, $module_id, $objBanners->banner_useragent);
                }//text banner

                // Video Banner
                if ($objBanners->banner_type == BannerVideo::BANNER_TYPE_VIDEO) {
                    $objBannerVideo = new BannerVideo($objBanners, $banner_cssID, $banner_class);
                    $arrBanners = $objBannerVideo->generateTemplateData();

                    $arrResults[] = $arrBanners[0];

                    //$this->arrBannerData = $arrBanners; //wird von setStatViewUpdate genutzt
                    $this->setStatViewUpdate($arrResults, $module_id, $objBanners->banner_useragent);
                }//video banner
            }//Banner vorhanden
        } // foreach($this->arrAllBannersBasic)

        //anderes Template?
        if (($this->banner_template != $this->strTemplate)
         && ($this->banner_template != '')
        ) {
            $this->strTemplate = $this->banner_template;
            $this->Template = new \FrontendTemplate($this->strTemplate);
        }

        //falls $arrImageSize = false  und kein Text Banner ist es ein leeres array
        $this->Template->banners = $arrResults;
        $this->Template->bmid = "bmid".$module_id;

        return $this->Template;
    }

    /**
     * shuffle for associative arrays, preserves key=>value pairs.
     * http://www.php.net/manual/de/function.shuffle.php
     */
    protected function shuffleAssoc(&$array)
    {
        $keys = array_keys($array);
        shuffle($keys);
        shuffle($keys);

        foreach ($keys as $key) {
            $new[$key] = $array[$key];
            unset($array[$key]); // save memory
        }
        $array = $new;

        return true;
    }

    protected function setStatViewUpdate($arrBannerData, $module_id, $banner_useragent)
    {
        $objBannerCount = new BannerCount($arrBannerData, $banner_useragent, $module_id);
        $objBannerCount->setStatViewUpdate();
        unset($objBannerCount);
    }
}
