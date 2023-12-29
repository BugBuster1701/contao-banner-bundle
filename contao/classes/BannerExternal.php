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
use Contao\ImageSizeModel;
use Contao\StringUtil;

/**
 * Class BannerExternal
 */
class BannerExternal
{
	/**
	 * Banner extern
	 */
	public const BANNER_TYPE_EXTERN = 'banner_image_extern';

	protected $objBanners;

	protected $banner_cssID;

	protected $banner_class;

	protected $BannerImage;

	public function __construct($objBanners, $banner_cssID, $banner_class)
	{
		$this->objBanners   = $objBanners;
		$this->banner_cssID = $banner_cssID;
		$this->banner_class = $banner_class;
	}

	/**
	 * @return \stdClass
	 */
	public function generateImageData()
	{
		// BannerImage Class
		$this->BannerImage = new BannerImage();
		// Banner Art und Größe bestimmen
		$arrImageSize = $this->BannerImage->getBannerImageSize($this->objBanners->banner_image_extern, self::BANNER_TYPE_EXTERN);
		// Falls Datei gelöscht wurde, Abbruch
		if (false === $arrImageSize)
		{
			$arrImageSize[2] = 0;
			BannerLog::log('Banner Image with ID "' . $this->objBanners->id . '" not found', __METHOD__ . ':' . __LINE__, ContaoContext::ERROR);
			BannerLog::writeLog(__METHOD__, __LINE__, 'Banner Image with ID "' . $this->objBanners->id . '" not found');

			$objReturn = new \stdClass();
			$objReturn->FileSrc = null;
			$objReturn->Picture = null;
			$objReturn->ImageSize = $arrImageSize;

			return $objReturn;
		}
		// Banner Neue Größe 0:$Width 1:$Height
		$arrNewSizeValues = StringUtil::deserialize($this->objBanners->banner_imgSize);

		// Vordefinierte Größe?
		if (is_numeric($arrNewSizeValues[2]))
		{
			/** @var ImageSizeModel $imagesize */
			$imageSize = ImageSizeModel::findByPk((int) $arrNewSizeValues[2]);
			BannerLog::writeLog(__METHOD__, __LINE__, 'Predefined dimensions: ' . print_r($imageSize, true));

			if ($imageSize === null)
			{
				$arrNewSizeValues[0] = 0;
				$arrNewSizeValues[1] = 0;
				$arrNewSizeValues[2] = 0;
			}
			else
			{
				$arrNewSizeValues[0] = ($imageSize->width  > 0) ? $imageSize->width : 0;
				$arrNewSizeValues[1] = ($imageSize->height > 0) ? $imageSize->height : 0;
				$arrNewSizeValues[2] = $imageSize->resizeMode;
			}
		}

		// Banner Neue Größe ermitteln, return array $Width,$Height,$oriSize
		$arrImageSizenNew = $this->BannerImage->getBannerImageSizeNew($arrImageSize[0], $arrImageSize[1], $arrNewSizeValues[0], $arrNewSizeValues[1]);
		// Umwandlung bei Parametern
		$FileSrc = html_entity_decode($this->objBanners->banner_image_extern, ENT_NOQUOTES, 'UTF-8');

		// fake the Picture::create
		$picture['img']   =
		array(
			'src'    => StringUtil::specialchars(StringUtil::ampersand($FileSrc)),
			'width'  => $arrImageSizenNew[0],
			'height' => $arrImageSizenNew[1],
			'srcset' => StringUtil::specialchars(StringUtil::ampersand($FileSrc))
		);
		$picture['alt']   = StringUtil::specialchars(StringUtil::ampersand($this->objBanners->banner_name));
		$picture['title'] = StringUtil::specialchars(StringUtil::ampersand($this->objBanners->banner_comment));

		$arrImageSize[0] = $arrImageSizenNew[0];
		$arrImageSize[1] = $arrImageSizenNew[1];
		$arrImageSize[3] = ' height="' . $arrImageSizenNew[1] . '" width="' . $arrImageSizenNew[0] . '"';

		$objReturn = new \stdClass();
		$objReturn->FileSrc = $FileSrc;
		$objReturn->Picture = $picture;
		$objReturn->ImageSize = $arrImageSize;

		return $objReturn;
	}

	/**
	 * Generate Template Data
	 *
	 * @param  array  $arrImageSize
	 * @param  string $FileSrc
	 * @param  array  $picture
	 * @return array
	 */
	public function generateTemplateData($arrImageSize, $FileSrc, $picture)
	{
		return BannerTemplate::generateTemplateData($arrImageSize, $FileSrc, $picture, $this->objBanners, $this->banner_cssID, $this->banner_class);
	}
}
