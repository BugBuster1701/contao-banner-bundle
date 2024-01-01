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

use Contao\File;
use Contao\System;
use Symfony\Component\HttpClient\HttpClient;

/**
 * Class BannerImage
 */
class BannerImage extends System
{
	/**
	 * Current version of the class.
	 */
	public const BANNER_IMAGE_VERSION = '4.0.0';

	/**
	 * Banner intern
	 */
	public const BANNER_TYPE_INTERN = 'banner_image';

	/**
	 * Banner extern
	 */
	public const BANNER_TYPE_EXTERN = 'banner_image_extern';

	/**
	 * Banner text
	 */
	public const BANNER_TYPE_TEXT   = 'banner_text';

	/**
	 * public constructor for phpunit
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Returns the version number
	 *
	 * @return string
	 */
	public function getVersion()
	{
		return self::BANNER_IMAGE_VERSION;
	}

	/**
	 * Get the size of an image
	 *
	 * @param  string $BannerImage Image path/link
	 * @param  string $BannerType  intern,extern,text
	 * @return mixed  / false
	 */
	public function getBannerImageSize($BannerImage, $BannerType)
	{
		switch ($BannerType)
		{
			case self::BANNER_TYPE_INTERN:
				return $this->getImageSizeInternal($BannerImage);
				break;
			case self::BANNER_TYPE_EXTERN:
				return $this->getImageSizeExternal($BannerImage);
				break;
			case self::BANNER_TYPE_TEXT:
				return false;
				break;
			default:
				return false;
				break;
		}
	}

	/**
	 * Get the size of an internal image
	 *
	 * @param  string $BannerImage Image path
	 * @return mixed  / false
	 */
	protected function getImageSizeInternal($BannerImage)
	{
		$arrImageSize = false;

		try
		{
			$rootDir = System::getContainer()->getParameter('kernel.project_dir');
			$arrImageSize = getimagesize($rootDir . '/' . $BannerImage);
		}
		catch (\Exception $e)
		{
			$arrImageSize = false;
		}

		BannerLog::writeLog(__METHOD__, __LINE__, 'Image Size: ', $arrImageSize);

		return $arrImageSize;
	}

	/**
	 * Get the size of an external image
	 *
	 * @param  string $BannerImage Image link
	 * @return mixed  / false
	 */
	protected function getImageSizeExternal($BannerImage)
	{
		$token = md5(uniqid(random_int(0, getrandmax()), true));
		$tmpImage = 'system/tmp/mod_banner_fe_' . $token . '.tmp';

		$client = HttpClient::create(array(
			'max_redirects' => 5,
		));
		$response = $client->request('GET', html_entity_decode($BannerImage, ENT_NOQUOTES, 'UTF-8'));
		$statusCode = $response->getStatusCode();
		if (200 !== $statusCode)
		{
			return false;
		}

		// old: Test auf chunked, nicht noetig solange Contao bei HTTP/1.0 bleibt
		try
		{
			$objFile = new File($tmpImage);

			// $objFile->write($objRequest->response);
			$objFile->write($response->getContent());
			$objFile->close();
		}
		// Temp directory not writeable
		catch (\Exception $e)
		{
			if ($e->getCode() == 0)
			{
				BannerLog::logMessage('[getImageSizeExternal] tmpFile Problem: notWriteable');
			}
			else
			{
				BannerLog::logMessage('[getImageSizeExternal] tmpFile Problem: error');
			}

			return false;
		}
		$client=null;
		unset($client);
		$arrImageSize = $this->getImageSizeInternal($tmpImage);

		$objFile->delete();
		$objFile = null;
		unset($objFile);

		BannerLog::writeLog(__METHOD__, __LINE__, 'Image Size: ', $arrImageSize);

		return $arrImageSize;
	}

	/**
	 * Calculate the new size for witdh and height
	 *
	 * @param  int   $oldWidth  ,mandatory
	 * @param  int   $oldHeight ,mandatory
	 * @param  int   $newWidth  ,optional
	 * @param  int   $newHeight ,optional
	 * @return array $Width,$Height,$oriSize
	 */
	public function getBannerImageSizeNew($oldWidth, $oldHeight, $newWidth=0, $newHeight=0)
	{
		$Width   = $oldWidth;  // Default, and flash require this
		$Height  = $oldHeight; // Default, and flash require this
		$oriSize = true;       // Attribute for images without conversion

		if ($oldWidth == $newWidth && $oldHeight == $newHeight)
		{
			return array($Width, $Height, $oriSize);
		}

		if ($newWidth > 0 && $newHeight > 0)
		{
			$Width   = $newWidth;
			$Height  = $newHeight;
			$oriSize = false;
		}
		elseif ($newWidth > 0)
		{
			$Width   = $newWidth;
			$Height  = ceil($newWidth * $oldHeight / $oldWidth);
			$oriSize = false;
		}
		elseif ($newHeight > 0)
		{
			$Width   = ceil($newHeight * $oldWidth / $oldHeight);
			$Height  = $newHeight;
			$oriSize = false;
		}

		return array($Width, $Height, $oriSize);
	}

	/**
	 * Calculate the new size if necessary by comparing with maxWidth and maxHeight
	 *
	 * @param  array $arrImageSize
	 * @param  int   $maxWidth
	 * @param  int   $maxHeight
	 * @return array $Width,$Height,$oriSize
	 */
	public function getCheckBannerImageSize($arrImageSize, $maxWidth, $maxHeight)
	{
		// $arrImageSize[0] Breite (max 250px in BE)
		// $arrImageSize[1] Hoehe  (max  40px in BE)
		// $arrImageSize[2] Type
		if ($arrImageSize[0] > $arrImageSize[1]) // Breite > Hoehe = Landscape ==
		{
			if ($arrImageSize[0] > $maxWidth)	// neue feste Breite
			{
				$newImageSize = $this->getBannerImageSizeNew($arrImageSize[0], $arrImageSize[1], $maxWidth, 0);
				$intWidth  = $newImageSize[0];
				$intHeight = $newImageSize[1];
				$oriSize   = $newImageSize[2];
			}
			else
			{
				$intWidth  = $arrImageSize[0];
				$intHeight = $arrImageSize[1];
				$oriSize   = true; // Merkmal fuer Bilder ohne Umrechnung
			}
		}
		else // Hoehe >= Breite, ggf. Hoehe verkleinern
		{
			if ($arrImageSize[1] > $maxWidth) // Hoehe > max Breite = Portrait ||
			{// pruefen ob bei neuer Hoehe die Breite zu klein wird
				if (($maxWidth*$arrImageSize[0]/$arrImageSize[1]) < $maxHeight)
				{
					// Breite statt Hoehe setzen, Breite auf maximale Hoehe
					$newImageSize = $this->getBannerImageSizeNew($arrImageSize[0], $arrImageSize[1], $maxHeight, 0);
					$intWidth  = $newImageSize[0];
					$intHeight = $newImageSize[1];
					$oriSize   = $newImageSize[2];
				}
				else
				{
					$newImageSize = $this->getBannerImageSizeNew($arrImageSize[0], $arrImageSize[1], 0, $maxHeight);
					$intWidth  = $newImageSize[0];
					$intHeight = $newImageSize[1];
					$oriSize   = $newImageSize[2];
				}
			}
			else
			{
				$intWidth  = $arrImageSize[0];
				$intHeight = $arrImageSize[1];
				$oriSize = true; // Merkmal fuer Bilder ohne Umrechnung
			}
		}

		return array($intWidth, $intHeight, $oriSize);
	}
}
