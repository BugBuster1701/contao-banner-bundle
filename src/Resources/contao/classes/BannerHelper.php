<?php

/**
 * Contao Open Source CMS, Copyright (C) 2005-2014 Leo Feyer
 *
 * Modul Banner - FE Helper Class BannerHelper
 *
 * @copyright  Glen Langer 2007..2015 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Banner
 * @license    LGPL
 * @filesource
 * @see        https://github.com/BugBuster1701/banner
 */

namespace BugBuster\Banner;

use BugBuster\Banner\BannerLog;


/**
 * Class BannerHelper
 *
 * @copyright  Glen Langer 2007..2015 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Banner
 * @license    LGPL
 */
class BannerHelper extends \Frontend 
{
	/**
	 * Banner intern
	 * @var string
	 */
	const BANNER_TYPE_INTERN = 'banner_image';
	
	/**
	 * Banner extern
	 * @var string
	 */
	const BANNER_TYPE_EXTERN = 'banner_image_extern';
	
	/**
	 * Banner text
	 * @var string
	 */
	const BANNER_TYPE_TEXT   = 'banner_text';
	
	/**
	 * Banner Data, for BannerStatViewUpdate
	 */
	protected $arrBannerData = array();
	
	/**
	 * Banner Seen
	 */
	public static $arrBannerSeen = array();
	
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
	 * @var bool	true  = View OK
	 * 				false = FE User logged in and nothing is allowed to view (wrong group)
	 */
	protected $statusBannerFrontendGroupView = true;
	
	/**
	 * Banner basic status
	 * @var bool    true = $arrAllBannersBasic filled | false = error
	 */
	protected $statusAllBannersBasic = true;
	
	/**
	 * Category values 
	 * @var mixed	array|false, false if category not exists
	 */
	protected $arrCategoryValues = array();
	
	/**
	 * All banner basic data (id,weighting) from a category
	 * @var array
	 */
	protected $arrAllBannersBasic = array();
	
	
	/**
	 * Page Output Format
	 * @var string
	 */
	protected $strFormat = 'html5';
	
	/**
	 * Session
	 *
	 * @var string
	 * @access private
	 */
	private $_session   = array();
	 
	/**
	 * BannerHelper::bannerHelperInit
	 * 
	 * @return	false, if anything is wrong
	 */
	protected function bannerHelperInit()
	{
	    //Fix the planet
	    $this->statusRandomBlocker           = false;
	    $this->statusFirstViewBlocker        = false;
	    $this->statusBannerFirstView         = false;
	    $this->statusBannerFrontendGroupView = true;
	    $this->statusAllBannersBasic         = true;
	    $this->arrCategoryValues             = array();
	    $this->arrAllBannersBasic            = array();
		
		//set $arrCategoryValues over tl_banner_category
		if ($this->getSetCategoryValues() === false) { return false; }
		
		//check for protected user groups
		//set $statusBannerFrontendGroupView
		$this->checkSetUserFrontendLogin();
		
		//get basic banner infos (id,weighting) in $this->arrAllBannersBasic
		if ($this->getSetAllBannerForCategory() === false) 
		{
			$this->statusAllBannersBasic = false;
		}
		
		$this->strFormat = 'html5';
		
		if (!isset($GLOBALS['objPage'])) 
		{
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
	 * @return boolean    true = OK | false = we have a problem
	 */
	protected function getSetCategoryValues()
	{
	    //DEBUG log_message('getSetCategoryValues banner_categories:'.$this->banner_categories,'Banner.log');
		//$this->banner_categories is now an ID, but the name is backward compatible 
		if ( !isset($this->banner_categories) || !is_numeric($this->banner_categories) ) 
		{
			BannerLog::log($GLOBALS['TL_LANG']['tl_banner']['banner_cat_not_found'], 'ModulBanner Compile', 'ERROR');
			$this->arrCategoryValues = false;
			return false;
		}
		$objBannerCategory = \Database::getInstance()->prepare("SELECT 
                                                                    * 
                                                                FROM  
                                                                    tl_banner_category 
                                                                WHERE 
                                                                    id=?")
											         ->execute($this->banner_categories); 
		if ($objBannerCategory->numRows == 0) 
		{
			BannerLog::log($GLOBALS['TL_LANG']['tl_banner']['banner_cat_not_found'], 'ModulBanner Compile', 'ERROR');
			$this->arrCategoryValues = false;
			return false;
		}
		$arrGroup = deserialize($objBannerCategory->banner_groups);
		//Pfad+Dateiname holen ueber UUID (findByPk leitet um auf findByUuid)
		$objFile = \FilesModel::findByPk($objBannerCategory->banner_default_image);
		$this->arrCategoryValues = array(
                                        'id'                    => $objBannerCategory->id,
                                        'banner_default'		=> $objBannerCategory->banner_default,
                                        'banner_default_name'	=> $objBannerCategory->banner_default_name,
                                        'banner_default_image'	=> $objFile->path,
                                        'banner_default_url'	=> $objBannerCategory->banner_default_url,
                                        'banner_default_target'	=> $objBannerCategory->banner_default_target,
                                        'banner_numbers'		=> $objBannerCategory->banner_numbers, //0:single,1:multi,see banner_limit
                                        'banner_random'			=> $objBannerCategory->banner_random,
                                        'banner_limit'			=> $objBannerCategory->banner_limit, // 0:all, others = max 
                                        'banner_protected'		=> $objBannerCategory->banner_protected,
                                        'banner_group'			=> $arrGroup[0]
                                        );
        //DEBUG log_message('getSetCategoryValues arrCategoryValues:'.print_r($this->arrCategoryValues,true),'Banner.log');
		return true;
	}
	
	/**
	 * BannerHelper::checkSetUserFrontendLogin
	 * 
	 * Check if FE User loggen in and banner category is protected
	 * 
	 * @return boolean    true = View allowed | false = View not allowed
	 */
	protected function checkSetUserFrontendLogin()
	{
		if (FE_USER_LOGGED_IN)
		{
		    $this->import('FrontendUser', 'User');
		    
		    if ( $this->arrCategoryValues['banner_protected'] == 1 
		      && $this->arrCategoryValues['banner_group']      > 0 ) 
		    {
		    	if ( $this->User->isMemberOf($this->arrCategoryValues['banner_group']) === false ) 
		    	{
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
	 * @return boolean    true = $arrAllBannersBasic is filled | false = empty $arrAllBannersBasic
	 */
	protected function getSetAllBannerForCategory()
	{
	    $this->arrAllBannersBasic = array(); 
		//wenn mit der definierte Kategorie ID keine Daten gefunden wurden
		//macht Suche nach Banner kein Sinn
		if ($this->arrCategoryValues === false) 
		{
			return false;
		}
		//Domain Name ermitteln
		$http_host = \Environment::get('host');
		//aktueller Zeitstempel
		$intTime = time();
		
		//alle gültigen aktiven Banner,
		//ohne Beachtung der Gewichtung,
		//mit Beachtung der Domain
		//sortiert nach "sorting"
		//nur Basic Felder `id`, `banner_weighting` 
		$objBanners = \Database::getInstance()
		                ->prepare("SELECT 
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
                        ->execute($this->banner_categories
        							, '', ''
        							, '', ''
        							, 1
        							, '', $intTime, '', $intTime
        							, '', $http_host);
		while ($objBanners->next())
		{
			$this->arrAllBannersBasic[$objBanners->id] = $objBanners->banner_weighting;
		}
		//DEBUG log_message('getSetAllBannerForCategory arrAllBannersBasic:'.print_r($this->arrAllBannersBasic,true),'Banner.log');
		return (bool)$this->arrAllBannersBasic; //false bei leerem array, sonst true
	}
	
	/**
	 * setDebugSettings
	 *
	 * @param unknown $banner_category_id
	 */
	public function setDebugSettings($banner_category_id)
	{
	    if (0 == $banner_category_id) { return; }// keine Banner Category, nichts zu tun
	    
	    $GLOBALS['banner']['debug']['tag']          = false;
	    $GLOBALS['banner']['debug']['helper']       = false;
	    $GLOBALS['banner']['debug']['image']        = false;
	    $GLOBALS['banner']['debug']['referrer']     = false;
	    $GLOBALS['banner']['debug']['logic']        = false;
	    $GLOBALS['banner']['debug']['checks']       = false;
	
	    $objBanner = \Database::getInstance()
                    ->prepare("SELECT
                                    banner_expert_debug_tag,
                                    banner_expert_debug_helper,
                                    banner_expert_debug_image,
                                    banner_expert_debug_referrer,
                                    banner_expert_debug_logic,
                                    banner_expert_debug_checks
                                FROM
                                    tl_banner_category
                                WHERE
                                    id=?
                                ")
                    ->limit(1)
                    ->execute($banner_category_id);
        while ($objBanner->next())
        {
            $GLOBALS['banner']['debug']['tag']          = (boolean)$objBanner->banner_expert_debug_tag;
            $GLOBALS['banner']['debug']['helper']       = (boolean)$objBanner->banner_expert_debug_helper;
            $GLOBALS['banner']['debug']['image']        = (boolean)$objBanner->banner_expert_debug_image;
            $GLOBALS['banner']['debug']['referrer']     = (boolean)$objBanner->banner_expert_debug_referrer;
            $GLOBALS['banner']['debug']['logic']        = (boolean)$objBanner->banner_expert_debug_logic;
            $GLOBALS['banner']['debug']['checks']       = (boolean)$objBanner->banner_expert_debug_checks;
            BannerLog::writeLog('## START ##', '## DEBUG ##', 'T'.(int)$GLOBALS['banner']['debug']['tag'] .'#H'. (int)$GLOBALS['banner']['debug']['helper'] .'#I'. (int)$GLOBALS['banner']['debug']['image'] .'#R'.(int) $GLOBALS['banner']['debug']['referrer']);
        }
	}
	
	
	
	
	
} // class

