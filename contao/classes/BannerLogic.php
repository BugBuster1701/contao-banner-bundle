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

use Contao\System;

/**
 * Class BannerLogic
 *
 */
class BannerLogic
{
	private $_session = array();

	public $statusRandomBlocker = false;

	public $statusBannerFirstView;

	public $statusFirstViewBlocker;

	protected $container;

	protected $request;

	protected $BannerReferrer;

	public function __construct()
	{
		$this->container = System::getContainer();
		$this->request = $this->container->get('request_stack')->getCurrentRequest();
	}

	/**
	 * Get weighting for single banner
	 *
	 * @param [id $arrAllBannersBasic,weighting]
	 *
	 * @return integer 0|1|2|3    0 on error
	 */
	public function getSingleWeighting($arrAllBannersBasic)
	{
		$arrPrio = array();
		$arrPrioW = array();
		$arrWeights = array_flip($arrAllBannersBasic);

		// welche Wichtungen gibt es?
		if (\array_key_exists(1, $arrWeights))
		{
			$arrPrioW[1] = 1;
		}
		if (\array_key_exists(2, $arrWeights))
		{
			$arrPrioW[2] = 2;
		}
		if (\array_key_exists(3, $arrWeights))
		{
			$arrPrioW[3] = 3;
		}

		$arrPrio[0] = array('start'=>0,  'stop'=>0);
		$arrPrio[1] = array('start'=>1,  'stop'=>90);
		$arrPrio[2] = array('start'=>91, 'stop'=>150);
		$arrPrio[3] = array('start'=>151, 'stop'=>180);
		if (!\array_key_exists(2, $arrPrioW))
		{
			// no prio 2 banner
			$arrPrio[2] = array('start'=>0,  'stop'=>0);
			$arrPrio[3] = array('start'=>91, 'stop'=>120);
		}
		$intPrio1 = (\count($arrPrioW)) ? min($arrPrioW) : 0;
		$intPrio2 = (\count($arrPrioW)) ? max($arrPrioW) : 0;

		// wenn Wichtung vorhanden, dann per Zufall eine auswählen
		if ($intPrio1>0)
		{
			$intWeightingHigh = random_int($arrPrio[$intPrio1]['start'], $arrPrio[$intPrio2]['stop']);

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
	 * Get session
	 *
	 * @param  string $session_name e.g.: 'RandomBlocker'
	 * @return mixed
	 */
	public function getSession($session_name)
	{
		$this->_session = $this->request->getSession()->get($session_name);

		return $this->_session;
	}

	/**
	 * Set session
	 *
	 * @param string $session_name e.g.: 'RandomBlocker'
	 * @param array  $arrData      array('key' => array(Value1,Value2,...))
	 */
	public function setSession($session_name, $arrData, $merge = false)
	{
		if ($merge)
		{
			$this->_session = $this->request->getSession()->get($session_name);

			// numerische Schlüssel werden neu numeriert, daher
			// geht nicht: array_merge($this->_session, $arrData)
			$merge_array = (array) $this->_session + $arrData;
			$this->request->getSession()->set($session_name, $merge_array);
		}
		else
		{
			$this->request->getSession()->set($session_name, $arrData);
		}
	}

	/**
	 * Remove Session Key
	 *
	 * @param string $session_name
	 */
	public function removeSessionKey($session_name)
	{
		$this->request->getSession()->remove($session_name);
	}

	/**
	 * BannerLogic::setRandomBlockerId
	 *
	 * Random Blocker, Set Banner-ID
	 *
	 * @param integer $BannerID
	 * @param integer $module_id
	 */
	public function setRandomBlockerId($BannerID, $module_id)
	{
		if ($BannerID==0)
		{
			return;
		}// kein Banner, nichts zu tun

		$this->statusRandomBlocker = true;
		$this->setSession('RandomBlocker' . $module_id, array($BannerID => time()));
		BannerLog::writeLog(__METHOD__, __LINE__, 'setRandomBlockerId BannerID:' . $BannerID);
	}

	/**
	 * BannerLogic::getRandomBlockerId
	 *
	 * Random Blocker, Get Banner-ID
	 *
	 * @param  integer $module_id
	 * @return integer Banner-ID
	 */
	public function getRandomBlockerId($module_id)
	{
		$this->getSession('RandomBlocker' . $module_id);
		if (\count($this->_session ?? []))
		{
			$key   = key($this->_session);
			$value = current($this->_session);
			unset($value);
			reset($this->_session);
			BannerLog::writeLog(__METHOD__, __LINE__, 'getRandomBlockerId BannerID:' . $key);

			// DEBUG log_message('getRandomBlockerId BannerID:'.$key,'Banner.log');
			return $key;
		}

		return 0;
	}

	/**
	 * BannerLogic::setFirstViewBlockerId
	 *
	 * First View Blocker, Set Banner Categorie-ID and timestamp
	 *
	 * @param integer $banner_categorie
	 * @param integer $module_id
	 */
	public function setFirstViewBlockerId($banner_categorie, $module_id)
	{
		if ($banner_categorie==0)
		{
			return;
		}// keine Banner Kategorie, nichts zu tun

		$this->statusFirstViewBlocker = true;
		$this->setSession('FirstViewBlocker' . $module_id, array($banner_categorie => time()));
	}

	/**
	 * BannerLogic::getFirstViewBlockerId
	 *
	 * First View Blocker, Get Banner Categorie-ID if the timestamp ....
	 *
	 * @param integer $module_id
	 */
	public function getFirstViewBlockerId($module_id)
	{
		$this->getSession('FirstViewBlocker' . $module_id);
		if (\count($this->_session ?? []))
		{
			// each deprecated in PHP 7.2, daher
			$key    = key($this->_session);
			$tstmap = current($this->_session);
			reset($this->_session);
			if ($this->removeOldFirstViewBlockerId($key, $tstmap) === true)
			{
				// Key ist noch gültig und es muss daher geblockt werden
				// DEBUG log_message('getFirstViewBlockerId Banner Kat ID: '.$key,'Banner.log');
				return $key;
			}
		}

		return false;
	}

	/**
	 * BannerLogic::removeOldFirstViewBlockerId
	 *
	 * First View Blocker, Remove old Banner Categorie-ID
	 *
	 * @param  integer $key
	 * @return boolean true = Key is valid, it must be blocked | false = key is invalid
	 */
	public function removeOldFirstViewBlockerId($key, $tstmap)
	{
		// 5 Minuten Blockierung, älter >= 5 Minuten wird gelöscht
		$FirstViewBlockTime = time() - 60*5;

		if ($tstmap >  $FirstViewBlockTime)
		{
			return true;
		}
		$this->removeSessionKey($key);
		// $this->request->getSession()->remove($key);

		return false;
	}

	/**
	 * BannerLogic::getSetFirstView
	 *
	 * Get FirstViewBanner status and set cat id as blocker
	 *
	 * @param  char    $banner_firstview(1) DB Feld, '' / 1 checkbox Firstview Banner
	 * @return boolean true = if requested and not blocked | false = if requested but blocked
	 */
	public function getSetFirstView($banner_firstview, $banner_categories, $module_id)
	{
		// return true; // for Test only
		// FirstViewBanner gewünscht?
		if ($banner_firstview !=1)
		{
			return false;
		}

		$this->BannerReferrer = new BannerReferrer();
		$this->BannerReferrer->checkReferrer();
		$ReferrerDNS = $this->BannerReferrer->getReferrerDNS();
		// o own , w wrong
		if ($ReferrerDNS === 'o')
		{
			// eigener Referrer, Begrenzung auf First View nicht nötig.
			$this->statusBannerFirstView = false;

			return false;
		}

		if ($this->getFirstViewBlockerId($module_id) === false)
		{
			// nichts geblockt, also blocken fürs den nächsten Aufruf
			$this->setFirstViewBlockerId($banner_categories, $module_id);

			// kein firstview block gefunden, Anzeigen erlaubt
			$this->statusBannerFirstView = true;

			return true;
		}
		$this->statusBannerFirstView = false;

		return false;
	}
}
