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

/**
 * Run in a custom namespace, so the class can be replaced
 */
namespace BugBuster\Banner;

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
	protected $strFormat = 'xhtml';
	
	/**
	 * Session
	 *
	 * @var string
	 * @access private
	 */
	private $_session   = array();
	 
	/**
	 * INIT
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
		
		//ModuleBannerTag->outputFormat
		if ($this->outputFormat == 'html5')
		{
		    $this->strFormat = 'html5';
		}
		//DEBUG log_message('bannerHelperInit this->outputFormat:'.$this->outputFormat,'Banner.log');
		 
		if (!isset($GLOBALS['objPage'])) 
		{
			$objPage = new \stdClass();
			$objPage->templateGroup = $this->templatepfad;
			$objPage->outputFormat = $this->outputFormat;
			$GLOBALS['objPage'] = $objPage;
		}
		
	}
	
	/**
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
			$this->log($GLOBALS['TL_LANG']['tl_banner']['banner_cat_not_found'], 'ModulBanner Compile', 'ERROR');
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
			$this->log($GLOBALS['TL_LANG']['tl_banner']['banner_cat_not_found'], 'ModulBanner Compile', 'ERROR');
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
	 * Get default banner or empty banner in $this->Template->banners
	 * 
	 * @return boolean    true
	 */
	protected function getDefaultBanner()
	{
		$arrImageSize = array();
		//CSS-ID/Klasse(n) je Banner, für den wrapper
		$banner_cssID   = '';
		$banner_class   = ' banner_default';
				
		//BannerDefault gewünscht und vorhanden?
		if ( $this->arrCategoryValues['banner_default'] == '1' 
            && strlen($this->arrCategoryValues['banner_default_image']) > 0 
	       ) 
		{
			//Template setzen
			if ( ($this->banner_template != $this->strTemplate) 
			  && ($this->banner_template != '') ) 
			{
			    $this->strTemplate = $this->banner_template;
			    $this->Template = new \FrontendTemplate($this->strTemplate);
			}
			//Link je nach Ausgabeformat
			if ($this->strFormat == 'xhtml') 
			{
			    $banner_default_target = ($this->arrCategoryValues['banner_default_target'] == '1') ? LINK_BLUR : LINK_NEW_WINDOW;
			} 
			else 
			{
			    $banner_default_target = ($this->arrCategoryValues['banner_default_target'] == '1') ? '' : ' target="_blank"';
			}
			//BannerImage Class
			$this->BannerImage = new \Banner\BannerImage();
			 
			//Banner Art bestimmen
			$arrImageSize = $this->BannerImage->getBannerImageSize($this->arrCategoryValues['banner_default_image'], self::BANNER_TYPE_INTERN);
			// 1 = GIF, 2 = JPG, 3 = PNG
			// 4 = SWF, 13 = SWC (zip-like swf file)
			// 5 = PSD, 6 = BMP, 7 = TIFF(intel byte order), 8 = TIFF(motorola byte order)
			// 9 = JPC, 10 = JP2, 11 = JPX, 12 = JB2, 13 = SWC, 14 = IFF

			//fake the Picture::create
			$picture['img']   = array
			(
			    'src'    => $this->urlEncode($this->arrCategoryValues['banner_default_image']),
			    'width'  => $arrImageSize[0],
			    'height' => $arrImageSize[1],
			    'srcset' => $this->urlEncode($this->arrCategoryValues['banner_default_image'])
			);
			$picture['alt']   = specialchars(ampersand($this->arrCategoryValues['banner_default_name']));
			$picture['title'] = '';
			
			ModuleBannerLog::writeLog(__METHOD__ , __LINE__ , 'Fake Picture: '. print_r($picture,true));
			
			switch ($arrImageSize[2]) 
			{
			    case 1:
			    case 2:
			    case 3:
			        $arrBanners[] = array
							        (
							        'banner_key'     => 'defbid=',
							        'banner_wrap_id'    => $banner_cssID,
							        'banner_wrap_class' => $banner_class,
							        'banner_id'      => $this->arrCategoryValues['id'],
							        'banner_name'    => specialchars(ampersand($this->arrCategoryValues['banner_default_name'])),
							        'banner_url'     => $this->arrCategoryValues['banner_default_url'],
							        'banner_target'  => $banner_default_target,
							        'banner_comment' => specialchars(ampersand($this->arrCategoryValues['banner_default_name'])),
							        'src'            => $this->urlEncode(      $this->arrCategoryValues['banner_default_image']),
							        'alt'            => specialchars(ampersand($this->arrCategoryValues['banner_default_name'])),
							        'size'     		 => '',
							        'banner_pic'     => true,
							        'banner_flash'   => false,
							        'banner_text'    => false,
							        'banner_empty'   => false,	// issues 733
							        'picture'        => $picture
							        );
			        break;
			    case 4:  // Flash swf
			    case 13: // Flash swc
			        list($usec, ) = explode(" ", microtime());
			        $arrBanners[] = array
							        (
					                'banner_key'     => 'defbid=',
						            'banner_wrap_id'    => $banner_cssID,
						            'banner_wrap_class' => $banner_class,
					                'banner_id'      => $this->arrCategoryValues['id'],
					                'banner_name'    => specialchars(ampersand($this->arrCategoryValues['banner_default_name'])),
					                'banner_url'     => $this->arrCategoryValues['banner_default_url'],
					                'banner_target'  => $banner_default_target,
					                'banner_comment' => specialchars(ampersand($this->arrCategoryValues['banner_default_name'])),
					                'swf_src'        => $this->arrCategoryValues['banner_default_image'],
					                'swf_width'      => $arrImageSize[0],
					                'swf_height'     => $arrImageSize[1],
					                'swf_id'         => round((float)$usec*100000,0).'_'.$this->arrCategoryValues['id'],
					                'alt'            => specialchars(ampersand($this->arrCategoryValues['banner_default_name'])),
					                'banner_pic'     => false,
					                'banner_flash'   => true,
					                'banner_text'    => false,
					                'banner_empty'   => false	// issues 733
							        );
			        break;
			}
			$arrResults[] = $arrBanners[0];
			$this->Template->banners = $arrResults;
			return true;
		}
		//Kein BannerDefault
		$NoBannerFound = ($GLOBALS['TL_LANG']['MSC']['tl_banner']['noBanner']) ? $GLOBALS['TL_LANG']['MSC']['tl_banner']['noBanner'] : 'no banner, no default banner';
		$arrBanners[] = array
		(
		        'banner_key'  => 'bid=',
    		    'banner_wrap_id'    => $banner_cssID,
    		    'banner_wrap_class' => $banner_class,
		        'banner_id'   => 0,
		        'banner_name' => specialchars(ampersand($NoBannerFound)),
		        'banner_url'  => '',
		        'banner_target'  => '',
		        'banner_comment' => '',
		        'src' 			=> '',
		        'alt' 			=> '',
		        'size'     		=> '',
		        'banner_pic' 	=> false,
		        'banner_flash'  => false,
		        'banner_text'   => false,
		        'banner_empty'  => true	// issues 733
		);
		$arrResults[] = $arrBanners[0];
		//Ausblenden wenn leer?
		if ($this->banner_hideempty == 1)
		{
		    // auf Leer umschalten
		    $this->strTemplate='mod_banner_empty';
		    $this->Template = new \FrontendTemplate($this->strTemplate);
		}
		$this->Template->banners = $arrResults;
		
		return true;
	}
	
	/**
	 * Get weighting for single banner
	 * parameter over $this->arrAllBannersBasic [id,weighting]
	 * 
	 * @return integer    0|1|2|3    0 on error
	 */
	protected function getSingleWeighting()
	{
	    $arrPrio = array();
	    $arrPrioW = array();
	    $arrWeights = array_flip($this->arrAllBannersBasic);

	    //welche Wichtungen gibt es?
	    if (array_key_exists(1, $arrWeights)) { $arrPrioW[1] = 1; };
	    if (array_key_exists(2, $arrWeights)) { $arrPrioW[2] = 2; };
	    if (array_key_exists(3, $arrWeights)) { $arrPrioW[3] = 3; };
	    
	    $arrPrio[0] = array('start'=>0,  'stop'=>0);
	    $arrPrio[1] = array('start'=>1,  'stop'=>90);
	    $arrPrio[2] = array('start'=>91, 'stop'=>150);
	    $arrPrio[3] = array('start'=>151,'stop'=>180);
	    if ( !array_key_exists(2, $arrPrioW) )
	    {
	        // no prio 2 banner
	        $arrPrio[2] = array('start'=>0,  'stop'=>0);
	        $arrPrio[3] = array('start'=>91, 'stop'=>120);
	    }
	    $intPrio1 = (count($arrPrioW)) ? min($arrPrioW) : 0 ;
	    $intPrio2 = (count($arrPrioW)) ? max($arrPrioW) : 0 ;
	    
	    //wenn Wichtung vorhanden, dann per Zufall eine auswählen
	    if ($intPrio1>0)
	    {
	        $intWeightingHigh = mt_rand($arrPrio[$intPrio1]['start'],$arrPrio[$intPrio2]['stop']);
	    
	        // 1-180 auf 1-3 umrechnen
	        if ($intWeightingHigh<=$arrPrio[3]['stop'])
	        {
	            $intWeighting=3;
	        }
	        if ($intWeightingHigh<=$arrPrio[2]['stop'])
	        {
	            $intWeighting=2;
	        }
	        if ($intWeightingHigh<=$arrPrio[1]['stop'])
	        {
	            $intWeighting=1;
	        }
	    }
	    else
	    {
	        $intWeighting=0;
	    }
	    return $intWeighting;
	}
	
	/**
	 * Random Blocker, Set Banner-ID
	 * 
	 * @param integer    $BannerID
	 */
	protected function setRandomBlockerId($BannerID=0)
	{
	    if ($BannerID==0) { return; }// kein Banner, nichts zu tun
	    
	    $this->statusRandomBlocker = true;
	    $this->setSession('RandomBlocker'.$this->module_id , array( $BannerID => time() ));
	    return ;
	}
	
	/**
	 * Random Blocker, Get Banner-ID
	 * 
	 * @return integer    Banner-ID
	 */
	protected function getRandomBlockerId()
	{
	    $this->getSession('RandomBlocker'.$this->module_id);
	    if ( count($this->_session) )
	    {
	        list($key, $val) = each($this->_session);
	        unset($val);
	        reset($this->_session);
	        //DEBUG log_message('getRandomBlockerId BannerID:'.$key,'Banner.log');
	        return $key;
	    }
	    return 0;
	}
	
	/**
	 * First View Blocker, Set Banner Categorie-ID and timestamp
	 * 
	 * @param integer    $banner_categorie
	 */
	protected function setFirstViewBlockerId($banner_categorie=0)
	{
	    if ($banner_categorie==0) { return; }// keine Banner Kategorie, nichts zu tun
	     
	    $this->statusFirstViewBlocker = true;
	    $this->setSession('FirstViewBlocker'.$this->module_id, array( $banner_categorie => time() ));
	    return ;
	}
	
	/**
	 * First View Blocker, Get Banner Categorie-ID if the timestamp .... 
	 *
	 * @param mixed    $banner_categorie | false
	 */
	protected function getFirstViewBlockerId()
	{
	    $this->getSession('FirstViewBlocker'.$this->module_id);
	    if ( count($this->_session) )
	    {
	        list($key, $tstmap) = each($this->_session);
	        reset($this->_session);
	        if ( $this->removeOldFirstViewBlockerId($key, $tstmap) === true ) 
	        {
	            // Key ist noch gültig und es muss daher geblockt werden
	            //DEBUG log_message('getFirstViewBlockerId Banner Kat ID: '.$key,'Banner.log');
	            return $key;
	        }
	    }
	    return false;
	}
	
	/**
	 * First View Blocker, Remove old Banner Categorie-ID
	 *
	 * @param  integer    $banner_categorie
	 * @return boolean    true = Key is valid, it must be blocked | false = key is invalid
	 */
	protected function removeOldFirstViewBlockerId($key, $tstmap)
	{
	    // 5 Minuten Blockierung, älter >= 5 Minuten wird gelöscht
	    $FirstViewBlockTime = time() - 60*5;
	    
	    if ( $tstmap >  $FirstViewBlockTime ) 
	    {
	        return true;
	    }
	    else 
	    {
	        \Session::getInstance()->remove($key);
	    }
	    return false;
	}
	
	
	/**
	 * Get FirstViewBanner status and set cat id as blocker
	 * 
	 * @return boolean    true = if requested and not blocked | false = if requested but blocked
	 */
	protected function getSetFirstView()
	{
	    //return true; // for Test only
	    //FirstViewBanner gewünscht?
	    if ($this->banner_firstview !=1) { return false; }

	    $this->BannerReferrer = new \Banner\BannerReferrer();
	    $this->BannerReferrer->checkReferrer();
	    $ReferrerDNS = $this->BannerReferrer->getReferrerDNS();
	    // o own , w wrong
	    if ($ReferrerDNS === 'o')
	    {
	        // eigener Referrer, Begrenzung auf First View nicht nötig.
	        $this->statusBannerFirstView = false;
	        return false;
	    }
	    
	    if ( $this->getFirstViewBlockerId() === false )
	    {
	        // nichts geblockt, also blocken fürs den nächsten Aufruf
	        $this->setFirstViewBlockerId($this->banner_categories);
	        
	        // kein firstview block gefunden, Anzeigen erlaubt
	        $this->statusBannerFirstView = true;
	        return true;
	    }
	    else
	    {
	        $this->statusBannerFirstView = false;
	        return false;
	    }
	    
	}
	
	/**
	 * Get First View Banner
	 * 
	 * @return internal    Value in $this->strTemplate
	 */
	protected function getSingleBannerFirst()
	{
	    $arrBanners = array();
	    $arrResults = array();
	    $FileSrc = '';
	    
	    //first aktiv banner in category
	    //$this->arrAllBannersBasic den ersten Datensatz über die ID nutzen
	    //seltsamerweise kommt reference Fehler bei Kombination in einer Anweisung, daher getrennt
	    $banner_keys = array_keys($this->arrAllBannersBasic); 
	    $banner_id   = array_shift($banner_keys);
	    $objBanners  = \Database::getInstance()
                    	    ->prepare("SELECT
                            	            TLB.*
                                       FROM
                            	            tl_banner AS TLB
                                       WHERE 
                                            TLB.`id`=?"
                    	            )
            	            ->limit(1)
            	            ->execute( $banner_id );
        $intRows = $objBanners->numRows;
        //Banner vorhanden?
        if($intRows > 0)
        {
            $objBanners->next();
            self::$arrBannerSeen[] = $objBanners->id;
            //CSS-ID/Klasse(n) je Banner, für den wrapper
            $banner_cssID   = '';
            $banner_class   = '';
            $banner_classes = '';
            $_cssID = deserialize($objBanners->banner_cssid);
            if ( is_array($_cssID) ) 
            {
                if ($_cssID[0] != '') 
                {
                    $banner_cssID   = ' id="banner_'.$_cssID[0].'"';
                }
                if ($_cssID[1] != '') 
                {
                    $banner_classes = explode(" ", $_cssID[1]);
                    foreach ($banner_classes as $banner_classone) 
                    {
                        $banner_class .= ' banner_'.$banner_classone;
                    }
                }
            }
            
            switch ($objBanners->banner_type)
            {
                case self::BANNER_TYPE_INTERN :
                    //Pfad+Dateiname holen ueber UUID (findByPk leitet um auf findByUuid)
                    $objFile = \FilesModel::findByPk($objBanners->banner_image);
                    //BannerImage Class
                    $this->BannerImage = new \Banner\BannerImage();
                    //Banner Art und Größe bestimmen
                    $arrImageSize = $this->BannerImage->getBannerImageSize($objFile->path, self::BANNER_TYPE_INTERN);
                    //Falls Datei gelöscht wurde, Abbruch
                    if (false === $arrImageSize) 
                    {
                    	$arrImageSize[2] = 0;
                    	$this->log('Banner Image with ID "'.$objBanners->id.'" not found', 'BannerHelper getSingleBannerFirst', TL_ERROR);
                    	break;
                    }                    
                    //Banner Neue Größe 0:$Width 1:$Height 2:resize mode
                    $arrNewSizeValues = deserialize($objBanners->banner_imgSize);
                    //Banner Neue Größe ermitteln, return array $Width,$Height,$oriSize
                    $arrImageSizenNew = $this->BannerImage->getBannerImageSizeNew($arrImageSize[0],$arrImageSize[1],$arrNewSizeValues[0],$arrNewSizeValues[1]);
                    
                    //wenn oriSize = true, oder bei GIF/SWF/SWC = original Pfad nehmen
                    if ($arrImageSizenNew[2] === true //oriSize
                         || $arrImageSize[2] == 1  // GIF
                         || $arrImageSize[2] == 4  // SWF
                         || $arrImageSize[2] == 13 // SWC
                         ) 
                    {
                        $FileSrc = $objFile->path;
                        $arrImageSize[0] = $arrImageSizenNew[0];
                        $arrImageSize[1] = $arrImageSizenNew[1];
                        $arrImageSize[3] = ' height="'.$arrImageSizenNew[1].'" width="'.$arrImageSizenNew[0].'"';
                        
                        //fake the Picture::create
                        $picture['img']   = array
                        (
                            'src'    => specialchars(ampersand($FileSrc)),
                            'width'  => $arrImageSizenNew[0],
                            'height' => $arrImageSizenNew[1],
                            'srcset' => specialchars(ampersand($FileSrc))
                        );
                        $picture['alt']   = specialchars(ampersand($objBanners->banner_name));
                        $picture['title'] = specialchars(ampersand($objBanners->banner_comment));
                        
                        ModuleBannerLog::writeLog(__METHOD__ , __LINE__ , 'Orisize Picture: '. print_r($picture,true));
                    }
                    else
                    {
                        //Resize an image and store the resized version in the assets/images folder
                        //return The path of the resized image or null
                        $FileSrc = \Image::get($this->urlEncode($objFile->path), $arrImageSizenNew[0], $arrImageSizenNew[1],'proportional');
                        
                        $picture = \Picture::create($this->urlEncode($objFile->path), array($arrImageSizenNew[0], $arrImageSizenNew[1], $arrNewSizeValues[2]))->getTemplateData();
                        $picture['alt']   = specialchars(ampersand($objBanners->banner_name));
                        $picture['title'] = specialchars(ampersand($objBanners->banner_comment));
                        
                        ModuleBannerLog::writeLog(__METHOD__ , __LINE__ , 'Resize Picture: '. print_r($picture,true));
                        
                        $arrImageSize[0] = $arrImageSizenNew[0];
                        $arrImageSize[1] = $arrImageSizenNew[1];
                        $arrImageSize[3] = ' height="'.$arrImageSizenNew[1].'" width="'.$arrImageSizenNew[0].'"';
                    }
                    break;
                case self::BANNER_TYPE_EXTERN :
                    //BannerImage Class
                    $this->BannerImage = new \Banner\BannerImage();
                    //Banner Art und Größe bestimmen
                    $arrImageSize = $this->BannerImage->getBannerImageSize($objBanners->banner_image_extern, self::BANNER_TYPE_EXTERN);
                    //Falls Datei gelöscht wurde, Abbruch
                    if (false === $arrImageSize)
                    {
                        $arrImageSize[2] = 0;
                        $this->log('Banner Image with ID "'.$objBanners->id.'" not found', 'BannerHelper getSingleBannerFirst', TL_ERROR);
                        break;
                    }
                    //Banner Neue Größe 0:$Width 1:$Height
                    $arrNewSizeValues = deserialize($objBanners->banner_imgSize);
                    //Banner Neue Größe ermitteln, return array $Width,$Height,$oriSize
                    $arrImageSizenNew = $this->BannerImage->getBannerImageSizeNew($arrImageSize[0],$arrImageSize[1],$arrNewSizeValues[0],$arrNewSizeValues[1]);
                    //Umwandlung bei Parametern
                    $FileSrc = html_entity_decode($objBanners->banner_image_extern, ENT_NOQUOTES, 'UTF-8');

                    $arrImageSize[0] = $arrImageSizenNew[0];
                    $arrImageSize[1] = $arrImageSizenNew[1];
                    $arrImageSize[3] = ' height="'.$arrImageSizenNew[1].'" width="'.$arrImageSizenNew[0].'"';
                    break;
                case self::BANNER_TYPE_TEXT :
                    $arrImageSize = false;
                    break;
            }

            if ($arrImageSize !== false) //Bilder extern/intern
            {
                if ($this->strFormat == 'xhtml')
                {
                    $banner_target = ($objBanners->banner_target == '1') ? LINK_BLUR : LINK_NEW_WINDOW;
                } 
                else 
                {
                    $banner_target = ($objBanners->banner_target == '1') ? '' : ' target="_blank"';
                }
                
                if ( strlen($objBanners->banner_comment) > 1 )
                {
                    $banner_comment_pos = strpos($objBanners->banner_comment,"\n",1);
                    if ($banner_comment_pos !== false)
                    {
                        $objBanners->banner_comment = substr($objBanners->banner_comment,0,$banner_comment_pos);
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
                        $objBanners->banner_url = $domain . $this->generateFrontendUrl($objParent->row(), '', $objParent->language);
                    }
                }
                
                //$arrImageSize[0]  eigene Breite 
                //$arrImageSize[1]  eigene Höhe
                //$arrImageSize[3]  Breite und Höhe in der Form height="yyy" width="xxx"
                //$arrImageSize[2]
                // 1 = GIF, 2 = JPG, 3 = PNG
                // 4 = SWF, 13 = SWC (zip-like swf file)
                // 5 = PSD, 6 = BMP, 7 = TIFF(intel byte order), 8 = TIFF(motorola byte order)
                // 9 = JPC, 10 = JP2, 11 = JPX, 12 = JB2, 13 = SWC, 14 = IFF
                switch ($arrImageSize[2])
                {
                    case 1:
                    case 2:
                    case 3:
                        $arrBanners[] = array
                        (
                                'banner_key'     => 'bid=',
                                'banner_wrap_id'    => $banner_cssID,
                                'banner_wrap_class' => $banner_class,
                                'banner_id'      => $objBanners->id,
                                'banner_name'    => specialchars(ampersand($objBanners->banner_name)),
                                'banner_url'     => $objBanners->banner_url,
                                'banner_target'  => $banner_target,
                                'banner_comment' => specialchars(ampersand($objBanners->banner_comment)),
                                'src'            => specialchars(ampersand($FileSrc)),//specialchars(ampersand($this->urlEncode($FileSrc))),
                                'alt'            => specialchars(ampersand($objBanners->banner_name)),
                                'size'           => $arrImageSize[3],
                                'banner_pic'     => true,
                                'banner_flash'   => false,
                                'banner_text'    => false,
                                'banner_empty'   => false,
                                'picture'        => $picture
                        );
                        break;
                    case 4:  // Flash swf
                    case 13: // Flash swc
                        list($usec, ) = explode(" ", microtime());
                        
                        //Check for Fallback Image, only for local flash files (Path,Breite,Höhe)
                        $src_fallback = $this->BannerImage->getCheckBannerImageFallback($FileSrc,$arrImageSize[0],$arrImageSize[1]);
                        if ($src_fallback !== false)
                        {
                            //Fallback gefunden
                            if ($this->strFormat == 'xhtml') 
                            {
                                $fallback_content = '<img src="' . $src_fallback . '" alt="'.specialchars(ampersand($objBanners->banner_comment)).'" height="'.$arrImageSize[1].'" width="'.$arrImageSize[0].'" />';
                            } 
                            else 
                            {
                                $fallback_content = '<img src="' . $src_fallback . '" alt="'.specialchars(ampersand($objBanners->banner_comment)).'" height="'.$arrImageSize[1].'" width="'.$arrImageSize[0].'">';
                            }
                        }
                        else
                        {
                            //kein Fallback
                            if ($this->strFormat == 'xhtml')
                            {
                                $fallback_content = $FileSrc ."<br />". specialchars(ampersand($objBanners->banner_comment)) ."<br />". specialchars(ampersand($objBanners->banner_name));
                            } 
                            else 
                            {
                                $fallback_content = $FileSrc ."<br>". specialchars(ampersand($objBanners->banner_comment)) ."<br>". specialchars(ampersand($objBanners->banner_name));
                            }
                        }
                        $arrBanners[] = array
                        (
                            'banner_key'     => 'bid=',
                            'banner_wrap_id'    => $banner_cssID,
                            'banner_wrap_class' => $banner_class,
                            'banner_id'      => $objBanners->id,
                            'banner_name'    => specialchars(ampersand($objBanners->banner_name)),
                            'banner_url'     => $objBanners->banner_url,
                            'banner_target'  => $banner_target,
                            'banner_comment' => specialchars(ampersand($objBanners->banner_comment)),
                            'swf_src'        => specialchars(ampersand($FileSrc)),
                            'swf_width'      => $arrImageSize[0],
                            'swf_height'     => $arrImageSize[1],
                            'swf_id'         => round((float)$usec*100000,0).'_'.$objBanners->id,
                            'alt'            => specialchars(ampersand($objBanners->banner_name)),
                            'fallback_content'=> $fallback_content,
                            'banner_pic'     => false,
                            'banner_flash'   => true,
                            'banner_text'    => false,
                            'banner_empty'   => false
                        );
                        break;
                    default:
                        $arrBanners[] = array
                        (
                            'banner_key'     => 'bid=',
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
                        );
                        break;
                }//switch
                
                //anderes Template?
                if (($this->banner_template != $this->strTemplate) 
                 && ($this->banner_template != ''))
                {
                    $this->strTemplate = $this->banner_template;
                    $this->Template = new \FrontendTemplate($this->strTemplate);
                }
                $this->arrBannerData = $arrBanners; //wird von BannerStatViewUpdate genutzt
                $this->setStatViewUpdate();
                $this->Template->banners = $arrBanners;
                return true;
                
            }//$arrImageSize !== false
            
            // Text Banner
            if ($objBanners->banner_type == 'banner_text') 
            {
                if ($this->strFormat == 'xhtml')
                {
                    $banner_target = ($objBanners->banner_target == '1') ? LINK_BLUR : LINK_NEW_WINDOW;
                } 
                else 
                {
                    $banner_target = ($objBanners->banner_target == '1') ? '' : ' target="_blank"';
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
                        $objBanners->banner_url = $domain . $this->generateFrontendUrl($objParent->row(), '', $objParent->language);
                    }
                }
                
                // Kurz URL (nur Domain)
                $treffer = parse_url(\Idna::decode($objBanners->banner_url)); // #79
                $banner_url_kurz = $treffer['host'];
                if (isset($treffer['port'])) 
                {
                    $banner_url_kurz .= ':'.$treffer['port'];
                }
                
                $arrBanners[] = array
                (
                        'banner_key'     => 'bid=',
                        'banner_wrap_id'    => $banner_cssID,
                        'banner_wrap_class' => $banner_class,
                        'banner_id'      => $objBanners->id,
                        'banner_name'    => specialchars(ampersand($objBanners->banner_name)),
                        'banner_url'     => $objBanners->banner_url,
                        'banner_url_kurz'=> $banner_url_kurz,
                        'banner_target'  => $banner_target,
                        'banner_comment' => ampersand(nl2br($objBanners->banner_comment)),
                        'banner_pic'     => false,
                        'banner_flash'   => false,
                        'banner_text'    => true,
                        'banner_empty'   => false	// issues 733
                );
                if (($this->banner_template != $this->strTemplate) 
                 && ($this->banner_template != '')) 
                {
                    $this->strTemplate = $this->banner_template;
                    $this->Template = new \FrontendTemplate($this->strTemplate);
                }
                $arrResults[] = $arrBanners[0];
                $this->Template->banners = $arrResults;
                 
                $this->arrBannerData = $arrResults;
                $this->setStatViewUpdate();
                return true;
            }
        }//Banner vorhanden
        //falls $arrImageSize = false  und kein Text Banner
        $this->Template->banners = $arrBanners; // leeres array
	}
	
	protected function getSingleBanner()
	{
	    //RandomBlocker entfernen falls möglich und nötig
	    if ( count($this->arrAllBannersBasic) >1 ) // einer muss ja übrig bleiben
	    {
	        $intRandomBlockerID = $this->getRandomBlockerId();
	        if (isset($this->arrAllBannersBasic[$intRandomBlockerID]))
	        {
	            unset($this->arrAllBannersBasic[$intRandomBlockerID]);
	        }
	    }
	    
	    //Gewichtung nach vorhandenen Wichtungen
	    $SingleBannerWeighting = $this->getSingleWeighting();

	    //alle Basic Daten durchgehen und die löschen die nicht der Wichtung entsprechen
	    while ( list($key, $val) = each($this->arrAllBannersBasic) ) 
	    {
	        if ($val != $SingleBannerWeighting) 
	        {
	            unset($this->arrAllBannersBasic[$key]);
	        }
	    }
	    reset($this->arrAllBannersBasic); //sicher ist sicher
	    
	    //Zufallszahl
	    //array_shuffle und array_rand zu "ungenau"
	    $intShowBanner =  mt_rand(1,count($this->arrAllBannersBasic)); 
	    $banner_keys = array_keys($this->arrAllBannersBasic);
	    for ($xx=1;$xx<=$intShowBanner;$xx++)
	    {
	        $banner_id   = array_shift($banner_keys);
	    }
	    
	    //Random Blocker setzen
	    $this->setRandomBlockerId($banner_id);
	    
	    $objBanners  = \Database::getInstance()
                            ->prepare("SELECT
                            	            TLB.*
                                       FROM
                            	            tl_banner AS TLB
                                       WHERE
                                            TLB.`id`=?"
                                     )
                            ->limit(1)
                            ->execute( $banner_id );
	    $intRows = $objBanners->numRows;
	    //Banner vorhanden?
	    if($intRows > 0)
	    {
	        $objBanners->next();
	        self::$arrBannerSeen[] = $objBanners->id;
	        //CSS-ID/Klasse(n) je Banner, für den wrapper
	        $banner_cssID   = '';
	        $banner_class   = '';
	        $banner_classes = '';
	        $_cssID = deserialize($objBanners->banner_cssid);
	    	if ( is_array($_cssID) )
            {
                if ($_cssID[0] != '')
                {
                    $banner_cssID   = ' id="banner_'.$_cssID[0].'"';
                }
                if ($_cssID[1] != '')
                {
                    $banner_classes = explode(" ", $_cssID[1]);
                    foreach ($banner_classes as $banner_classone)
                    {
                        $banner_class .= ' banner_'.$banner_classone;
                    }
                }
            }
        
	        switch ($objBanners->banner_type)
	        {
	            case self::BANNER_TYPE_INTERN :
	                //Pfad+Dateiname holen ueber UUID (findByPk leitet um auf findByUuid)
	                $objFile = \FilesModel::findByPk($objBanners->banner_image);
	                //BannerImage Class
	                $this->BannerImage = new \Banner\BannerImage();
	                //Banner Art und Größe bestimmen
	                $arrImageSize = $this->BannerImage->getBannerImageSize($objFile->path, self::BANNER_TYPE_INTERN);
	                //Falls Datei gelöscht wurde, Abbruch
	                if (false === $arrImageSize)
	                {
	                    $arrImageSize[2] = 0;
	                    $this->log('Banner Image with ID "'.$objBanners->id.'" not found', 'BannerHelper getSingleBanner', TL_ERROR);
	                    break;
	                }
	                //Banner Neue Größe 0:$Width 1:$Height
	                $arrNewSizeValues = deserialize($objBanners->banner_imgSize);
	                //Banner Neue Größe ermitteln, return array $Width,$Height,$oriSize
	                $arrImageSizenNew = $this->BannerImage->getBannerImageSizeNew($arrImageSize[0],$arrImageSize[1],$arrNewSizeValues[0],$arrNewSizeValues[1]);
	    
	                //wenn oriSize = true, oder bei GIF/SWF/SWC = original Pfad nehmen
	                if ($arrImageSizenNew[2] === true
	                        || $arrImageSize[2] == 1  // GIF
	                        || $arrImageSize[2] == 4  // SWF
	                        || $arrImageSize[2] == 13 // SWC
	                )
	                {
	                    $FileSrc = $objFile->path;
	                    $arrImageSize[0] = $arrImageSizenNew[0];
	                    $arrImageSize[1] = $arrImageSizenNew[1];
	                    $arrImageSize[3] = ' height="'.$arrImageSizenNew[1].'" width="'.$arrImageSizenNew[0].'"';
	                    
	                    //fake the Picture::create
                        $picture['img']   = array
                        (
                            'src'    => specialchars(ampersand($FileSrc)),
                            'width'  => $arrImageSizenNew[0],
                            'height' => $arrImageSizenNew[1],
                            'srcset' => specialchars(ampersand($FileSrc))
                        );
	                    $picture['alt']   = specialchars(ampersand($objBanners->banner_name));
	                    $picture['title'] = specialchars(ampersand($objBanners->banner_comment));
	                    
	                    ModuleBannerLog::writeLog(__METHOD__ , __LINE__ , 'Orisize Picture: '. print_r($picture,true));
	                }
	                else
	                {
	                    $FileSrc = \Image::get($this->urlEncode($objFile->path), $arrImageSizenNew[0], $arrImageSizenNew[1],'proportional');
	                    
	                    $picture = \Picture::create($this->urlEncode($objFile->path), array($arrImageSizenNew[0], $arrImageSizenNew[1], $arrNewSizeValues[2]))->getTemplateData();
	                    $picture['alt']   = specialchars(ampersand($objBanners->banner_name));
	                    $picture['title'] = specialchars(ampersand($objBanners->banner_comment));
	                    
	                    ModuleBannerLog::writeLog(__METHOD__ , __LINE__ , 'Resize Picture: '. print_r($picture,true));

	                    $arrImageSize[0] = $arrImageSizenNew[0];
	                    $arrImageSize[1] = $arrImageSizenNew[1];
	                    $arrImageSize[3] = ' height="'.$arrImageSizenNew[1].'" width="'.$arrImageSizenNew[0].'"';
	                }
	                break;
	            case self::BANNER_TYPE_EXTERN :
	                //BannerImage Class
	                $this->BannerImage = new \Banner\BannerImage();
	                //Banner Art und Größe bestimmen
	                $arrImageSize = $this->BannerImage->getBannerImageSize($objBanners->banner_image_extern, self::BANNER_TYPE_EXTERN);
	                //Falls Datei gelöscht wurde, Abbruch
	                if (false === $arrImageSize)
	                {
	                    $arrImageSize[2] = 0;
	                    $this->log('Banner Image with ID "'.$objBanners->id.'" not found', 'BannerHelper getSingleBanner', TL_ERROR);
	                    break;
	                }
	                //Banner Neue Größe 0:$Width 1:$Height
	                $arrNewSizeValues = deserialize($objBanners->banner_imgSize);
	                //Banner Neue Größe ermitteln, return array $Width,$Height,$oriSize
	                $arrImageSizenNew = $this->BannerImage->getBannerImageSizeNew($arrImageSize[0],$arrImageSize[1],$arrNewSizeValues[0],$arrNewSizeValues[1]);
	                //Umwandlung bei Parametern
	                $FileSrc = html_entity_decode($objBanners->banner_image_extern, ENT_NOQUOTES, 'UTF-8');

	                $arrImageSize[0] = $arrImageSizenNew[0];
	                $arrImageSize[1] = $arrImageSizenNew[1];
	                $arrImageSize[3] = ' height="'.$arrImageSizenNew[1].'" width="'.$arrImageSizenNew[0].'"';
	                break;
	            case self::BANNER_TYPE_TEXT :
	                $arrImageSize = false;
	                break;
	        }

	        if ($arrImageSize !== false) //Bilder extern/intern
	        {
	            if ($this->strFormat == 'xhtml')
	            {
	                $banner_target = ($objBanners->banner_target == '1') ? LINK_BLUR : LINK_NEW_WINDOW;
	            }
	            else
	            {
	                $banner_target = ($objBanners->banner_target == '1') ? '' : ' target="_blank"';
	            }
	    
	            if ( strlen($objBanners->banner_comment) > 1 )
	            {
	                $banner_comment_pos = strpos($objBanners->banner_comment,"\n",1);
	                if ($banner_comment_pos !== false)
	                {
	                    $objBanners->banner_comment = substr($objBanners->banner_comment,0,$banner_comment_pos);
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
    	                $objBanners->banner_url = $domain . $this->generateFrontendUrl($objParent->row(), '', $objParent->language);
	                }
	            }
	    
	            //$arrImageSize[0]  eigene Breite
	            //$arrImageSize[1]  eigene Höhe
	            //$arrImageSize[3]  Breite und Höhe in der Form height="yyy" width="xxx"
	            //$arrImageSize[2]
	            // 1 = GIF, 2 = JPG, 3 = PNG
	            // 4 = SWF, 13 = SWC (zip-like swf file)
	            // 5 = PSD, 6 = BMP, 7 = TIFF(intel byte order), 8 = TIFF(motorola byte order)
	            // 9 = JPC, 10 = JP2, 11 = JPX, 12 = JB2, 13 = SWC, 14 = IFF
	            switch ($arrImageSize[2])
	            {
	                case 1:
	                case 2:
	                case 3:
	                    $arrBanners[] = array
	                    (
	                    'banner_key'     => 'bid=',
	                    'banner_wrap_id'    => $banner_cssID,
	                    'banner_wrap_class' => $banner_class,
	                    'banner_id'      => $objBanners->id,
	                    'banner_name'    => specialchars(ampersand($objBanners->banner_name)),
	                    'banner_url'     => $objBanners->banner_url,
	                    'banner_target'  => $banner_target,
	                    'banner_comment' => specialchars(ampersand($objBanners->banner_comment)),
	                    'src'            => specialchars(ampersand($FileSrc)),//specialchars(ampersand($this->urlEncode($FileSrc))),
	                    'alt'            => specialchars(ampersand($objBanners->banner_name)),
	                    'size'           => $arrImageSize[3],
	                    'banner_pic'     => true,
	                    'banner_flash'   => false,
	                    'banner_text'    => false,
	                    'banner_empty'   => false,
                        'picture'        => $picture
	                    );
	                    break;
	                case 4:  // Flash swf
	                case 13: // Flash swc
	                    list($usec, ) = explode(" ", microtime());
	    
	                    //Check for Fallback Image, only for local flash files (Path,Breite,Höhe)
	                    $src_fallback = $this->BannerImage->getCheckBannerImageFallback($FileSrc,$arrImageSize[0],$arrImageSize[1]);
	                    if ($src_fallback !== false)
	                    {
	                        //Fallback gefunden
	                        if ($this->strFormat == 'xhtml')
	                        {
	                            $fallback_content = '<img src="' . $src_fallback . '" alt="'.specialchars(ampersand($objBanners->banner_comment)).'" height="'.$arrImageSize[1].'" width="'.$arrImageSize[0].'" />';
	                        }
	                        else
	                        {
	                            $fallback_content = '<img src="' . $src_fallback . '" alt="'.specialchars(ampersand($objBanners->banner_comment)).'" height="'.$arrImageSize[1].'" width="'.$arrImageSize[0].'">';
	                        }
	                    }
	                    else
	                    {
	                        //kein Fallback
	                        if ($this->strFormat == 'xhtml')
	                        {
	                            $fallback_content = $FileSrc ."<br />". specialchars(ampersand($objBanners->banner_comment)) ."<br />". specialchars(ampersand($objBanners->banner_name));
	                        }
	                        else
	                        {
	                            $fallback_content = $FileSrc ."<br>". specialchars(ampersand($objBanners->banner_comment)) ."<br>". specialchars(ampersand($objBanners->banner_name));
	                        }
	                    }
	                    $arrBanners[] = array
	                    (
	                            'banner_key'     => 'bid=',
	                            'banner_wrap_id'    => $banner_cssID,
	                            'banner_wrap_class' => $banner_class,
	                            'banner_id'      => $objBanners->id,
	                            'banner_name'    => specialchars(ampersand($objBanners->banner_name)),
	                            'banner_url'     => $objBanners->banner_url,
	                            'banner_target'  => $banner_target,
	                            'banner_comment' => specialchars(ampersand($objBanners->banner_comment)),
	                            'swf_src'        => specialchars(ampersand($FileSrc)),
	                            'swf_width'      => $arrImageSize[0],
	                            'swf_height'     => $arrImageSize[1],
	                            'swf_id'         => round((float)$usec*100000,0).'_'.$objBanners->id,
	                            'alt'            => specialchars(ampersand($objBanners->banner_name)),
	                            'fallback_content'=> $fallback_content,
	                            'banner_pic'     => false,
	                            'banner_flash'   => true,
	                            'banner_text'    => false,
	                            'banner_empty'   => false
	                    );
	                    break;
	                default:
	                    $arrBanners[] = array
	                    (
	                    'banner_key'     => 'bid=',
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
	                    );
	                    break;
	            }//switch
	    
	            //anderes Template?
	            if (($this->banner_template != $this->strTemplate) 
	             && ($this->banner_template != ''))
	            {
	                $this->strTemplate = $this->banner_template;
	                $this->Template = new \FrontendTemplate($this->strTemplate);
	            }
	            $this->arrBannerData = $arrBanners; //wird von BannerStatViewUpdate genutzt
	            $this->setStatViewUpdate();
	            $this->Template->banners = $arrBanners;
	            return true;
	    
	        }//$arrImageSize !== false
	    
	        // Text Banner
	        if ($objBanners->banner_type == 'banner_text')
	        {
	            if ($this->strFormat == 'xhtml')
	            {
	                $banner_target = ($objBanners->banner_target == '1') ? LINK_BLUR : LINK_NEW_WINDOW;
	            }
	            else
	            {
	                $banner_target = ($objBanners->banner_target == '1') ? '' : ' target="_blank"';
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
    	                $objBanners->banner_url = $domain . $this->generateFrontendUrl($objParent->row(), '', $objParent->language);
	                }
	            }
	    
	            // Kurz URL (nur Domain)
	            $treffer = parse_url(\Idna::decode($objBanners->banner_url)); // #79
	            $banner_url_kurz = $treffer['host'];
	            if (isset($treffer['port']))
	            {
	                $banner_url_kurz .= ':'.$treffer['port'];
	            }
	    
	            $arrBanners[] = array
	            (
	                    'banner_key'     => 'bid=',
	                    'banner_wrap_id'    => $banner_cssID,
	                    'banner_wrap_class' => $banner_class,
	                    'banner_id'      => $objBanners->id,
	                    'banner_name'    => specialchars(ampersand($objBanners->banner_name)),
	                    'banner_url'     => $objBanners->banner_url,
	                    'banner_url_kurz'=> $banner_url_kurz,
	                    'banner_target'  => $banner_target,
	                    'banner_comment' => ampersand(nl2br($objBanners->banner_comment)),
	                    'banner_pic'     => false,
	                    'banner_flash'   => false,
	                    'banner_text'    => true,
	                    'banner_empty'   => false	// issues 733
	            );
	            if (($this->banner_template != $this->strTemplate) 
	             && ($this->banner_template != '')) 
	            {
	                $this->strTemplate = $this->banner_template;
	                $this->Template = new \FrontendTemplate($this->strTemplate);
	            }
	            $arrResults[] = $arrBanners[0];
	            $this->Template->banners = $arrResults;
	             
	            $this->arrBannerData = $arrResults;
	            $this->setStatViewUpdate();
	            return true;
	        }
	    }//Banner vorhanden
	    //falls $arrImageSize = false  und kein Text Banner
	    $this->Template->banners = $arrBanners; // leeres array
	}
	
	protected function getMultiBanner()
	{
	    /* $this->arrCategoryValues[...]
	     * banner_random
		 * banner_limit         - 0 all, other:max
	     */
	    
	    reset($this->arrAllBannersBasic); //sicher ist sicher
	     
	    //RandomBlocker entfernen falls möglich und nötig
	    
        // einer muss mindestens übrig bleiben
	    if ( count($this->arrAllBannersBasic) >1                
	         // bei Alle Banner anzeigen (0) nichts entfernen
	         && $this->arrCategoryValues['banner_limit'] >0  
	         // nur wenn mehr Banner übrig als per limit festgelegt
	         && ( count($this->arrAllBannersBasic) > $this->arrCategoryValues['banner_limit'] )
  	       )
	    {
	        $intRandomBlockerID = $this->getRandomBlockerId();
	        if (isset($this->arrAllBannersBasic[$intRandomBlockerID]))
	        {
	            unset($this->arrAllBannersBasic[$intRandomBlockerID]);
	        }
	    }
	    
	    if ( $this->arrCategoryValues['banner_random'] == 1 ) 
	    {
	        $this->shuffleAssoc($this->arrAllBannersBasic);
	    }
	    
	    //wenn limit gesetzt, array arrAllBannersBasic dezimieren
	    if ( $this->arrCategoryValues['banner_limit'] >0 ) 
	    {
	        $del = count($this->arrAllBannersBasic) - $this->arrCategoryValues['banner_limit'];
	        for ($i = 0; $i < $del; $i++) 
	        {
	            array_pop($this->arrAllBannersBasic);
	        }
	    }

	    //Rest soll nun angezeigt werden.
	    //Schleife
	    while ( list($banner_id, $banner_weigth) = each($this->arrAllBannersBasic) )
	    {
	        unset($banner_weigth);
	        $objBanners  = \Database::getInstance()
                                ->prepare("SELECT
                                                TLB.*
                                           FROM
                                	            tl_banner AS TLB
                                           WHERE
                                                TLB.`id`=?"
                                         )
                                ->limit(1)
                                ->execute( $banner_id );
	        $intRows = $objBanners->numRows;
	        //Banner vorhanden?
	        if($intRows > 0)
	        {
	            $arrBanners = array();
	            $objBanners->next();
	            self::$arrBannerSeen[] = $objBanners->id;
	            //CSS-ID/Klasse(n) je Banner, für den wrapper
	            $banner_cssID   = '';
	            $banner_class   = '';
	            $banner_classes = '';
	            $_cssID = deserialize($objBanners->banner_cssid);
	            if ( is_array($_cssID) )
	            {
	                if ($_cssID[0] != '')
	                {
	                    $banner_cssID   = ' id="banner_'.$_cssID[0].'"';
	                }
	                if ($_cssID[1] != '')
	                {
	                    $banner_classes = explode(" ", $_cssID[1]);
	                    foreach ($banner_classes as $banner_classone)
	                    {
	                        $banner_class .= ' banner_'.$banner_classone;
	                    }
	                }
	            }
	             
	            if (!$this->statusRandomBlocker) 
	            {
	                //Random Blocker setzen für den ersten Banner
	                $this->setRandomBlockerId($banner_id);
	            }
	            
	            switch ($objBanners->banner_type)
	            {
	                case self::BANNER_TYPE_INTERN :
	                    //Pfad+Dateiname holen ueber UUID (findByPk leitet um auf findByUuid)
	                    $objFile = \FilesModel::findByPk($objBanners->banner_image);
	                    //BannerImage Class
	                    $this->BannerImage = new \Banner\BannerImage();
	                    //Banner Art und Größe bestimmen
	                    $arrImageSize = $this->BannerImage->getBannerImageSize($objFile->path, self::BANNER_TYPE_INTERN);
	                    //Falls Datei gelöscht wurde, Abbruch
	                    if (false === $arrImageSize)
	                    {
	                        $arrImageSize[2] = 0;
	                        $this->log('Banner Image with ID "'.$objBanners->id.'" not found', 'BannerHelper getMultiBanner', TL_ERROR);
	                        break;
	                    }
	                    //Banner Neue Größe 0:$Width 1:$Height
	                    $arrNewSizeValues = deserialize($objBanners->banner_imgSize);
	                    //Banner Neue Größe ermitteln, return array $Width,$Height,$oriSize
	                    $arrImageSizenNew = $this->BannerImage->getBannerImageSizeNew($arrImageSize[0],$arrImageSize[1],$arrNewSizeValues[0],$arrNewSizeValues[1]);
	                     
	                    //wenn oriSize = true, oder bei GIF/SWF/SWC = original Pfad nehmen
	                    if ($arrImageSizenNew[2] === true
	                            || $arrImageSize[2] == 1  // GIF
	                            || $arrImageSize[2] == 4  // SWF
	                            || $arrImageSize[2] == 13 // SWC
	                    )
	                    {
	                        $FileSrc = $objFile->path;
	                        $arrImageSize[0] = $arrImageSizenNew[0];
	                        $arrImageSize[1] = $arrImageSizenNew[1];
	                        $arrImageSize[3] = ' height="'.$arrImageSizenNew[1].'" width="'.$arrImageSizenNew[0].'"';
	                        
	                        //fake the Picture::create
	                        $picture['img']   = array
	                        (
	                            'src'    => specialchars(ampersand($FileSrc)),
	                            'width'  => $arrImageSizenNew[0],
	                            'height' => $arrImageSizenNew[1],
	                            'srcset' => specialchars(ampersand($FileSrc))
	                        );
	                        $picture['alt']   = specialchars(ampersand($objBanners->banner_name));
	                        $picture['title'] = specialchars(ampersand($objBanners->banner_comment));
	                        
	                        ModuleBannerLog::writeLog(__METHOD__ , __LINE__ , 'Orisize Picture: '. print_r($picture,true));
	                    }
	                    else
	                    {
	                        $FileSrc = \Image::get($this->urlEncode($objFile->path), $arrImageSizenNew[0], $arrImageSizenNew[1],'proportional');
 
	                        $picture = \Picture::create($this->urlEncode($objFile->path), array($arrImageSizenNew[0], $arrImageSizenNew[1], $arrNewSizeValues[2]))->getTemplateData();
	                        $picture['alt']   = specialchars(ampersand($objBanners->banner_name));
	                        $picture['title'] = specialchars(ampersand($objBanners->banner_comment));
	                        
	                        ModuleBannerLog::writeLog(__METHOD__ , __LINE__ , 'Resize Picture: '. print_r($picture,true));

	                        $arrImageSize[0] = $arrImageSizenNew[0];
	                        $arrImageSize[1] = $arrImageSizenNew[1];
	                        $arrImageSize[3] = ' height="'.$arrImageSizenNew[1].'" width="'.$arrImageSizenNew[0].'"';
	                    }
	                    break;
	                case self::BANNER_TYPE_EXTERN :
	                    //BannerImage Class
	                    $this->BannerImage = new \Banner\BannerImage();
	                    //Banner Art und Größe bestimmen
	                    $arrImageSize = $this->BannerImage->getBannerImageSize($objBanners->banner_image_extern, self::BANNER_TYPE_EXTERN);
	                    //Falls Datei gelöscht wurde, Abbruch
	                    if (false === $arrImageSize)
	                    {
	                        $arrImageSize[2] = 0;
	                        $this->log('Banner Image with ID "'.$objBanners->id.'" not found', 'BannerHelper getMultiBanner', TL_ERROR);
	                        break;
	                    }
	                    //Banner Neue Größe 0:$Width 1:$Height
	                    $arrNewSizeValues = deserialize($objBanners->banner_imgSize);
	                    //Banner Neue Größe ermitteln, return array $Width,$Height,$oriSize
	                    $arrImageSizenNew = $this->BannerImage->getBannerImageSizeNew($arrImageSize[0],$arrImageSize[1],$arrNewSizeValues[0],$arrNewSizeValues[1]);
	                    //Umwandlung bei Parametern
	                    $FileSrc = html_entity_decode($objBanners->banner_image_extern, ENT_NOQUOTES, 'UTF-8');
	                    
	                    //fake the Picture::create
	                    $picture['img']   = array
	                    (
	                    	'src'    => specialchars(ampersand($FileSrc)),
	                        'width'  => $arrImageSizenNew[0],
	                        'height' => $arrImageSizenNew[1],
	                        'srcset' => specialchars(ampersand($FileSrc))
	                    );
	                    $picture['alt']   = specialchars(ampersand($objBanners->banner_name));
	                    $picture['title'] = specialchars(ampersand($objBanners->banner_comment));
	                    
	                    $arrImageSize[0] = $arrImageSizenNew[0];
	                    $arrImageSize[1] = $arrImageSizenNew[1];
	                    $arrImageSize[3] = ' height="'.$arrImageSizenNew[1].'" width="'.$arrImageSizenNew[0].'"';
	                    break;
	                case self::BANNER_TYPE_TEXT :
	                    $arrImageSize = false;
	                    break;
	            }

	            if ($arrImageSize !== false) //Bilder extern/intern
	            {
	                if ($this->strFormat == 'xhtml')
	                {
	                    $banner_target = ($objBanners->banner_target == '1') ? LINK_BLUR : LINK_NEW_WINDOW;
	                }
	                else
	                {
	                    $banner_target = ($objBanners->banner_target == '1') ? '' : ' target="_blank"';
	                }
	                 
	                if ( strlen($objBanners->banner_comment) > 1 )
	                {
	                    $banner_comment_pos = strpos($objBanners->banner_comment,"\n",1);
	                    if ($banner_comment_pos !== false)
	                    {
	                        $objBanners->banner_comment = substr($objBanners->banner_comment,0,$banner_comment_pos);
	                    }
	                }
	                 
	                // Banner Seite als Ziel?
	                if ($objBanners->banner_jumpTo > 0)
	                {
	                    $objBanners->banner_url = ''; //default
	                    $domain = \Environment::get('base');
	                    $objParent = \PageModel::findWithDetails($objBanners->banner_jumpTo);
	                    if ($objParent !== null) 
	                    {
    	                    if ($objParent->domain != '')
    	                    {
    	                        $domain = (\Environment::get('ssl') ? 'https://' : 'http://') . $objParent->domain . TL_PATH . '/';
    	                    }
    	                    $objBanners->banner_url = $domain . $this->generateFrontendUrl($objParent->row(), '', $objParent->language);
	                    }
	                }
	                
	                //$arrImageSize[0]  eigene Breite
	                //$arrImageSize[1]  eigene Höhe
	                //$arrImageSize[3]  Breite und Höhe in der Form height="yyy" width="xxx"
	                //$arrImageSize[2]
	                // 1 = GIF, 2 = JPG, 3 = PNG
	                // 4 = SWF, 13 = SWC (zip-like swf file)
	                // 5 = PSD, 6 = BMP, 7 = TIFF(intel byte order), 8 = TIFF(motorola byte order)
	                // 9 = JPC, 10 = JP2, 11 = JPX, 12 = JB2, 13 = SWC, 14 = IFF
	                switch ($arrImageSize[2])
	                {
	                    case 1:
	                    case 2:
	                    case 3:
	                        $arrBanners[] = array
	                        (
	                        'banner_key'     => 'bid=',
	                        'banner_wrap_id'    => $banner_cssID,
	                        'banner_wrap_class' => $banner_class,
	                        'banner_id'      => $objBanners->id,
	                        'banner_name'    => specialchars(ampersand($objBanners->banner_name)),
	                        'banner_url'     => $objBanners->banner_url,
	                        'banner_target'  => $banner_target,
	                        'banner_comment' => specialchars(ampersand($objBanners->banner_comment)),
	                        'src'            => specialchars(ampersand($FileSrc)),//specialchars(ampersand($this->urlEncode($FileSrc))),
	                        'alt'            => specialchars(ampersand($objBanners->banner_name)),
	                        'size'           => $arrImageSize[3],
	                        'banner_pic'     => true,
	                        'banner_flash'   => false,
	                        'banner_text'    => false,
	                        'banner_empty'   => false,
                            'picture'        => $picture
	                        );
	                        $picture = null; unset($picture);
	                        break;
	                    case 4:  // Flash swf
	                    case 13: // Flash swc
	                        list($usec, ) = explode(" ", microtime());
	                         
	                        //Check for Fallback Image, only for local flash files (Path,Breite,Höhe)
	                        $src_fallback = $this->BannerImage->getCheckBannerImageFallback($FileSrc,$arrImageSize[0],$arrImageSize[1]);
	                        if ($src_fallback !== false)
	                        {
	                            //Fallback gefunden
	                            if ($this->strFormat == 'xhtml')
	                            {
	                                $fallback_content = '<img src="' . $src_fallback . '" alt="'.specialchars(ampersand($objBanners->banner_comment)).'" height="'.$arrImageSize[1].'" width="'.$arrImageSize[0].'" />';
	                            }
	                            else
	                            {
	                                $fallback_content = '<img src="' . $src_fallback . '" alt="'.specialchars(ampersand($objBanners->banner_comment)).'" height="'.$arrImageSize[1].'" width="'.$arrImageSize[0].'">';
	                            }
	                        }
	                        else
	                        {
	                            //kein Fallback
	                            if ($this->strFormat == 'xhtml')
	                            {
	                                $fallback_content = $FileSrc ."<br />". specialchars(ampersand($objBanners->banner_comment)) ."<br />". specialchars(ampersand($objBanners->banner_name));
	                            }
	                            else
	                            {
	                                $fallback_content = $FileSrc ."<br>". specialchars(ampersand($objBanners->banner_comment)) ."<br>". specialchars(ampersand($objBanners->banner_name));
	                            }
	                        }
	                        $arrBanners[] = array
	                        (
	                                'banner_key'     => 'bid=',
    	                            'banner_wrap_id'    => $banner_cssID,
    	                            'banner_wrap_class' => $banner_class,
	                                'banner_id'      => $objBanners->id,
	                                'banner_name'    => specialchars(ampersand($objBanners->banner_name)),
	                                'banner_url'     => $objBanners->banner_url,
	                                'banner_target'  => $banner_target,
	                                'banner_comment' => specialchars(ampersand($objBanners->banner_comment)),
	                                'swf_src'        => specialchars(ampersand($FileSrc)),
	                                'swf_width'      => $arrImageSize[0],
	                                'swf_height'     => $arrImageSize[1],
	                                'swf_id'         => round((float)$usec*100000,0).'_'.$objBanners->id,
	                                'alt'            => specialchars(ampersand($objBanners->banner_name)),
	                                'fallback_content'=> $fallback_content,
	                                'banner_pic'     => false,
	                                'banner_flash'   => true,
	                                'banner_text'    => false,
	                                'banner_empty'   => false
	                        );
	                        break;
	                    default:
	                        $arrBanners[] = array
	                        (
	                        'banner_key'     => 'bid=',
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
	                        );
	                        break;
	                }//switch
	                $arrResults[] = $arrBanners[0];
	                
	                $this->arrBannerData = $arrBanners; //wird von setStatViewUpdate genutzt
	                $this->setStatViewUpdate();
	            }//$arrImageSize !== false
	             
	            // Text Banner
	            if ($objBanners->banner_type == 'banner_text')
	            {
	                if ($this->strFormat == 'xhtml')
	                {
	                    $banner_target = ($objBanners->banner_target == '1') ? LINK_BLUR : LINK_NEW_WINDOW;
	                }
	                else
	                {
	                    $banner_target = ($objBanners->banner_target == '1') ? '' : ' target="_blank"';
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
    	                    $objBanners->banner_url = $domain . $this->generateFrontendUrl($objParent->row(), '', $objParent->language);
	                    }
	                }
	                 
	                // Kurz URL (nur Domain)
	                $treffer = parse_url(\Idna::decode($objBanners->banner_url)); // #79
	                $banner_url_kurz = $treffer['host'];
	                if (isset($treffer['port']))
	                {
	                    $banner_url_kurz .= ':'.$treffer['port'];
	                }
	                 
	                $arrBanners[] = array
	                (
	                        'banner_key'     => 'bid=',
    	                    'banner_wrap_id'    => $banner_cssID,
    	                    'banner_wrap_class' => $banner_class,
	                        'banner_id'      => $objBanners->id,
	                        'banner_name'    => specialchars(ampersand($objBanners->banner_name)),
	                        'banner_url'     => $objBanners->banner_url,
	                        'banner_url_kurz'=> $banner_url_kurz,
	                        'banner_target'  => $banner_target,
	                        'banner_comment' => ampersand(nl2br($objBanners->banner_comment)),
	                        'banner_pic'     => false,
	                        'banner_flash'   => false,
	                        'banner_text'    => true,
	                        'banner_empty'   => false	// issues 733
	                );
	                
	                $arrResults[] = $arrBanners[0];
	            
	                $this->arrBannerData = $arrBanners; //wird von setStatViewUpdate genutzt
	                $this->setStatViewUpdate();
	                
	            }//text banner
	            
	        }//Banner vorhanden
	    } // while each($this->arrAllBannersBasic)
	    
	    //anderes Template?
	    if (($this->banner_template != $this->strTemplate) 
	     && ($this->banner_template != ''))
	    {
	        $this->strTemplate = $this->banner_template;
	        $this->Template = new \FrontendTemplate($this->strTemplate);
	    }
	    
	    //falls $arrImageSize = false  und kein Text Banner ist es ein leeres array
	    $this->Template->banners = $arrResults;
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
    
        foreach($keys as $key) 
        {
            $new[$key] = $array[$key];
            unset($array[$key]); /* save memory */
        }
        $array = $new;
        
        return true;
    }
    

/*
   _____                  _   _                      __         _                     
  / ____|                | | (_)                    / _|       (_)                    
 | |     ___  _   _ _ __ | |_ _ _ __   __ _    ___ | |_  __   ___  _____      _____   
 | |    / _ \| | | | '_ \| __| | '_ \ / _` |  / _ \|  _| \ \ / / |/ _ \ \ /\ / / __|  
 | |___| (_) | |_| | | | | |_| | | | | (_| | | (_) | |    \ V /| |  __/\ V  V /\__ \ 
  \_____\___/ \__,_|_| |_|\__|_|_| |_|\__, |  \___/|_|     \_/ |_|\___| \_/\_/ |___/
                                       __/ |                                          
   & Blocking                         |___/       
*/	
	
	/**
	 * Insert/Update Banner View Stat
	 */
	protected function setStatViewUpdate()
	{
	    if ($this->bannerCheckBot() === true)
	    {
	        return; //Bot gefunden, wird nicht gezaehlt
	    }
	    if ($this->checkUserAgent() === true)
	    {
	        return ; //User Agent Filterung
	    }
	    
	    // Blocker
	    $lastBanner = array_pop($this->arrBannerData);
	    $BannerID = $lastBanner['banner_id'];
	    if ($BannerID==0)
	    { // kein Banner, nichts zu tun
	        return;
	    }
	    
	    if ( $this->getStatViewUpdateBlockerId($BannerID) === true )
	    {
	        // Eintrag innerhalb der Blockzeit
	        return; // blocken, nicht zählen, raus hier
	    }
	    else
	    {
	        // nichts geblockt, also blocken fürs den nächsten Aufrufs
	        $this->setStatViewUpdateBlockerId($BannerID);
	    }
	    
	    //Zählung, Insert
	    $arrSet = array
	    (
            'id' => $BannerID,
            'tstamp' => time(),
            'banner_views' => 1
	    );
	    $objInsert = \Database::getInstance()->prepare("INSERT IGNORE INTO tl_banner_stat %s")
                                             ->set($arrSet)
                                             ->executeUncached();
	    if ($objInsert->insertId == 0)
	    {
	        //Zählung, Update
	        \Database::getInstance()->prepare("UPDATE 
                                                    `tl_banner_stat` 
                                               SET 
                                                    `tstamp`=?
                                                  , `banner_views` = `banner_views`+1 
                                               WHERE 
                                                    `id`=?")
                                    ->executeUncached(time(), $BannerID);
	    }
	    
	}//BannerStatViewUpdate()

	/**
	 * StatViewUpdate Blocker, Set Banner ID and timestamp
	 *
	 * @param integer    $banner_id
	 */
	protected function setStatViewUpdateBlockerId($banner_id=0)
	{
	    if ($banner_id==0) { return; }// keine Banner ID, nichts zu tun
    	//das können mehrere sein!, mergen!
	    $this->setSession('StatViewUpdateBlocker'.$this->module_id, array( $banner_id => time() ), true );
	    return ;
	}
	
	/**
	 * StatViewUpdate Blocker, Get Banner ID if the timestamp ....
	 *
	 * @param boolean    true if blocked | false
	 */
	protected function getStatViewUpdateBlockerId($banner_id=0)
	{
	    $this->getSession('StatViewUpdateBlocker'.$this->module_id);
	    if ( count($this->_session) )
	    {
	        reset($this->_session);
	        while ( list($key, $val) = each($this->_session) )
	        {
	            if ( $key == $banner_id && 
	                 $this->removeStatViewUpdateBlockerId($key, $val) === true )
	            {
	                // Key ist noch gültig und es muss daher geblockt werden
	                //DEBUG log_message('getStatViewUpdateBlockerId Banner ID:'.$key,'Banner.log');
	                return true;
	            }
	        }
	    }
	    return false;
	}
	
	/**
	 * StatViewUpdate Blocker, Remove old Banner ID
	 *
	 * @param  integer    $banner_id
	 * @return boolean    true = Key is valid, it must be blocked | false = key is invalid
	 */
	protected function removeStatViewUpdateBlockerId($banner_id, $tstmap)
	{
		$BannerBlockTime = time() - 60*5;  // 5 Minuten, 0-5 min wird geblockt
	    if ( isset($GLOBALS['TL_CONFIG']['mod_banner_block_time'] ) 
	     && intval($GLOBALS['TL_CONFIG']['mod_banner_block_time'])>0
	       )
	    {
	        $BannerBlockTime = time() - 60*1*intval($GLOBALS['TL_CONFIG']['mod_banner_block_time']);
	    }
	     
	    if ( $tstmap >  $BannerBlockTime )
	    {
	        return true;
	    }
	    else
	    {
	        //wenn mehrere dann nur den Teil, nicht die ganze Session
	        unset($this->_session[$banner_id]);
	        //wenn Anzahl Banner in Session nun 0 dann Session loeschen
	        if ( count($this->_session) == 0 ) 
	        {
	            //komplett löschen
	            \Session::getInstance()->remove('StatViewUpdateBlocker'.$this->module_id);
	        }
	        else //sonst neu setzen
	        {
    	        //gekuerzte Session neu setzen
	            $this->setSession('StatViewUpdateBlocker'.$this->module_id, $this->_session , false );
	        }
	    }
	    return false;
	}
	
	
	/**
	 * Spider Bot Check
	 */
	protected function bannerCheckBot()
	{
	    if (isset($GLOBALS['TL_CONFIG']['mod_banner_bot_check']) 
	      && (int)$GLOBALS['TL_CONFIG']['mod_banner_bot_check'] == 0
	       )
	    {
	        //DEBUG log_message('bannerCheckBot abgeschaltet','Banner.log');
	        return false; //Bot Suche abgeschaltet ueber localconfig.php
	    }
	    if (!in_array('botdetection', \ModuleLoader::getActive()) )
	    {
	        //botdetection Modul fehlt, Abbruch
	        $this->log('BotDetection extension required!', 'ModulBanner bannerCheckBot', TL_ERROR);
	        return false;
	    }
	    // Import Helperclass ModuleBotDetection
	    $this->ModuleBotDetection = new \BotDetection\ModuleBotDetection();
	    if ($this->ModuleBotDetection->BD_CheckBotAgent() || $this->ModuleBotDetection->BD_CheckBotIP())
	    {
	        //DEBUG log_message('bannerCheckBot True','Banner.log');
	        return true;
	    }
	    //DEBUG log_message('bannerCheckBot False','Banner.log');
	    return false;
	} //bannerCheckBot
	
	/**
	 * HTTP_USER_AGENT Special Check
	 */
	protected function checkUserAgent()
	{
	    if ( \Environment::get('httpUserAgent') )  
	    {
	        $UserAgent = trim(\Environment::get('httpUserAgent'));
	    } 
	    else 
	    {
	        return false; // Ohne Absender keine Suche
	    }
	    $arrUserAgents = explode(",", $this->banner_useragent);
	    if (strlen(trim($arrUserAgents[0])) == 0) 
	    {
	        return false; // keine Angaben im Modul
	    }
	    array_walk($arrUserAgents, array('self','trimBannerArrayValue'));  // trim der array values
	    // grobe Suche
	    $CheckUserAgent = str_replace($arrUserAgents, '#', $UserAgent);
	    if ($UserAgent != $CheckUserAgent) 
	    {   // es wurde ersetzt also was gefunden
	        //DEBUG log_message('CheckUserAgent Filterung; Treffer!','Banner.log');
	        return true;
	    }
	    return false;
	} //checkUserAgent
	public static function trimBannerArrayValue(&$data) 
	{
	    $data = trim($data);
	    return ;
	}
	
	/**
	 * Get session 
	 *
	 * @param string   $session_name   e.g.: 'RandomBlocker'
	 * @return void
	 * @access protected
	 */
	protected function getSession( $session_name )
	{
	    $this->_session = (array)\Session::getInstance()->get( $session_name );
	}
	
	/**
	 * Set session
	 * 
	 * @param string   $session_name   e.g.: 'RandomBlocker' 
	 * @param array    $arrData        array('key' => array(Value1,Value2,...))
	 * @return void
	 * @access protected 
	 */
	protected function setSession( $session_name, $arrData, $merge = false )
	{
	    if ($merge) 
	    {
	        $this->_session = \Session::getInstance()->get( $session_name );
	        
	        // numerische Schlüssel werden neu numeriert, daher
	        // geht nicht: array_merge($this->_session, $arrData)
	        $merge_array = (array)$this->_session + $arrData;
	        \Session::getInstance()->set( $session_name, $merge_array );
	    }
	    else 
	    {
	        \Session::getInstance()->set( $session_name, $arrData );
	    }
	    
	}
	
} // class

