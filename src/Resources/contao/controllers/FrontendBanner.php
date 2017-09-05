<?php

/**
 * @copyright  Glen Langer 2017 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Banner
 * @license    LGPL-3.0+
 * @see	       https://github.com/BugBuster1701/contao-banner-bundle
 */

namespace BugBuster\Banner;

use Symfony\Component\HttpFoundation\Response;

/**
 * Front end banner controller
 *
 * @author     Glen Langer (BugBuster)
 */
class FrontendBanner extends \Frontend
{
    /**
     * Banner ID
     * @var int
     */
    protected $intBID;
    protected $intDEFBID;
    
    /**
     * Session
     *
     * @var string
     * @access private
     */
    private $_session   = array();
    
	/**
	 * Initialize the object (do not remove)
	 */
	public function __construct()
	{
		parent::__construct();

		// See #4099
		if (!defined('BE_USER_LOGGED_IN'))
		{
			define('BE_USER_LOGGED_IN', false);
		}

		if (!defined('FE_USER_LOGGED_IN'))
		{
			define('FE_USER_LOGGED_IN', false);
		}
	
		$this->intBID    = (int)\Input::get('bid');
		$this->intDEFBID = (int)\Input::get('defbid');
		
		//\System::loadLanguageFile('tl_visitors');
	}


	/**
	 * Run the controller
	 *
	 * @return Response
	 */
	public function run()
	{
        // Input a digit >0 ?
	    if ( 0 == $this->intBID )
	    {
	        if ( 0 == $this->intDEFBID )
	        {
	            //header('HTTP/1.1 501 Not Implemented');
	            //throw new \ErrorException('Invalid Banner ID (' . \Input::get('bid') . ')',2,1,basename(__FILE__),__LINE__);
	            $objResponse = new Response( 'Invalid Banner ID (' . \Input::get('bid') . ')' , 501);
	            return $objResponse;
	        }
	    }
	    
	    //Banner oder Kategorie Banner (Default Banner)
	    if ( 0 < $this->intBID )
	    {
	        //normaler Banner
	        $banner_not_viewed = false;
	        // Check whether the Banner ID exists
	        $objBanners = \Database::getInstance()
                                    ->prepare("SELECT
                                                tb.id
                                              , tb.banner_url
                                              , tb.banner_jumpTo
                                              , tbs.banner_clicks
                                           FROM
                                                tl_banner tb
                                              , tl_banner_stat tbs
                                           WHERE
                                                tb.id=tbs.id
                                           AND
                                                tb.id=?")
                                    ->execute($this->intBID);
	    
            if (!$objBanners->next())
            {
                $objBanners = \Database::getInstance()
                                        ->prepare("SELECT
                                                    tb.id
                                                  , tb.banner_url
                                                  , tb.banner_jumpTo
                                               FROM
                                                    tl_banner tb
                                               WHERE
                                                    tb.id=?")
                                        ->execute($this->intBID);
	                                                         
                if (!$objBanners->next())
                {
                    //header('HTTP/1.1 501 Not Implemented');
                    //throw new \ErrorException('Banner ID not found',2,1,basename(__FILE__),__LINE__);
                    $objResponse = new Response( 'Banner ID not found' , 501);
                    return $objResponse;
                }
                else
                {
                    $banner_not_viewed = true;
                }
            }
	    
            $banner_stat_update = false;
            if (   $this->checkUserAgent()       === false
                && $this->checkBot()             === false
                && $this->getSetReClickBlocker() === false
                )
            {
                // keine User Agent Filterung
                // kein Bot
                // kein ReClick
                $banner_stat_update = true;
            }
	    
            if ($banner_stat_update === true)
            {
                if ($banner_not_viewed === false)
                {
                    //Update
                    $tstamp = time();
                    $banner_clicks = $objBanners->banner_clicks + 1;
                    \Database::getInstance()->prepare("UPDATE
                                                            tl_banner_stat
                                                       SET
                                                            tstamp=?
                                                          , banner_clicks=?
                                                       WHERE
                                                            id=?")
                                            ->execute($tstamp, $banner_clicks, $this->intBID);
                }
                else
                {
                    //Insert
                    $arrSet = array
                    (
                        'id'     => $this->intBID,
                        'tstamp' => time(),
                        'banner_clicks' => 1
                    );
                    \Database::getInstance()->prepare("INSERT IGNORE INTO tl_banner_stat %s")
                                            ->set($arrSet)
                                            ->execute();
                }
            }
	    
            //Banner Ziel per Page?
            if ($objBanners->banner_jumpTo >0)
            {
                //url generieren
                $objBannerNextPage = \Database::getInstance()
                                                ->prepare("SELECT
                                                                id
                                                              , alias
                                                           FROM
                                                                tl_page
                                                           WHERE
                                                                id=?")
                                                ->limit(1)
                                                ->execute($objBanners->banner_jumpTo);

                if ($objBannerNextPage->numRows)
                {
                    $objPage = \PageModel::findWithDetails($objBanners->banner_jumpTo);
                    $objBanners->banner_url = \Controller::generateFrontendUrl($objBannerNextPage->fetchAssoc(),
                                                                                null,
                                                                                $objPage->rootLanguage);
                }
            }
            $banner_redirect = $this->getRedirectType($this->intBID);
	    }
	    else
	    {
	        // Default Banner from Category
	        // Check whether the Banner ID exists
	        $objBanners = \Database::getInstance()
                                    ->prepare("SELECT
                                                    id
                                                  , banner_default_url AS banner_url
                                               FROM
                                                    tl_banner_category
                                               WHERE
                                                    id=?")
                                    ->execute($this->intDEFBID);
	    
            if (!$objBanners->next())
            {
                //header('HTTP/1.1 501 Not Implemented');
                //throw new \ErrorException('Default Banner ID not found',2,1,basename(__FILE__),__LINE__);
                $objResponse = new Response( 'Default Banner ID not found' , 501);
                return $objResponse;
            }
            $banner_redirect = '303';
	    }
	    $banner_url = ampersand($objBanners->banner_url);
	    // 301 Moved Permanently
	    // 302 Found
	    // 303 See Other
	    // 307 Temporary Redirect (ab Contao 3.1)
	    $this->redirect($banner_url, $banner_redirect);
    }
    
    /**
     * Search Banner Redirect Definition
     *
     * @return int	301,302
     *
     */
    protected function getRedirectType($BID)
    {
        // aus BID die CatID
        // über CatID in tl_module.banner_categories die tl_module.banner_redirect
        // Schleife über alle zeilen, falls mehrere
        $objCat = \Database::getInstance()->prepare("SELECT
                                                        pid as CatID
                                                     FROM
                                                        `tl_banner`
                                                     WHERE
                                                        id=?")
                                        ->execute($BID);
	    
        if (0 == $objCat->numRows)
        {
            return '301'; // error, but the show must go on
        }
        $objCat->next();
        $objBRT = \Database::getInstance()->prepare("SELECT
                                                        `banner_categories`
                                                       ,`banner_redirect`
                                                     FROM
                                                        `tl_module`
                                                     WHERE
                                                        type=?
                                                     AND
                                                        banner_categories=?")
                                        ->execute('banner', $objCat->CatID);
        if (0 == $objBRT->numRows)
        {
            return '301'; // error, but the show must go on
        }
        $arrBRT = array();
        while ($objBRT->next())
        {
            $arrBRT[] = ($objBRT->banner_redirect == 'temporary') ? '302' : '301';
        }
        if (count($arrBRT) == 1)
        {
            return $arrBRT[0];	// Nur ein Modul importiert, eindeutig
        }
        else
        {
            // mindestens 2 FE Module mit derselben Kategorie, zaehlen
            $anz301=$anz302=0;
            foreach ($arrBRT as $type)
            {
                if ($type=='301')
                {
                    $anz301++;
                }
                else
                {
                    $anz302++;
                }
            }
            if ($anz301 >= $anz302)
            {		// 301 hat bei Gleichstand Vorrang
                return '301';
            }
            else
            {
                return '302';
            }
        }
    }
	    
    /**
     * ReClick Blocker
     *
     * @return bool    false/true  =   no ban / ban
     *
     */
    protected function getSetReClickBlocker()
    {
        return false; //TODO temporär
	        //$ClientIP = bin2hex(sha1(\Environment::get('remoteAddr'),true)); // sha1 20 Zeichen, bin2hex 40 zeichen
	        $BannerID = $this->intBID;
	        if ( $this->getReClickBlockerId($BannerID) === false )
	        {
	            // nichts geblockt, also blocken fürs den nächsten Aufruf
	            $this->setReClickBlockerId($BannerID);
	    
	            // kein ReClickBlocker block gefunden, Zaehlung erlaubt, nicht blocken
	            return false;
	        }
	        else
	        {
	            // Eintrag innerhalb der Blockzeit, blocken
	            return true;
	        }
    }
	    
    /**
     * Spider Bot Check
     * @return true = found, false = not found
     */
    protected function checkBot()
    {
        return false; //TODO temporär
	        if (isset($GLOBALS['TL_CONFIG']['mod_banner_bot_check'])
	            && (int)$GLOBALS['TL_CONFIG']['mod_banner_bot_check'] == 0
	            )
	        {
	            //fuer debug log_message('bannerCheckBot abgeschaltet','Banner.log');
	            return false; //Bot Suche abgeschaltet ueber localconfig.php
	        }
	        if ($this->BD_CheckBotAgent() || $this->BD_CheckBotIP())
	        {
	            //fuer debug log_message('bannerCheckBot True','Banner.log');
	            return true;
	        }
	        //fuer debug log_message('bannerCheckBot False','Banner.log');
	        return false;
    } //checkBot
	    
    /**
     * HTTP_USER_AGENT Special Check
     */
    protected function checkUserAgent()
    {
        return false; //TODO temporär
	        if ( \Environment::get('httpUserAgent') )
	        {
	            $UserAgent = trim( \Environment::get('httpUserAgent') );
	        }
	        else
	        {
	            return false; // Ohne Absender keine Suche
	        }
	         
	        $objUserAgent = \Database::getInstance()->prepare("SELECT
                                                                `banner_useragent`
                                                           FROM
                                                                `tl_module`
                                                           WHERE
                                                                `banner_useragent` !=?")
	                                                                    ->limit(1)
	                                                                    ->execute('');
	                                                                    if (!$objUserAgent->next())
	                                                                    {
	                                                                        return false; // keine Angaben im Modul
	                                                                    }
	                                                                    $arrUserAgents = explode(",", $objUserAgent->banner_useragent);
	                                                                    if (strlen(trim($arrUserAgents[0])) == 0)
	                                                                    {
	                                                                        return false; // keine Angaben im Modul
	                                                                    }
	                                                                    array_walk($arrUserAgents, array('self','bannerclickTrimArrayValue'));  // trim der array values
	                                                                    // grobe Suche
	                                                                    $CheckUserAgent=str_replace($arrUserAgents, '#', $UserAgent);
	                                                                    if ($UserAgent != $CheckUserAgent)
	                                                                    {   // es wurde ersetzt also was gefunden
	                                                                        //fuer debug log_message('CheckUserAgent Click Filterung: Treffer!','Banner.log');
	                                                                        return true;
	                                                                    }
	                                                                    return false;
    } //checkUserAgent
	
    public static function bannerclickTrimArrayValue(&$data)
    {
        $data = trim($data);
        return ;
    }
	    
    /*  _____               _
       / ____|             (_)
      | (___   ___  ___ ___ _  ___  _ __
       \___ \ / _ \/ __/ __| |/ _ \| '_ \
       ____) |  __/\__ \__ \ | (_) | | | |
      |_____/ \___||___/___/_|\___/|_| |_|s
     */
    
    /**
     * Set ReClick Blocker, Set Banner ID and timestamp
     *
     * @param integer    $banner_id
     */
    protected function setReClickBlockerId($banner_id=0)
    {
        if ($banner_id==0) { return; }// keine Banner ID, nichts zu tun
        //das können mehrere sein!, mergen!
        $this->setSession('ReClickBlocker', array( $banner_id => time() ), true );
        return ;
    }
	    
	    
    /**
     * Get ReClick Blocker, Get Banner ID if the timestamp ....
     *
     * @param boolean    true if blocked | false
     */
    protected function getReClickBlockerId($banner_id=0)
    {
        $this->getSession('ReClickBlocker');
        if ( count($this->_session) )
        {
            reset($this->_session);
            foreach ($this->_session as $key => $val)
            {
                if ( $key == $banner_id &&
                    $this->removeReClickBlockerId($key, $val) === true )
                {
                    // Key ist noch gültig und es muss daher geblockt werden
                    //fuer debug log_message('getReClickBlockerId Banner ID:'.$key,'Banner.log');
                    return true;
                }
            }
        }
        return false;
    }
	    
    /**
     * ReClick Blocker, Remove old Banner ID
     *
     * @param  integer    $banner_id
     * @return boolean    true = Key is valid, it must be blocked | false = key is invalid
     */
    protected function removeReClickBlockerId($banner_id, $tstmap)
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
                \Session::getInstance()->remove('ReClickBlocker');
            }
            else //sonst neu setzen
            {
                //gekuerzte Session neu setzen
                $this->setSession('ReClickBlocker', $this->_session , false );
            }
        }
        return false;
    }
	    
	    
	    
    /**
     * Get session
     *
     * @param string   $session_name   e.g.: 'ReClickBlocker'
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
     * @param string   $session_name   e.g.: 'ReClickBlocker'
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
	
}
