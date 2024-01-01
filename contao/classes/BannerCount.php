<?php

/*
 * This file is part of a BugBuster Contao Bundle.
 *
 * @copyright  Glen Langer 2024 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Contao Banner Bundle
 * @link       https://github.com/BugBuster1701/contao-banner-bundle
 *
 * @license    LGPL-3.0-or-later
 */

/**
 * Run in a custom namespace, so the class can be replaced
 */

namespace BugBuster\Banner;

use BugBuster\BotDetection\ModuleBotDetection;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Database;
use Contao\Environment;
use Contao\System;

/**
 * Class BannerCount
 */
class BannerCount extends System
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
			BannerLog::writeLog(__METHOD__, __LINE__, 'Bot found, is not counted');

			return; // Bot gefunden, wird nicht gezaehlt
		}
		if ($this->checkUserAgent() === true)
		{
			BannerLog::writeLog(__METHOD__, __LINE__, 'Filtering of user agents, is not counted');

			return; // User Agent Filterung
		}

		$BannerChecks = new BannerChecks();
		if ($BannerChecks->checkBE() === true)
		{
			BannerLog::writeLog(__METHOD__, __LINE__, 'Backend Login, is not counted');

			return; // Backend Login
		}
		if ($BannerChecks->checkDebug() === true)
		{
			BannerLog::writeLog(__METHOD__, __LINE__, 'Debug Modus is active, is not counted');

			return; // Debug Modus is active
		}
		$BannerChecks = null;
		unset($BannerChecks);

		// Blocker
		$lastBanner = array_pop($this->arrBannerData);
		$BannerID = $lastBanner['banner_id'];
		if ($BannerID == 0) // kein Banner, nichts zu tun
		{
			BannerLog::writeLog(__METHOD__, __LINE__, 'No banner, nothing to do');

			return;
		}

		if ($this->getStatViewUpdateBlockerId($BannerID, $this->module_id) === true)
		{
			// Eintrag innerhalb der Blockzeit
			BannerLog::writeLog(__METHOD__, __LINE__, 'Entry within the block time, is not counted');

			return; // blocken, nicht zählen, raus hier
		}
		// nichts geblockt, also blocken für den nächsten Aufruf
		BannerLog::writeLog(__METHOD__, __LINE__, 'Block for the next request', '');
		$this->setStatViewUpdateBlockerId($BannerID, $this->module_id);

		// Zählung, Insert
		$arrSet =
		array(
			'id' => $BannerID,
			'tstamp' => time(),
			'banner_views' => 1
		);
		$objInsert = Database::getInstance()->prepare("INSERT IGNORE INTO tl_banner_stat %s")
											->set($arrSet)
											->execute();
		if ($objInsert->insertId == 0)
		{
			// Zählung, Update
			Database::getInstance()->prepare("UPDATE
                            	                `tl_banner_stat`
                            	                SET
                            	                `tstamp`=?
                            	                , `banner_views` = `banner_views`+1
                            	                WHERE
                            	                `id`=?")
									->execute(time(), $BannerID);
		}
		BannerLog::writeLog(__METHOD__, __LINE__, 'Counting is done for Banner ID: ', $BannerID);
	}// BannerStatViewUpdate()

	/**
	 * StatViewUpdate Blocker, Set Banner ID and timestamp
	 *
	 * @param integer $banner_id
	 */
	protected function setStatViewUpdateBlockerId($banner_id=0)
	{
		if ($banner_id==0)
		{
			return;
		}// keine Banner ID, nichts zu tun
		// das können mehrere sein!, mergen!
		$objBannerLogic = new BannerLogic();
		$objBannerLogic->setSession('StatViewUpdateBlocker' . $this->module_id, array($banner_id => time()), true);
	}

	/**
	 * StatViewUpdate Blocker, Get Banner ID if the timestamp ....
	 *
	 * @param boolean $banner_id true if blocked | false
	 */
	protected function getStatViewUpdateBlockerId($banner_id=0)
	{
		$objBannerLogic = new BannerLogic();
		$session = (array) $objBannerLogic->getSession('StatViewUpdateBlocker' . $this->module_id);
		if (\count($session))
		{
			reset($session);

			foreach ($session as $key => $val)
			{
				if (
					$key == $banner_id
					&& true === $this->removeStatViewUpdateBlockerId($key, $val, $session)
				) {
					// Key ist noch gültig und es muss daher geblockt werden
					BannerLog::writeLog(__METHOD__, __LINE__, 'Key still valid, blocking for banner ID: ', $banner_id);

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
		if (
			isset($GLOBALS['TL_CONFIG']['mod_banner_block_time'])
		 && (int) $GLOBALS['TL_CONFIG']['mod_banner_block_time'] >0
		) {
			$BannerBlockTime = time() - 60*1*(int) $GLOBALS['TL_CONFIG']['mod_banner_block_time'];
		}

		if ($tstmap >  $BannerBlockTime)
		{
			return true;
		}
		// wenn mehrere dann nur den Teil, nicht die ganze Session
		unset($session[$banner_id]);
		// wenn Anzahl Banner in Session nun 0 dann Session loeschen
		if (\count($session) == 0)
		{
			// komplett löschen
			$objBannerLogic = new BannerLogic();
			$objBannerLogic->removeSessionKey('StatViewUpdateBlocker' . $this->module_id);
			$objBannerLogic = null;
			unset($objBannerLogic);
		// \Contao\Session::getInstance()->remove('StatViewUpdateBlocker'.$this->module_id);
		}
		else // sonst neu setzen
		{// gekuerzte Session neu setzen
			$objBannerLogic = new BannerLogic();
			$objBannerLogic->setSession('StatViewUpdateBlocker' . $this->module_id, $session, false);
			$objBannerLogic = null;
			unset($objBannerLogic);
		}

		return false;
	}

	/**
	 * Spider Bot Check
	 */
	protected function bannerCheckBot()
	{
		if (
			isset($GLOBALS['TL_CONFIG']['mod_banner_bot_check'])
		  && (int) $GLOBALS['TL_CONFIG']['mod_banner_bot_check'] == 0
		) {
			// DEBUG log_message('bannerCheckBot abgeschaltet','Banner.log');
			BannerLog::writeLog(__METHOD__, __LINE__, 'bannerCheckBot switched off');

			return false; // Bot Suche abgeschaltet ueber localconfig.php
		}

		$bundles = array_keys(System::getContainer()->getParameter('kernel.bundles')); // old \ModuleLoader::getActive()
		if (!\in_array('BugBusterBotdetectionBundle', $bundles))
		{
			// botdetection Modul fehlt, Abbruch
			BannerLog::log('contao-botdetection-bundle extension required for extension: contao-banner-bundle!', 'BannerCount::bannerCheckBot', ContaoContext::ERROR);

			return false;
		}

		// Import Helperclass ModuleBotDetection
		$ModuleBotDetection = new ModuleBotDetection();
		if ($ModuleBotDetection->checkBotAllTests())
		{
			// DEBUG log_message('bannerCheckBot True','Banner.log');
			BannerLog::writeLog(__METHOD__, __LINE__, 'bannerCheckBot True');

			return true;
		}

		// DEBUG log_message('bannerCheckBot False','Banner.log');
		BannerLog::writeLog(__METHOD__, __LINE__, 'bannerCheckBot False');

		return false;
	} // bannerCheckBot

	/**
	 * HTTP_USER_AGENT Special Check
	 */
	protected function checkUserAgent()
	{
		if (Environment::get('httpUserAgent'))
		{
			$UserAgent = trim(Environment::get('httpUserAgent'));
		}
		else
		{
			return false; // Ohne Absender keine Suche
		}
		$arrUserAgents = explode(",", $this->banner_useragent ?? '');
		if (\strlen(trim($arrUserAgents[0])) == 0)
		{
			return false; // keine Angaben im Modul
		}
		array_walk($arrUserAgents, array('self', 'trimBannerArrayValue'));  // trim der array values
		// grobe Suche
		$CheckUserAgent = str_replace($arrUserAgents, '#', $UserAgent);
		if ($UserAgent != $CheckUserAgent)   // es wurde ersetzt also was gefunden
		{// DEBUG log_message('CheckUserAgent Filterung; Treffer!','Banner.log');
			BannerLog::writeLog(__METHOD__, __LINE__, 'Filtering of user agents: Match!');

			return true;
		}

		return false;
	}

	// checkUserAgent
	public static function trimBannerArrayValue(&$data)
	{
		$data = trim($data);
	}
}
