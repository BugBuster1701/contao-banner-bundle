<?php
/**
 * Contao Open Source CMS, Copyright (C) 2005-2022 Leo Feyer
 *
 * FE Helper Class BannerChecks
 *
 * @copyright  Glen Langer 2007..2022 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @license    LGPL
 * @filesource
 * @see	       https://github.com/BugBuster1701/contao-banner-bundle
 */

namespace BugBuster\Banner;

use BugBuster\Banner\BannerLog;
use BugBuster\BotDetection\ModuleBotDetection;
use Contao\CoreBundle\Monolog\ContaoContext;
use Psr\Log\LogLevel;

class BannerChecks extends \Frontend
{
    /**
     * Spider Bot Check
     * @return true = found, false = not found
     */
    public function checkBot()
    {
        if (isset($GLOBALS['TL_CONFIG']['mod_banner_bot_check'])
            && (int) $GLOBALS['TL_CONFIG']['mod_banner_bot_check'] == 0
        ) {
            BannerLog::writeLog(__METHOD__, __LINE__, ': False: Bot Suche abgeschaltet ueber localconfig.php');

            return false; //Bot Suche abgeschaltet ueber localconfig.php
        }

        $bundles = array_keys(\System::getContainer()->getParameter('kernel.bundles')); // old \ModuleLoader::getActive()

        if (!\in_array('BugBusterBotdetectionBundle', $bundles)) {
            //BugBusterBotdetectionBundle Modul fehlt, Abbruch
            \System::getContainer()
                        ->get('monolog.logger.contao')
                        ->log(
                            LogLevel::ERROR,
                            'contao-botdetection-bundle extension required for extension: Banner!',
                            ['contao' => new ContaoContext('BannerChecks checkBot ', TL_ERROR)]
                        );
            BannerLog::writeLog(__METHOD__, __LINE__, print_r($bundles, true));

            return false;
        }
        $ModuleBotDetection = new ModuleBotDetection();
        if ($ModuleBotDetection->checkBotAllTests()) {
            BannerLog::writeLog(__METHOD__, __LINE__, ': True');

            return true;
        }
        BannerLog::writeLog(__METHOD__, __LINE__, ': False');

        return false;
    } //checkBot

    /**
     * HTTP_USER_AGENT Special Check
     */
    public function checkUserAgent()
    {
        if (\Environment::get('httpUserAgent')) {
            $UserAgent = trim(\Environment::get('httpUserAgent'));
        } else {
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
        if (!$objUserAgent->next()) {
            BannerLog::writeLog(__METHOD__, __LINE__, ': False: keine Angaben im Modul!');

            return false; // keine Angaben im Modul
        }
        $arrUserAgents = explode(",", $objUserAgent->banner_useragent);
        if (\strlen(trim($arrUserAgents[0])) == 0) {
            return false; // keine Angaben im Modul
        }
        array_walk($arrUserAgents, ['self', 'bannerclickTrimArrayValue']);  // trim der array values
        // grobe Suche
        $CheckUserAgent=str_replace($arrUserAgents, '#', $UserAgent);
        if ($UserAgent != $CheckUserAgent) {   //es wurde ersetzt also was gefunden
            BannerLog::writeLog(__METHOD__, __LINE__, ': True: Treffer!');

            return true;
        }

        return false;
    } //checkUserAgent

    public static function bannerclickTrimArrayValue(&$data)
    {
        $data = trim($data);

        return;
    }

    /**
     * BE Login Check
     * basiert auf Frontend.getLoginStatus
     *
     * @return bool
     */
    public function checkBE()
    {
        $objTokenChecker = \System::getContainer()->get('contao.security.token_checker');
        if ($objTokenChecker->hasBackendUser()) {
            BannerLog::writeLog(__METHOD__, __LINE__, ': True');

            return true;
        }

        BannerLog::writeLog(__METHOD__, __LINE__, ': False');

        return false;
    } //CheckBE
}
