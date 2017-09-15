<?php
/**
 * Contao Open Source CMS, Copyright (C) 2005-2017 Leo Feyer
 *
 * FE Helper Class BannerChecks
 *
 * @copyright  Glen Langer 2007 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Banner
 * @license    LGPL
 * @filesource
 * @see	       https://github.com/BugBuster1701/contao-banner-bundle
 */

namespace BugBuster\Banner;

use BugBuster\BotDetection\ModuleBotDetection;
use BugBuster\Banner\BannerLog;
use Psr\Log\LogLevel;
use Contao\CoreBundle\Monolog\ContaoContext;


class BannerChecks extends \Frontend
{
    /**
     * Spider Bot Check
     * @return true = found, false = not found
     */
    public function checkBot()
    {
        if (  isset($GLOBALS['TL_CONFIG']['mod_banner_bot_check'])
            && (int)$GLOBALS['TL_CONFIG']['mod_banner_bot_check'] == 0
            )
        {
            BannerLog::writeLog( __METHOD__ , __LINE__ , ': False: Bot Suche abgeschaltet ueber localconfig.php' );
            return false; //Bot Suche abgeschaltet ueber localconfig.php
        }
         
        $bundles = array_keys(\System::getContainer()->getParameter('kernel.bundles')); // old \ModuleLoader::getActive()
    
        if ( !in_array( 'BugBusterBotdetectionBundle', $bundles ) )
        {
            //BugBusterBotdetectionBundle Modul fehlt, Abbruch
            \System::getContainer()
                        ->get('monolog.logger.contao')
                        ->log(LogLevel::ERROR,
                            'contao-botdetection-bundle extension required for extension: Banner!',
                            array('contao' => new ContaoContext('BannerChecks checkBot ', TL_ERROR)));
            BannerLog::writeLog( __METHOD__ , __LINE__ , print_r($bundles, true) );
            return false;
        }
        $ModuleBotDetection = new ModuleBotDetection();
        if ($ModuleBotDetection->checkBotAllTests())
        {
            BannerLog::writeLog( __METHOD__ , __LINE__ , ': True' );
            return true;
        }
        BannerLog::writeLog( __METHOD__ , __LINE__ , ': False' );
        return false;
    } //checkBot
    
    /**
     * HTTP_USER_AGENT Special Check
     */
    public function checkUserAgent()
    {
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
            BannerLog::writeLog( __METHOD__ , __LINE__ , ': False: keine Angaben im Modul!' );
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
        {   //es wurde ersetzt also was gefunden
            BannerLog::writeLog( __METHOD__ , __LINE__ , ': True: Treffer!' );
            return true;
        }
        return false;
    } //checkUserAgent
    
    public static function bannerclickTrimArrayValue(&$data)
    {
        $data = trim($data);
        return ;
    }
       
    /**
     * BE Login Check
     * basiert auf Frontend.getLoginStatus
     *
     * @return bool
     */
    public function checkBE($strCookie = 'BE_USER_AUTH')
    {
        $cookie = \Input::cookie($strCookie);
        if ($cookie === null)
        {
            BannerLog::writeLog( __METHOD__ , __LINE__ , ': False1' );
            return false;
        }
    
        $hash = $this->getSessionHash($strCookie);
    
        // Validate the cookie hash
        if ($cookie == $hash)
        {
            // Try to find the session
            $objSession = \SessionModel::findByHashAndName($hash, $strCookie);
    
            // Validate the session ID and timeout
            if (   $objSession !== null
                && $objSession->sessionID == \System::getContainer()->get('session')->getId()
                && (\System::getContainer()->getParameter('contao.security.disable_ip_check') || $objSession->ip == \Environment::get('ip'))
                && ($objSession->tstamp + \Config::get('sessionTimeout')) > time()
                )
            {
                // The session could be verified
                BannerLog::writeLog( __METHOD__ , __LINE__ , ': True' );
                return true;
            }
        }
        BannerLog::writeLog( __METHOD__ , __LINE__ , ': False2' );
        return false;
    
    } //CheckBE
    
    
}
