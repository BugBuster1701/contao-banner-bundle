<?php

/*
 * This file is part of a BugBuster Contao Bundle.
 *
 * @copyright  Glen Langer 2025 <http://contao.ninja>
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

/**
 * Class BannerReferrer
 */
class BannerReferrer
{
	/**
	 * Current version of the class.
	 */
	public const BANNER_REFERRER_VERSION = '3.0.0';

	private $_http_referrer = '';

	private $_referrer_DNS  = '';

	private $_vhost         = '';

	public const REFERRER_UNKNOWN  = '-';

	public const REFERRER_OWN      = 'o';

	public const REFERRER_WRONG    = 'w';

	/**
	 * Returns the version number
	 *
	 * @return string
	 */
	public function getVersion()
	{
		return self::BANNER_REFERRER_VERSION;
	}

	public function checkReferrer()
	{
		$this->reset();
		if (
			$this->_http_referrer !== self::REFERRER_UNKNOWN
			&& $this->_referrer_DNS  !== self::REFERRER_WRONG
		) {
			$this->detect();
		}
		BannerLog::writeLog(__METHOD__, __LINE__, 'Referrer_DNS: ', $this->_referrer_DNS);
	}

	/**
	 * Reset all properties
	 */
	protected function reset()
	{
		// NEVER TRUST USER INPUT
		if (\function_exists('filter_var'))	// Adjustment for hoster without the filter extension
		{
			$this->_http_referrer  = isset($_SERVER['HTTP_REFERER']) ? filter_var($_SERVER['HTTP_REFERER'], FILTER_SANITIZE_URL) : self::REFERRER_UNKNOWN;
		}
		else
		{
			$this->_http_referrer  = $_SERVER['HTTP_REFERER'] ?? self::REFERRER_UNKNOWN;
		}
		$this->_referrer_DNS = self::REFERRER_UNKNOWN;
		if (
			$this->_http_referrer == ''
			|| $this->_http_referrer == '-'
		) {
			// ungueltiger Referrer
			$this->_referrer_DNS = self::REFERRER_WRONG;
		}
	}

	protected function detect()
	{
		try
		{
			$this->_referrer_DNS = parse_url($this->_http_referrer, PHP_URL_HOST);
		}
		catch (\Exception $e)
		{
			$this->_referrer_DNS == null;
		}
		if ($this->_referrer_DNS === null)
		{
			// try this...
			try
			{
				$this->_referrer_DNS = parse_url('http://' . $this->_http_referrer, PHP_URL_HOST);
			}
			catch (\Exception $e)
			{
				$this->_referrer_DNS == null;
			}
			if (
				$this->_referrer_DNS === null
				|| $this->_referrer_DNS === false
			) {
				// wtf...
				$this->_referrer_DNS = self::REFERRER_WRONG;
			}
		}
		$this->_vhost = parse_url('http://' . $this->vhost(), PHP_URL_HOST);
		// ReferrerDNS = HostDomain ?
		if ($this->_referrer_DNS == $this->_vhost)
		{
			$this->_referrer_DNS = self::REFERRER_OWN;
		}
	}

	/**
	 * Return the current URL without path or query string or protocol
	 * @return string
	 */
	protected function vhost()
	{
		if (isset($_SERVER['HTTP_X_FORWARDED_HOST']))
		{
			return preg_replace('/[^A-Za-z0-9\[\]\.:-]/', '', rtrim($_SERVER['HTTP_X_FORWARDED_HOST'], '/'));
		}

		$host = rtrim($_SERVER['HTTP_HOST']);
		if (empty($host))
		{
			$host = rtrim($_SERVER['SERVER_NAME']);
		}
		$host  = preg_replace('/[^A-Za-z0-9\[\]\.:-]/', '', $host);

		return $host;
	}

	public function getReferrerDNS()
	{
		return $this->_referrer_DNS;
	}

	public function getReferrerFull()
	{
		return $this->_http_referrer;
	}

	public function getHost()
	{
		return $this->_vhost;
	}

	public function __toString()
	{
		return "Referrer DNS : {$this->getReferrerDNS()}\n<br>" .
			   "Referrer full: {$this->getReferrerFull()}\n<br>" .
			   "Server Host :  {$this->getHost()}\n<br>";
	}
}
