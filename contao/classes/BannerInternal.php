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

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\File;
use Contao\FilesModel;
use Contao\Frontend;
use Contao\ImageSizeModel;
use Contao\StringUtil;
use Contao\System;

/**
 * Class BannerInternal
 */
class BannerInternal
{
	/**
	 * Banner intern
	 */
	public const BANNER_TYPE_INTERN = 'banner_image';

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
		// Pfad+Dateiname holen ueber UUID (findByPk leitet um auf findByUuid)
		$objFile = FilesModel::findByPk($this->objBanners->banner_image);
		// BannerImage Class
		$this->BannerImage = new BannerImage();
		// Banner Art und Größe bestimmen
		$arrImageSize = $this->BannerImage->getBannerImageSize($objFile->path, self::BANNER_TYPE_INTERN);
		// Falls Datei gelöscht wurde, Abbruch
		if (false === $arrImageSize)
		{
			$arrImageSize[2] = 0;
			BannerLog::log('Banner Image with ID "' . $this->objBanners->id . '" not found', __METHOD__ . ':' . __LINE__, ContaoContext::ERROR);

			$objReturn = new \stdClass();
			$objReturn->FileSrc = null;
			$objReturn->Picture = null;
			$objReturn->ImageSize = $arrImageSize;

			return $objReturn;
		}
		// Banner Neue Größe 0:$Width 1:$Height 2:resize mode
		$arrNewSizeValues = StringUtil::deserialize($this->objBanners->banner_imgSize);
		$predefined = false;
		// Vordefinierte Größe?
		if (is_numeric($arrNewSizeValues[2]))
		{
			$predefined = true;
			$imageSize = ImageSizeModel::findByPk((int) $arrNewSizeValues[2]);
			BannerLog::writeLog(__METHOD__, __LINE__, 'Predefined dimensions: ', $imageSize);

			if ($imageSize === null)
			{
				$arrNewSizeValues[0] = 0;
				$arrNewSizeValues[1] = 0;
				$arrNewSizeValues[2] = 0;
			}
			else
			{
				$arrNewSizeValues[0] = ($imageSize->width > 0) ? $imageSize->width : 0;
				$arrNewSizeValues[1] = ($imageSize->height > 0) ? $imageSize->height : 0;
				$arrNewSizeValues[2] = $imageSize->resizeMode;
			}
		}
		BannerLog::writeLog(__METHOD__, __LINE__, 'NewSizeValues: ', $arrNewSizeValues);

		// Banner Neue Größe ermitteln, return array $Width,$Height,$oriSize
		$arrImageSizenNew = $this->BannerImage->getBannerImageSizeNew($arrImageSize[0], $arrImageSize[1], $arrNewSizeValues[0], $arrNewSizeValues[1]);

		// wenn oriSize = true und GIF original Pfad nehmen
		if (
			$arrImageSizenNew[2] === true // oriSize
			 && $arrImageSize[2] == 1  // GIF
		) {
			$FileSrc = $objFile->path;
			$arrImageSize[0] = $arrImageSizenNew[0];
			$arrImageSize[1] = $arrImageSizenNew[1];
			$arrImageSize[3] = ' height="' . $arrImageSizenNew[1] . '" width="' . $arrImageSizenNew[0] . '"';

			// fake the Picture::create
			$picture['img']   =
			array(
				'src'    => StringUtil::specialchars(StringUtil::ampersand($FileSrc)),
				'width'  => $arrImageSizenNew[0],
				'height' => $arrImageSizenNew[1],
				'srcset' => StringUtil::specialchars(StringUtil::ampersand($FileSrc))
			);
			$arrMeta = $this->getBannerMetaData($this->objBanners, $objFile);
			$picture['alt']   = $arrMeta['alt'];
			$picture['title'] = $arrMeta['title'];

			BannerLog::writeLog(__METHOD__, __LINE__, 'Orisize Picture: ', $picture);
		}
		else
		{
			// Resize an image and store the resized version in the assets/images folder
			// return The path of the resized image or null
			$container = System::getContainer();
			$rootDir   = $container->getParameter('kernel.project_dir');
			$staticUrl = $container->get('contao.assets.files_context')->getStaticUrl();
			$FileSrc = $container
						->get('contao.image.factory')
						->create($rootDir . '/' . $objFile->path, array($arrImageSizenNew[0], $arrImageSizenNew[1], $arrNewSizeValues[2]))
						->getUrl($rootDir);

			BannerLog::writeLog(__METHOD__, __LINE__, 'Resize Image: ', $FileSrc);

			$picture = $container->get('contao.image.picture_factory');
			if ($predefined)
			{
				$picture = $picture->create($rootDir . '/' . $objFile->path, $imageSize->id);
			}
			else
			{
				$picture = $picture->create($rootDir . '/' . $objFile->path, array($arrImageSizenNew[0], $arrImageSizenNew[1], $arrNewSizeValues[2]));
			}

			$picture =
			array(
				'img'     => $picture->getImg($rootDir, $staticUrl),
				'sources' => $picture->getSources($rootDir, $staticUrl)
			);

			$arrMeta = $this->getBannerMetaData($this->objBanners, $objFile);
			$picture['alt']   = $arrMeta['alt'];
			$picture['title'] = $arrMeta['title'];

			BannerLog::writeLog(__METHOD__, __LINE__, 'Resize Picture: ', $picture);

			$arrImageSize[0] = $arrImageSizenNew[0];
			$arrImageSize[1] = $arrImageSizenNew[1];
			$arrImageSize[3] = ' height="' . $arrImageSizenNew[1] . '" width="' . $arrImageSizenNew[0] . '"';
		}

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

	/**
	 * Get Banner Meta Data
	 *
	 * @param  object $objBanners
	 * @param  object $objFile
	 * @return array
	 */
	public function getBannerMetaData($objBanners, $objFile)
	{
		$arrMeta = array();
		if ($objBanners->banner_overwritemeta != '1')
		{
			$arrMeta['alt']   = StringUtil::specialchars(StringUtil::ampersand($objBanners->banner_name));
			$arrMeta['title'] = StringUtil::specialchars(StringUtil::ampersand($objBanners->banner_comment));

			return $arrMeta;
		}
		global $objPage;
		$objBannerFile =  new File($objFile->path);

		$arrMeta =  Frontend::getMetaData($objBannerFile->meta, $objPage->language);

		if (empty($arrMeta))
		{
			if ($objPage->rootFallbackLanguage !== null)
			{
				$arrMeta =  Frontend::getMetaData($objFile->meta, $objPage->rootFallbackLanguage);
				BannerLog::writeLog(__METHOD__, __LINE__, 'BannerMetaData rootFallback: ', $arrMeta);
			}
		}
		if (empty($arrMeta['alt']))
		{
			$arrMeta['alt']   = StringUtil::specialchars(StringUtil::ampersand($objBanners->banner_name));
		}
		if (empty($arrMeta['title']))
		{
			$arrMeta['title']   = StringUtil::specialchars(StringUtil::ampersand($objBanners->banner_comment));
		}

		return $arrMeta;
	}
}
