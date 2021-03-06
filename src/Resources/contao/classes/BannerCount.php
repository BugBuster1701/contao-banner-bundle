<?php

/**
 * Extension for Contao Open Source CMS, Copyright (C) 2005-2017 Leo Feyer
 *
 * Class BannerCount - Frontend
 *
 * @copyright  Glen Langer 2017 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @licence    LGPL
 * @filesource
 * @see	       https://github.com/BugBuster1701/contao-banner-bundle
 */

/**
 * Run in a custom namespace, so the class can be replaced
 */

namespace BugBuster\Banner;

use BugBuster\Banner\BannerLog;
use BugBuster\Banner\BannerLogic;
use BugBuster\BotDetection\ModuleBotDetection;

/** 
 * Class BannerCount
 *
 * @copyright  Glen Langer 2017 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @license    LGPL
 */
class BannerCount extends \System 
{
    /**
     * Banner Data, for BannerStatViewUpdate
     */
    protected $arrBannerData = array();

    protected $banner_useragent = '';

    protected $module_id = 0;

	/**
	 * public constructor for phpunit
	 */
	public function __construct($arrBannerData, $banner_useragent, $module_id) 
	{
	    $this->arrBannerData    = $arrBannerData;
	    $this->banner_useragent = $banner_useragent;
	    $this->module_id        = $module_id;

	    parent::__construct();
	}

	/**
	 * Insert/Update Banner View Stat
	 */
	public function setStatViewUpdate()
	{
	    if ($this->bannerCheckBot() === true)
	    {
	        BannerLog::writeLog(__METHOD__, __LINE__, 'Bot gefunden, wird nicht gezaehlt');

	        return; //Bot gefunden, wird nicht gezaehlt
	    }
	    if ($this->checkUserAgent() === true)
	    {
	        BannerLog::writeLog(__METHOD__, __LINE__, 'User Agent Filterung, wird nicht gezaehlt');

	        return; //User Agent Filterung
	    }

	    $BannerChecks = new BannerChecks();
	    if ($BannerChecks->checkBE() === true) 
	    {
	        BannerLog::writeLog(__METHOD__, __LINE__, 'Backend Login, wird nicht gezaehlt');

	        return; //Backend Login
	    }
	    $BannerChecks = null; unset($BannerChecks);

	    // Blocker
	    $lastBanner = array_pop($this->arrBannerData);
	    $BannerID = $lastBanner['banner_id'];
	    if ($BannerID == 0)
	    { // kein Banner, nichts zu tun
	        BannerLog::writeLog(__METHOD__, __LINE__, 'kein Banner, nichts zu tun');

	        return;
	    }

	    if ($this->getStatViewUpdateBlockerId($BannerID, $this->module_id) === true)
	    {
	        // Eintrag innerhalb der Blockzeit
	        BannerLog::writeLog(__METHOD__, __LINE__, 'Eintrag innerhalb der Blockzeit, wird nicht gezaehlt');

	        return; // blocken, nicht zählen, raus hier
	    }
	    else
	    {
	        // nichts geblockt, also blocken für den nächsten Aufruf
	        BannerLog::writeLog(__METHOD__, __LINE__, 'blocken fuer den naechsten Aufruf');
	        $this->setStatViewUpdateBlockerId($BannerID, $this->module_id);
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
                                            ->execute();
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
                	                ->execute(time(), $BannerID);
	    }
	    BannerLog::writeLog(__METHOD__, __LINE__, 'Zaehlung erfolgt fuer Banner ID: '.$BannerID);
	}//BannerStatViewUpdate()

	/**
	 * StatViewUpdate Blocker, Set Banner ID and timestamp
	 *
	 * @param integer $banner_id
	 */
	protected function setStatViewUpdateBlockerId($banner_id=0)
	{
	    if ($banner_id==0) {
	        return;
	    }// keine Banner ID, nichts zu tun
	    //das können mehrere sein!, mergen!
	    $objBannerLogic = new BannerLogic();
	    $objBannerLogic->setSession('StatViewUpdateBlocker'.$this->module_id, array($banner_id => time()), true);

	    return;
	}

	/**
	 * StatViewUpdate Blocker, Get Banner ID if the timestamp ....
	 *
	 * @param boolean    true if blocked | false
	 */
	protected function getStatViewUpdateBlockerId($banner_id=0)
	{
	    $objBannerLogic = new BannerLogic();
	    $session = $objBannerLogic->getSession('StatViewUpdateBlocker'.$this->module_id);
	    if (\count($session))
	    {
	        reset($session);
	        foreach ($session as $key => $val)
	        {
	            if ($key == $banner_id &&
	                true === $this->removeStatViewUpdateBlockerId($key, $val, $session)
	                )
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
	 * @param  integer $banner_id
	 * @return boolean true = Key is valid, it must be blocked | false = key is invalid
	 */
	protected function removeStatViewUpdateBlockerId($banner_id, $tstmap, $session)
	{
	    $BannerBlockTime = time() - 60*5;  // 5 Minuten, 0-5 min wird geblockt
	    if (isset($GLOBALS['TL_CONFIG']['mod_banner_block_time'])
	     && (int) ($GLOBALS['TL_CONFIG']['mod_banner_block_time'])>0
	    )
	    {
	        $BannerBlockTime = time() - 60*1*(int) ($GLOBALS['TL_CONFIG']['mod_banner_block_time']);
	    }

	    if ($tstmap >  $BannerBlockTime)
	    {
	        return true;
	    }
	    else
	    {
	        //wenn mehrere dann nur den Teil, nicht die ganze Session
	        unset($session[$banner_id]);
	        //wenn Anzahl Banner in Session nun 0 dann Session loeschen
	        if (\count($session) == 0)
	        {
	            //komplett löschen
	            \Session::getInstance()->remove('StatViewUpdateBlocker'.$this->module_id);
	        }
	        else //sonst neu setzen
	        {
	            //gekuerzte Session neu setzen
	            $objBannerLogic = new BannerLogic();
                $objBannerLogic->setSession('StatViewUpdateBlocker'.$this->module_id, $session, false);
                $objBannerLogic = null;
                unset($objBannerLogic);
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
	      && (int) $GLOBALS['TL_CONFIG']['mod_banner_bot_check'] == 0
	    )
	    {
	        //DEBUG log_message('bannerCheckBot abgeschaltet','Banner.log');
	        return false; //Bot Suche abgeschaltet ueber localconfig.php
	    }

	    $bundles = array_keys(\System::getContainer()->getParameter('kernel.bundles')); // old \ModuleLoader::getActive()
	    if (!\in_array('BugBusterBotdetectionBundle', $bundles))
	    {
            //botdetection Modul fehlt, Abbruch
	        BannerLog::log('contao-botdetection-bundle extension required for extension: contao-banner-bundle!', 'BannerCount::bannerCheckBot', TL_ERROR);

	        return false;
	    }

	    // Import Helperclass ModuleBotDetection
	    $ModuleBotDetection = new ModuleBotDetection();
	    if ($ModuleBotDetection->checkBotAllTests())
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
	    if (\Environment::get('httpUserAgent'))
	    {
	        $UserAgent = trim(\Environment::get('httpUserAgent'));
	    }
	    else
	    {
	        return false; // Ohne Absender keine Suche
	    }
	    $arrUserAgents = explode(",", $this->banner_useragent);
	    if (\strlen(trim($arrUserAgents[0])) == 0)
	    {
	        return false; // keine Angaben im Modul
	    }
	    array_walk($arrUserAgents, array('self', 'trimBannerArrayValue'));  // trim der array values
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

	    return;
	}

}
