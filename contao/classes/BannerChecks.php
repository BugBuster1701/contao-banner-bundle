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

use BugBuster\BotDetection\ModuleBotDetection;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Database;
use Contao\Environment;
use Contao\Frontend;
use Contao\System;
use Psr\Log\LogLevel;

/**
 * Class BannerChecks
 */
class BannerChecks extends Frontend
{
	/**
	 * Spider Bot Check
	 * @return true = found, false = not found
	 */
	public function checkBot()
	{
		if (
			isset($GLOBALS['TL_CONFIG']['mod_banner_bot_check'])
			&& (int) $GLOBALS['TL_CONFIG']['mod_banner_bot_check'] == 0
		) {
			BannerLog::writeLog(__METHOD__, __LINE__, 'Bot Check: False: Bot search disabled via localconfig.php');

			return false; // Bot Suche abgeschaltet ueber localconfig.php
		}

		$bundles = array_keys(System::getContainer()->getParameter('kernel.bundles')); // old \ModuleLoader::getActive()

		if (!\in_array('BugBusterBotdetectionBundle', $bundles))
		{
			// BugBusterBotdetectionBundle Modul fehlt, Abbruch
			System::getContainer()
						->get('monolog.logger.contao')
						->log(
							LogLevel::ERROR,
							'contao-botdetection-bundle extension required for extension: Banner!',
							array('contao' => new ContaoContext('BannerChecks checkBot ', ContaoContext::ERROR))
						);
			BannerLog::writeLog(__METHOD__, __LINE__, 'Botdetection missing, installed bundles: ', $bundles);

			return false;
		}
		$ModuleBotDetection = new ModuleBotDetection();
		if ($ModuleBotDetection->checkBotAllTests())
		{
			BannerLog::writeLog(__METHOD__, __LINE__, 'checkBotAllTests: True');

			return true;
		}
		BannerLog::writeLog(__METHOD__, __LINE__, 'checkBotAllTests: False');

		return false;
	} // checkBot

	/**
	 * HTTP_USER_AGENT Special Check
	 */
	public function checkUserAgent()
	{
		if (Environment::get('httpUserAgent'))
		{
			$UserAgent = trim(Environment::get('httpUserAgent'));
		}
		else
		{
			return false; // Ohne Absender keine Suche
		}

		$objUserAgent = Database::getInstance()->prepare("SELECT
                                                                `banner_useragent`
                                                           FROM
                                                                `tl_module`
                                                           WHERE
                                                                `banner_useragent` !=?")
												->limit(1)
												->execute('');
		if (!$objUserAgent->next())
		{
			BannerLog::writeLog(__METHOD__, __LINE__, 'Check User Agent: False: No information in the module!');

			return false; // keine Angaben im Modul
		}
		$arrUserAgents = explode(",", $objUserAgent->banner_useragent);
		if (\strlen(trim($arrUserAgents[0])) == 0)
		{
			return false; // keine Angaben im Modul
		}
		array_walk($arrUserAgents, array('self', 'bannerclickTrimArrayValue'));  // trim der array values
		// grobe Suche
		$CheckUserAgent=str_replace($arrUserAgents, '#', $UserAgent);
		if ($UserAgent != $CheckUserAgent)   // es wurde ersetzt also was gefunden
		{
			BannerLog::writeLog(__METHOD__, __LINE__, 'Check User Agent: True: Match!');

			return true;
		}

		return false;
	} // checkUserAgent

	public static function bannerclickTrimArrayValue(&$data)
	{
		$data = trim($data);
	}

	/**
	 * BE Login Check
	 * basiert auf Frontend.getLoginStatus
	 *
	 * @return bool
	 */
	public function checkBE()
	{
		$objTokenChecker = System::getContainer()->get('contao.security.token_checker');
		if ($objTokenChecker->hasBackendUser())
		{
			BannerLog::writeLog(__METHOD__, __LINE__, 'Backend User: True');

			return true;
		}

		BannerLog::writeLog(__METHOD__, __LINE__, 'Backend User: False');

		return false;
	} // CheckBE
}
