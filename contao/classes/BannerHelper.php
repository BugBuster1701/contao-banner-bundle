<?php

/**
 * Contao Open Source CMS, Copyright (C) 2005-2022 Leo Feyer
 *
 * Modul Banner - FE Helper Class BannerHelper
 *
 * @copyright  Glen Langer 2007..2022 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @license    LGPL
 * @filesource
 * @see        https://github.com/BugBuster1701/contao-banner-bundle
 */

namespace BugBuster\Banner;

use BugBuster\Banner\BannerLog;

/**
 * Class BannerHelper
 *
 * @copyright  Glen Langer 2007..2022 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @license    LGPL
 */
class BannerHelper extends \Contao\Frontend
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

    /**
     * Banner Data, for BannerStatViewUpdate
     */
    protected $arrBannerData = [];

    /**
     * Banner Seen
     */
    public static $arrBannerSeen = [];

    /**
     * Banner Random Blocker
     */
    protected $statusRandomBlocker = false;

    /**
     * First View Blocker
     */
    protected $statusFirstViewBlocker = false;

    /**
     * Banner First View Status
     */
    protected $statusBannerFirstView = false;

    /**
     * Banner Frontend Group View
     * @var bool true  = View OK
     *           false = FE User logged in and nothing is allowed to view (wrong group)
     */
    protected $statusBannerFrontendGroupView = true;

    /**
     * Banner basic status
     * @var bool true = filled | false = error
     */
    protected $statusAllBannersBasic = true;

    /**
     * Category values
     * @var mixed array|false, false if category not exists
     */
    protected $arrCategoryValues = [];

    /**
     * All banner basic data (id,weighting) from a category
     * @var array
     */
    protected $arrAllBannersBasic = [];

    /**
     * Page Output Format
     * @var string
     */
    protected $strFormat = 'html5';

    /**
     * Session
     *
     * @var string
     */
    private $_session   = [];

    /**
     * BannerHelper::bannerHelperInit
     *
     * @return false, if anything is wrong
     */
    protected function bannerHelperInit()
    {
        //Fix the planet
        $this->statusRandomBlocker           = false;
        $this->statusFirstViewBlocker        = false;
        $this->statusBannerFirstView         = false;
        $this->statusBannerFrontendGroupView = true;
        $this->statusAllBannersBasic         = true;
        $this->arrCategoryValues             = [];
        $this->arrAllBannersBasic            = [];

        //set $arrCategoryValues over tl_banner_category
        if ($this->getSetCategoryValues() === false) {
            return false;
        }

        //check for protected user groups
        //set $statusBannerFrontendGroupView
        $this->checkSetUserFrontendLogin();

        //get basic banner infos (id,weighting) in $this->arrAllBannersBasic
        if ($this->getSetAllBannerForCategory() === false) {
            $this->statusAllBannersBasic = false;
        }

        $this->strFormat = 'html5';

        if (!isset($GLOBALS['objPage'])) {
            $objPage = new \stdClass();
            $objPage->templateGroup = $this->templatepfad;
            $objPage->outputFormat = $this->outputFormat;
            $GLOBALS['objPage'] = $objPage;
        }
    }

    /**
     * BannerHelper::getSetCategoryValues
     *
     * Set Category Values in $this->arrCategoryValues over tl_banner_category
     *
     * @return boolean true = OK | false = we have a problem
     */
    protected function getSetCategoryValues()
    {
        //DEBUG log_message('getSetCategoryValues banner_categories:'.$this->banner_categories,'Banner.log');
        //$this->banner_categories is now an ID, but the name is backward compatible
        if (!isset($this->banner_categories) || !is_numeric($this->banner_categories)) {
            BannerLog::log($GLOBALS['TL_LANG']['tl_banner']['banner_cat_not_found'], 'ModulBanner Compile', 'ERROR');
            $this->arrCategoryValues = false;

            return false;
        }
        $objBannerCategory = \Contao\Database::getInstance()->prepare("SELECT 
                                                                    * 
                                                                FROM  
                                                                    tl_banner_category 
                                                                WHERE 
                                                                    id=?")
                                                     ->execute($this->banner_categories);
        if ($objBannerCategory->numRows == 0) {
            BannerLog::log($GLOBALS['TL_LANG']['tl_banner']['banner_cat_not_found'], 'ModulBanner Compile', 'ERROR');
            $this->arrCategoryValues = false;

            return false;
        }
        $arrGroup = \Contao\StringUtil::deserialize($objBannerCategory->banner_groups);
        //Pfad+Dateiname holen ueber UUID (findByPk leitet um auf findByUuid)
        $objFile = \Contao\FilesModel::findByPk($objBannerCategory->banner_default_image);
        $this->arrCategoryValues = [
                                        'id'                    => $objBannerCategory->id,
                                        'banner_default'		=> $objBannerCategory->banner_default,
                                        'banner_default_name'	=> $objBannerCategory->banner_default_name,
                                        'banner_default_image'	=> $objFile->path ?? '',
                                        'banner_default_url'	=> $objBannerCategory->banner_default_url,
                                        'banner_default_target'	=> $objBannerCategory->banner_default_target,
                                        'banner_numbers'		=> $objBannerCategory->banner_numbers, //0:single,1:multi,see banner_limit
                                        'banner_random'			=> $objBannerCategory->banner_random,
                                        'banner_limit'			=> $objBannerCategory->banner_limit, // 0:all, others = max
                                        'banner_protected'		=> $objBannerCategory->banner_protected,
                                        'banner_group'			=> $arrGroup[0] ?? 0
                                        ];
        //DEBUG log_message('getSetCategoryValues arrCategoryValues:'.print_r($this->arrCategoryValues,true),'Banner.log');
        return true;
    }

    /**
     * BannerHelper::checkSetUserFrontendLogin
     *
     * Check if FE User loggen in and banner category is protected
     *
     * @return boolean true = View allowed | false = View not allowed
     */
    protected function checkSetUserFrontendLogin()
    {
        $hasFrontendUser = \Contao\System::getContainer()->get('contao.security.token_checker')->hasFrontendUser();
        if ($hasFrontendUser) {
            //$this->import('FrontendUser', 'User');
            $user = \Contao\FrontendUser::getInstance();

            if ($this->arrCategoryValues['banner_protected'] == 1
              && $this->arrCategoryValues['banner_group']      > 0) {
                if ($user->isMemberOf($this->arrCategoryValues['banner_group']) === false) {
                    $this->statusBannerFrontendGroupView = false;

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * BannerHelper::getSetAllBannerForCategory
     *
     * Get all Banner basics (id,weighting) for category, in $arrAllBannersBasic
     *
     * @return boolean true = $arrAllBannersBasic is filled | false = empty $arrAllBannersBasic
     */
    protected function getSetAllBannerForCategory()
    {
        $this->arrAllBannersBasic = [];
        //wenn mit der definierte Kategorie ID keine Daten gefunden wurden
        //macht Suche nach Banner kein Sinn
        if ($this->arrCategoryValues === false) {
            return false;
        }
        //Domain Name ermitteln
        $http_host = \Contao\Environment::get('host');
        //aktueller Zeitstempel
        $intTime = time();

        //alle gültigen aktiven Banner,
        //ohne Beachtung der Gewichtung,
        //mit Beachtung der Domain
        //sortiert nach "sorting"
        //nur Basic Felder `id`, `banner_weighting`
        $objBanners = \Contao\Database::getInstance()
                        ->prepare(
                            "SELECT 
                                        TLB.`id`, TLB.`banner_weighting`
                                   FROM 
                                        tl_banner AS TLB 
                                   LEFT JOIN 
                                        tl_banner_category ON tl_banner_category.id=TLB.pid
                                   LEFT OUTER JOIN 
                                        tl_banner_stat AS TLS ON TLB.id=TLS.id
                                   WHERE 
                                        pid=?
                                   AND (
                                           (TLB.banner_until=?) 
		                                OR (TLB.banner_until=1 AND TLB.banner_views_until>TLS.banner_views)   
                                        OR (TLB.banner_until=1 AND TLB.banner_views_until=?)  
                                        OR (TLB.banner_until=1 AND TLS.banner_views is NULL)
                                       )
                                   AND (
                                           (TLB.banner_until=?) 
                                        OR (TLB.banner_until=1 AND TLB.banner_clicks_until>TLS.banner_clicks) 
                                        OR (TLB.banner_until=1 AND TLB.banner_clicks_until=?) 
                                        OR (TLB.banner_until=1 AND TLS.banner_clicks is NULL)
                                       )
                                   AND 
                                        TLB.banner_published =?
                                   AND 
                                       (TLB.banner_start=? OR TLB.banner_start<=?) 
                                   AND 
                                       (TLB.banner_stop=? OR TLB.banner_stop>=?)
                                   AND 
                                       (TLB.banner_domain=? OR RIGHT(?, CHAR_LENGTH(TLB.banner_domain)) = TLB.banner_domain)
                                   ORDER BY TLB.`sorting`"
                        )
                        ->execute($this->banner_categories, '', '', '', '', 1, '', $intTime, '', $intTime, '', $http_host);
        while ($objBanners->next()) {
            $this->arrAllBannersBasic[$objBanners->id] = $objBanners->banner_weighting;
        }
        //DEBUG log_message('getSetAllBannerForCategory arrAllBannersBasic:'.print_r($this->arrAllBannersBasic,true),'Banner.log');
        return (bool) $this->arrAllBannersBasic; //false bei leerem array, sonst true
    }

    /**
     * setDebugSettings
     *
     * @param unknown $banner_category_id
     */
    public function setDebugSettings($banner_category_id)
    {
        if (0 == $banner_category_id) {
            return;
        }// keine Banner Category, nichts zu tun

        $GLOBALS['banner']['debug']['all'] = false;

        $objBanner = \Contao\Database::getInstance()
                    ->prepare("SELECT
                                    banner_expert_debug_all
                                FROM
                                    tl_banner_category
                                WHERE
                                    id=?
                                ")
                    ->limit(1)
                    ->execute($banner_category_id);
        while ($objBanner->next()) {
            $GLOBALS['banner']['debug']['all'] = (bool) $objBanner->banner_expert_debug_all;
            BannerLog::writeLog('## START ##', '## DEBUG ##', '');
        }
    }

    /**
     * Generate a front end URL
     * Shorted version of Controller::generateFrontendUrl
     *
     * @param array  $arrRow       An array of page parameters
     * @param string $strParams    An optional string of URL parameters
     * @param string $strForceLang Force a certain language
     *
     * @return string An URL that can be used in the front end
     */
    public static function frontendUrlGenerator($arrRow, $strParams=null, $strForceLang=null)
    {
        $objTargetTo = \Contao\PageModel::findPublishedById($arrRow['id']);

        if ($objTargetTo === null) {
            return '';
        }

        $strUrl = $objTargetTo->getFrontendUrl($strParams);

        return $strUrl;
    }

    public static function decodePunycode($strUrl)
    {
        if (empty($strUrl)) {
            return '';
        }
        $arrUrl = parse_url($strUrl);

        if (!isset($arrUrl['scheme'])) {
            //interne Seite
            return $strUrl;
        }

        return \Contao\Idna::decodeUrl($strUrl);
    }
} // class
