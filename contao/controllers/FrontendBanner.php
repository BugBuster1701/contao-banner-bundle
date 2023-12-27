<?php

/*
 * This file is part of a BugBuster Contao Bundle.
 *
 * @copyright  Glen Langer 2023 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Contao Banner Bundle
 * @link       https://github.com/BugBuster1701/contao-banner-bundle
 *
 * @license    LGPL-3.0-or-later
 */

namespace BugBuster\Banner;

use Contao\Database;
use Contao\Environment;
use Contao\Frontend;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Front end banner controller
 * for route: bugbuster_banner_frontend_clicks
 */
class FrontendBanner extends Frontend
{
	/**
	 * Banner ID
	 * @var int
	 */
	protected $intBID;

	protected $intDEFBID;

	/**
	 * Session
	 */
	private $_session;

	/**
	 * Initialize the object (do not remove)
	 */
	public function __construct()
	{
		parent::__construct();

		// See #4099
		if (!\defined('BE_USER_LOGGED_IN'))
		{
			\define('BE_USER_LOGGED_IN', false);
		}

		if (!\defined('FE_USER_LOGGED_IN'))
		{
			\define('FE_USER_LOGGED_IN', false);
		}

		$this->intBID    = 0;
		$this->intDEFBID = 0;
	}

	/**
	 * Run the controller
	 *
	 * @return Response
	 */
	public function run($strbid, $bid)
	{
		if ($strbid == 'bid')
		{
			$this->intBID = $bid;
		}
		else
		{
			$this->intDEFBID = $bid;
		}

		$banner_category_id = $this->getBannerCategory($this->intBID);
		$objBannerHelper    = new BannerHelper();
		$objBannerHelper->setDebugSettings($banner_category_id);

		// Banner oder Kategorie Banner (Default Banner)
		if (0 < $this->intBID)
		{
			// normaler Banner
			$banner_not_viewed = false;
			// Check whether the Banner ID exists
			$objBanners = Database::getInstance()
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
				$objBanners = Database::getInstance()
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
					$objResponse = new Response('Banner ID not found', 501);

					return $objResponse;
				}
				$banner_not_viewed = true;
			}
			$BannerChecks = new BannerChecks();
			$banner_stat_update = false;
			if (
				$BannerChecks->checkUserAgent() === false
				&& $BannerChecks->checkBot()       === false
				&& $BannerChecks->checkBE()        === false
				&& $this->getSetReClickBlocker()   === false
			) {
				// keine User Agent Filterung, kein Bot, kein ReClick, kein BE Login
				$banner_stat_update = true;
			}
			$BannerChecks = null;
			unset($BannerChecks);

			// Zählung
			$this->countClicks($banner_stat_update, $banner_not_viewed, $objBanners);

			// Banner Ziel per Page?
			if ($objBanners->banner_jumpTo >0)
			{
				// url generieren
				$objBannerNextPage = Database::getInstance()
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
					$objPage = PageModel::findWithDetails($objBanners->banner_jumpTo);
					// deprecated #8 $objBanners->banner_url = \Controller::generateFrontendUrl($objBannerNextPage->fetchAssoc(), null, $objPage->rootLanguage);
					$objBanners->banner_url = BannerHelper::frontendUrlGenerator($objBannerNextPage->fetchAssoc(), null, $objPage->rootLanguage);
				}
			}
			$banner_redirect = $this->getRedirectType($this->intBID);
		}
		else
		{
			// Default Banner from Category
			// Check whether the Banner ID exists
			$objBanners = Database::getInstance()
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
				$objResponse = new Response('Default Banner ID not found', 501);

				return $objResponse;
			}
			$banner_redirect = '303';
		}
		$banner_url = $objBanners->banner_url;
		BannerLog::writeLog(__METHOD__, __LINE__, 'banner_url: ' . $banner_url);
		// 301 Moved Permanently
		// 302 Found
		// 303 See Other
		// 307 Temporary Redirect (ab Contao 3.1)
		// $this->redirect($banner_url, $banner_redirect);

		// Make the location an absolute URL
		if (!preg_match('@^https?://@i', $banner_url))
		{
			$banner_url = Environment::get('base') . ltrim($banner_url, '/');
		}

		$objResponse = new RedirectResponse($banner_url, $banner_redirect);
		$objResponse->setPrivate();
		$objResponse->setMaxAge(0);
		$objResponse->setSharedMaxAge(0);
		$objResponse->headers->addCacheControlDirective('must-revalidate', true);
		$objResponse->headers->addCacheControlDirective('no-store', true);

		return $objResponse;
	}

	protected function countClicks($banner_stat_update, $banner_not_viewed, $objBanners)
	{
		if ($banner_stat_update === true)
		{
			if ($banner_not_viewed === false)
			{
				// Update
				$tstamp = time();
				$banner_clicks = $objBanners->banner_clicks + 1;
				Database::getInstance()->prepare("UPDATE
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
				// Insert
				$arrSet =
				array(
					'id'     => $this->intBID,
					'tstamp' => time(),
					'banner_clicks' => 1
				);
				Database::getInstance()->prepare("INSERT IGNORE INTO tl_banner_stat %s")
										->set($arrSet)
										->execute();
			}
		}
	}

	/**
	 * Search Banner Redirect Definition
	 *
	 * @return int 301,302
	 */
	protected function getRedirectType($banner_id)
	{
		// aus $banner_id die CatID
		// über CatID in tl_module.banner_categories die tl_module.banner_redirect
		// Schleife über alle zeilen, falls mehrere
		$CatID = $this->getBannerCategory($banner_id);
		if (0 == $CatID)
		{
			return '301'; // error, but the show must go on
		}

		$objBRT = Database::getInstance()->prepare("SELECT
                                                        `banner_categories`
                                                       ,`banner_redirect`
                                                     FROM
                                                        `tl_module`
                                                     WHERE
                                                        type=?
                                                     AND
                                                        banner_categories=?")
										->execute('banner', $CatID);
		if (0 == $objBRT->numRows)
		{
			return '301'; // error, but the show must go on
		}
		$arrBRT = array();

		while ($objBRT->next())
		{
			$arrBRT[] = ($objBRT->banner_redirect == 'temporary') ? '302' : '301';
		}
		if (\count($arrBRT) == 1)
		{
			return $arrBRT[0];	// Nur ein Modul importiert, eindeutig
		}
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
		if ($anz301 >= $anz302)		// 301 hat bei Gleichstand Vorrang
		{
			return '301';
		}

		return '302';
	}

	/**
	 * ReClick Blocker
	 *
	 * @return bool false/true  =   no ban / ban
	 */
	protected function getSetReClickBlocker()
	{
		// $ClientIP = bin2hex(sha1(\Environment::get('remoteAddr'),true)); // sha1 20 Zeichen, bin2hex 40 zeichen
		$BannerID = $this->intBID;
		if ($this->getReClickBlockerId($BannerID) === false)
		{
			// nichts geblockt, also blocken fürs den nächsten Aufruf
			$this->setReClickBlockerId($BannerID);

			// kein ReClickBlocker block gefunden, Zaehlung erlaubt, nicht blocken
			BannerLog::writeLog(__METHOD__, __LINE__, ': False: Banner ID ' . $BannerID . ' Klick nicht geblockt');

			return false;
		}
		// Eintrag innerhalb der Blockzeit, blocken
		BannerLog::writeLog(__METHOD__, __LINE__, ': True: Banner ID ' . $BannerID . ' Klick geblockt');

		return true;
	}

	protected function getBannerCategory($banner_id)
	{
		$objCat = Database::getInstance()->prepare("SELECT
                                                        pid as CatID
                                                     FROM
                                                        `tl_banner`
                                                     WHERE
                                                        id=?")
										->execute($banner_id);
		if (0 == $objCat->numRows)
		{
			return 0; // error, but the show must go on
		}
		$objCat->next();

		return $objCat->CatID;
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
	 * @param integer $banner_id
	 */
	protected function setReClickBlockerId($banner_id=0)
	{
		if ($banner_id==0)
		{
			return;
		}// keine Banner ID, nichts zu tun
		// das können mehrere sein!, mergen!
		$this->setSession('ReClickBlocker', array($banner_id => time()), true);
	}

	/**
	 * Get ReClick Blocker, Get Banner ID if the timestamp ....
	 *
	 * @param boolean $banner_id true if blocked | false
	 */
	protected function getReClickBlockerId($banner_id=0)
	{
		$this->getSession('ReClickBlocker');
		if (\count($this->_session))
		{
			reset($this->_session);

			foreach ($this->_session as $key => $val)
			{
				if (
					$key == $banner_id
					&& $this->removeReClickBlockerId($key, $val) === true
				) {
					// Key ist noch gültig und es muss daher geblockt werden
					// fuer debug log_message('getReClickBlockerId Banner ID:'.$key,'Banner.log');
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * ReClick Blocker, Remove old Banner ID
	 *
	 * @param  integer $banner_id
	 * @return boolean true = Key is valid, it must be blocked | false = key is invalid
	 */
	protected function removeReClickBlockerId($banner_id, $tstmap)
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
			BannerLog::writeLog(__METHOD__, __LINE__, ': BannerBlockTime: ' . date("Y-m-d H:i:s", $BannerBlockTime));
			BannerLog::writeLog(__METHOD__, __LINE__, ': BannerSessiTime: ' . date("Y-m-d H:i:s", $tstmap));

			return true;
		}
		// wenn mehrere dann nur den Teil, nicht die ganze Session
		unset($this->_session[$banner_id]);
		// wenn Anzahl Banner in Session nun 0 dann Session loeschen
		if (\count($this->_session) == 0)
		{
			// komplett löschen
			$objBannerLogic = new BannerLogic();
			$objBannerLogic->removeSessionKey('ReClickBlocker');
			$objBannerLogic = null;
			unset($objBannerLogic);
		// \Session::getInstance()->remove('ReClickBlocker');
		}
		else // sonst neu setzen
		{// gekuerzte Session neu setzen
			$this->setSession('ReClickBlocker', $this->_session, false);
		}

		return false;
	}

	/**
	 * Get session
	 *
	 * @param string $session_name e.g.: 'ReClickBlocker'
	 */
	protected function getSession($session_name)
	{
		$objBannerLogic = new BannerLogic();
		$this->_session = (array) $objBannerLogic->getSession($session_name);
		$objBannerLogic = null;
		unset($objBannerLogic);
	}

	/**
	 * Set session
	 *
	 * @param string $session_name e.g.: 'ReClickBlocker'
	 * @param array  $arrData      array('key' => array(Value1,Value2,...))
	 */
	protected function setSession($session_name, $arrData, $merge = false)
	{
		$objBannerLogic = new BannerLogic();
		$objBannerLogic->setSession($session_name, $arrData, $merge);
		// if ($merge) {
		//     $this->_session = $this->getSession($session_name);

		//     // numerische Schlüssel werden neu numeriert, daher
		//     // geht nicht: array_merge($this->_session, $arrData)
		//     $merge_array = (array) $this->_session + $arrData;
		//     \Session::getInstance()->set($session_name, $merge_array);
		// } else {
		//     \Session::getInstance()->set($session_name, $arrData);
		// }
		$objBannerLogic = null;
		unset($objBannerLogic);
	}
}
