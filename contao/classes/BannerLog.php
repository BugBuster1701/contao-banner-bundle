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

namespace BugBuster\Banner;

use Contao\StringUtil;
use Contao\System;

/**
 * Class BannerLog
 */
class BannerLog
{
	/**
	 * Write in log file, if debug is enabled
	 *
	 * @param string       $method
	 * @param integer      $line
	 * @param string       $text
	 * @param array|string $value
	 */
	public static function writeLog($method, $line, $text, $value = '')
	{
		if (\is_array($value))
		{
			$value = '.' . json_encode($value, JSON_FORCE_OBJECT); // . Prefix, sonst Anzeige Bold im CM
		}
		if ($method == '## START ##')
		{
			if (!isset($GLOBALS['banner']['debug']['first']))
			{
				if ((bool) ($GLOBALS['banner']['debug']['all'] ?? false))
				{
					$arrUniqid = StringUtil::trimsplit('.', uniqid('c0n7a0', true));
					$GLOBALS['banner']['debug']['first'] = $arrUniqid[1];
					self::logMonolog($GLOBALS['banner']['debug']['first'], false, '', $method . ' ' . $line . ' ' . $text . $value);

					return;
				}

				return; // kein (first) log
			}

			return; // kein first log
		}
		if (false === (bool) ($GLOBALS['banner']['debug']['all'] ?? false))
		{
			// self::logMonolog($GLOBALS['banner']['debug']['first'], false, '', $method.' '.$line.' KEIN LOG AKTIVIERT');
			return; // kein Log aktiviert
		}

		$arrNamespace = StringUtil::trimsplit('::', $method);
		$arrClass =  StringUtil::trimsplit('\\', $arrNamespace[0]);
		// $vclass = $arrClass[2]; // class that will write the log
		$vclass = $arrClass[\count($arrClass)-1]; // class that will write the log

		self::logMonolog($GLOBALS['banner']['debug']['first'], $vclass . '::' . $arrNamespace[1], $line, $text . $value);
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
	 * Wrapper for Monolog
	 *
	 * @param string $uuid
	 * @param string $class
	 */
	public static function logMonolog($uuid, $class, $line, $message)
	{
		$strMessage = sprintf("%s %s\n", $uuid, $message);
		$userActionsLogger = System::getContainer()->get('bug_buster_banner.logger');
		$userActionsLogger->logMonologLog($strMessage, $class, (int) $line, 'debug');
	}

	/**
	 * Add a log entry to the database via Monolog
	 *
	 * @param string $strText     The log message
	 * @param string $strFunction The function name
	 * @param string $strCategory The category name
	 */
	public static function log($strText, $strFunction, $strCategory)
	{
		$userActionsLogger = System::getContainer()->get('bug_buster_banner.logger');
		$userActionsLogger->logSystemLog($strText, $strFunction, $strCategory);
	}
}
