<?php
/**
 * Extension for Contao Open Source CMS, Copyright (C) 2005-2017 Leo Feyer
 *
 * Class BannerLog - Frontend
 *
 * @copyright  Glen Langer 2017 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @licence    LGPL
 * @filesource
 * @package    Banner
 * @see	       https://github.com/BugBuster1701/contao-banner-bundle
 */

namespace BugBuster\Banner;

use Psr\Log\LogLevel;
use Contao\CoreBundle\Monolog\ContaoContext;

/**
 * Class BannerLog
 *
 * @copyright  Glen Langer 2017 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Banner
 * @license    LGPL
 */
class BannerLog
{
    /**
     * Write in log file, if debug is enabled
     *
     * @param string    $method
     * @param integer   $line
     */
    public static function writeLog($method,$line,$value)
    {
        if ($method == '## START ##') 
        {
            if (!isset($GLOBALS['banner']['debug']['first'])) 
            {
                if ((bool)$GLOBALS['banner']['debug']['tag']         ||
                    (bool)$GLOBALS['banner']['debug']['helper']      ||
                    (bool)$GLOBALS['banner']['debug']['image']       ||
                    (bool)$GLOBALS['banner']['debug']['referrer']     
                   )
                {
                    $arrUniqid = trimsplit('.', uniqid('c0n7a0',true) );
                    $GLOBALS['banner']['debug']['first'] = $arrUniqid[1];
                    self::logMessage(sprintf('[%s] [%s] [%s] %s',$GLOBALS['banner']['debug']['first'],$method,$line,$value),'banner_debug');
                    return ;
                }
                return ;
            }
            else
            {
                return ;
            }
        }
                
        $arrNamespace = trimsplit('::', $method);
        $arrClass =  trimsplit('\\', $arrNamespace[0]);
        $vclass = $arrClass[2]; // class that will write the log
        
        if (is_array($value))
        {
            $value = print_r($value,true);
        }
        
        switch ($vclass)
        {
            case "BannerTag":
                if ($GLOBALS['banner']['debug']['tag'])
                {
                    self::logMessage(sprintf('[%s] [%s] [%s] %s',$GLOBALS['banner']['debug']['first'],$vclass.'::'.$arrNamespace[1],$line,$value),'banner_debug');
                }
                break;
            case "BannerHelper":
                if ($GLOBALS['banner']['debug']['helper'])
                {
                    self::logMessage(sprintf('[%s] [%s] [%s] %s',$GLOBALS['banner']['debug']['first'],$vclass.'::'.$arrNamespace[1],$line,$value),'banner_debug');
                }
                break;
            case "BannerImage":
                if ($GLOBALS['banner']['debug']['image'])
                {
                    self::logMessage(sprintf('[%s] [%s] [%s] %s',$GLOBALS['banner']['debug']['first'],$vclass.'::'.$arrNamespace[1],$line,$value),'banner_debug');
                }
                break;
            case "BannerReferrer":
                if ($GLOBALS['banner']['debug']['referrer'])
                {
                    self::logMessage(sprintf('[%s] [%s] [%s] %s',$GLOBALS['banner']['debug']['first'],$vclass.'::'.$arrNamespace[1],$line,$value),'banner_debug');
                }
                break;
            default:
                self::logMessage(sprintf('[%s] [%s] [%s] %s',$GLOBALS['banner']['debug']['first'],$method,$line,'('.$vclass.')'.$value),'banner_debug');
                break;
        }
        return ;
    }
    
    /**
     * Wrapper for old log_message
     *
     * @param string $strMessage
     * @param string $strLogg
     */
    public static function logMessage($strMessage, $strLog=null)
    {
        if ($strLog === null)
        {
            $strLog = 'prod-' . date('Y-m-d') . '.log';
        }
        else
        {
            $strLog = 'prod-' . date('Y-m-d') . '-' . $strLog . '.log';
        }
    
        $strLogsDir = null;
    
        if (($container = \System::getContainer()) !== null)
        {
            $strLogsDir = $container->getParameter('kernel.logs_dir');
        }
    
        if (!$strLogsDir)
        {
            $strLogsDir = TL_ROOT . '/var/logs';
        }
    
        error_log(sprintf("[%s] %s\n", date('d-M-Y H:i:s'), $strMessage), 3, $strLogsDir . '/' . $strLog);
    }
    
    /**
     * Add a log entry to the database
     *
     * @param string $strText     The log message
     * @param string $strFunction The function name
     * @param string $strCategory The category name
     *
     */
    public static function log($strText, $strFunction, $strCategory)
    {
        $level = TL_ERROR === $strCategory ? LogLevel::ERROR : LogLevel::INFO;
        $logger = static::getContainer()->get('monolog.logger.contao');
    
        $logger->log($level, $strText, array('contao' => new ContaoContext($strFunction, $strCategory)));
    }
    
}
