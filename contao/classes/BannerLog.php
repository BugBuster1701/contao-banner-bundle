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

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\StringUtil;
use Contao\System;
use Psr\Log\LogLevel;

/**
 * Class BannerLog
 */
class BannerLog
{
	/**
	 * Write in log file, if debug is enabled
	 *
	 * @param string  $method
	 * @param integer $line
	 */
	public static function writeLog($method, $line, $value)
	{
		if ($method == '## START ##')
		{
			if (!isset($GLOBALS['banner']['debug']['first']))
			{
				if ((bool) ($GLOBALS['banner']['debug']['all'] ?? false))
				{
					$arrUniqid = StringUtil::trimsplit('.', uniqid('c0n7a0', true));
					$GLOBALS['banner']['debug']['first'] = $arrUniqid[1];
					self::logMessage(sprintf('[%s] [%s] [%s] %s', $GLOBALS['banner']['debug']['first'], $method, $line, $value), 'banner_debug');

					return;
				}

				return; // kein (first) log
			}

			return; // kein first log
		}
		if (false === (bool) ($GLOBALS['banner']['debug']['all'] ?? false))
		{
			return; // kein Log aktiviert
		}

		$arrNamespace = StringUtil::trimsplit('::', $method);
		$arrClass =  StringUtil::trimsplit('\\', $arrNamespace[0]);
		$vclass = $arrClass[2]; // class that will write the log

		if (\is_array($value))
		{
			$value = print_r($value, true);
		}

		self::logMessage(sprintf('[%s] [%s] [%s] %s', $GLOBALS['banner']['debug']['first'], $vclass . '::' . $arrNamespace[1], $line, $value), 'banner_debug');
	}

	/**
	 * Wrapper for old log_message
	 *
	 * @param string $strMessage
	 * @param string $strLog
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

		if (($container = System::getContainer()) !== null)
		{
			$strLogsDir = $container->getParameter('kernel.logs_dir');
		}

		if (!$strLogsDir)
		{
			$rootDir = System::getContainer()->getParameter('kernel.project_dir');
			$strLogsDir = $rootDir . '/var/logs';
		}

		error_log(sprintf("[%s] %s\n", date('d-M-Y H:i:s'), $strMessage), 3, $strLogsDir . '/' . $strLog);
	}

	/**
	 * Add a log entry to the database
	 *
	 * @param string $strText     The log message
	 * @param string $strFunction The function name
	 * @param string $strCategory The category name
	 */
	public static function log($strText, $strFunction, $strCategory)
	{
		$level = ContaoContext::ERROR === $strCategory ? LogLevel::ERROR : LogLevel::INFO;
		$logger = System::getContainer()->get('monolog.logger.contao');

		$logger->log($level, $strText, array('contao' => new ContaoContext($strFunction, $strCategory)));
	}
}
