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

use Contao\Database;
use Contao\Frontend;
use Contao\FrontendTemplate;
use Contao\StringUtil;

/**
 * Class BannerSingle
 */
class BannerSingle extends Frontend
{
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

	protected $arrCategoryValues = array();

	protected $BannerImage;

	protected $banner_template;

	protected $strTemplate;

	protected $Template;

	protected $arrAllBannersBasic;

	public function __construct($arrCategoryValues, $banner_template, $strTemplate, $objTemplate, $arrAllBannersBasic)
	{
		$this->arrCategoryValues  = $arrCategoryValues;
		$this->banner_template    = $banner_template;
		$this->strTemplate        = $strTemplate;
		$this->Template           = $objTemplate;
		$this->arrAllBannersBasic = $arrAllBannersBasic;
	}

	/**
	 * Get default banner or empty banner in $this->Template->banners
	 *
	 * @return $this->strTemplate
	 */
	public function getDefaultBanner($banner_hideempty, $module_id)
	{
		$arrImageSize = array();
		// CSS-ID/Klasse(n) je Banner, für den wrapper
		$banner_cssID   = '';
		$banner_class   = ' banner_default';

		// BannerDefault gewünscht und vorhanden?
		if (
			$this->arrCategoryValues['banner_default'] == '1'
			&& $this->arrCategoryValues['banner_default_image'] !== ''
		) {
			// Template setzen
			if (
				($this->banner_template != $this->strTemplate)
			  && ($this->banner_template != '')
			) {
				$this->strTemplate = $this->banner_template;
				$this->Template = new FrontendTemplate($this->strTemplate);
			}

			$banner_default_target = ($this->arrCategoryValues['banner_default_target'] == '1') ? '' : ' target="_blank"';

			// BannerImage Class
			$this->BannerImage = new BannerImage();

			// Banner Art bestimmen
			$arrImageSize = $this->BannerImage->getBannerImageSize($this->arrCategoryValues['banner_default_image'], self::BANNER_TYPE_INTERN);
			// 1 = GIF, 2 = JPG/JPEG, 3 = PNG
			// 4 = SWF, 13 = SWC (zip-like swf file)
			// 5 = PSD, 6 = BMP, 7 = TIFF(intel byte order), 8 = TIFF(motorola byte order)
			// 9 = JPC, 10 = JP2, 11 = JPX, 12 = JB2, 13 = SWC, 14 = IFF
			// 18 = WEBP, 19 0 AVIF

			// fake the Picture::create
			$picture['img']   =
			array(
				'src'    => $this->urlEncode($this->arrCategoryValues['banner_default_image']),
				'width'  => $arrImageSize[0],
				'height' => $arrImageSize[1],
				'srcset' => $this->urlEncode($this->arrCategoryValues['banner_default_image'])
			);
			$picture['alt']   = StringUtil::specialchars(StringUtil::ampersand($this->arrCategoryValues['banner_default_name']));
			$picture['title'] = '';

			BannerLog::writeLog(__METHOD__, __LINE__, 'Fake Picture: ', $picture);

			switch ($arrImageSize[2])
			{
				case 1:// GIF
				case 2:// JPG
				case 3:// PNG
				case 18: // WEBP
				case 19: // AVIF
					$arrBanners[] =
									array(
										'banner_key'     => 'defbid',
										'banner_wrap_id'    => $banner_cssID,
										'banner_wrap_class' => $banner_class,
										'banner_id'      => $this->arrCategoryValues['id'],
										'banner_name'    => StringUtil::specialchars(StringUtil::ampersand($this->arrCategoryValues['banner_default_name'])),
										'banner_url'     => $this->arrCategoryValues['banner_default_url'],
										'banner_target'  => $banner_default_target,
										'banner_comment' => StringUtil::specialchars(StringUtil::ampersand($this->arrCategoryValues['banner_default_name'])),
										'src'            => $this->urlEncode($this->arrCategoryValues['banner_default_image']),
										'alt'            => StringUtil::specialchars(StringUtil::ampersand($this->arrCategoryValues['banner_default_name'])),
										'size'     		 => '',
										'banner_pic'     => true,
										'banner_flash'   => false,
										'banner_text'    => false,
										'banner_video'   => false,
										'banner_empty'   => false,	// issues 733
										'picture'        => $picture
									);
					break;
			}
			$arrResults[] = $arrBanners[0];
			$this->Template->banners = $arrResults;
			$this->Template->bmid = "bmid" . $module_id;

			return $this->Template;
		}
		// Kein BannerDefault
		$NoBannerFound = ($GLOBALS['TL_LANG']['MSC']['tl_banner']['noBanner']) ?: 'no banner, no default banner';
		$arrBanners[] =
						array(
							'banner_key'  => 'bid',
							'banner_wrap_id'    => $banner_cssID,
							'banner_wrap_class' => $banner_class,
							'banner_id'   => 0,
							'banner_name' => StringUtil::specialchars(StringUtil::ampersand($NoBannerFound)),
							'banner_url'  => '',
							'banner_target'  => '',
							'banner_comment' => '',
							'src' 			=> '',
							'alt' 			=> '',
							'size'     		=> '',
							'banner_pic' 	=> false,
							'banner_flash'  => false,
							'banner_text'   => false,
							'banner_video'  => false,
							'banner_empty'  => true	// issues 733
						);
		$arrResults[] = $arrBanners[0];
		// Ausblenden wenn leer?
		if ($banner_hideempty == 1)
		{
			// auf Leer umschalten
			$this->strTemplate='mod_banner_empty';
			$this->Template->arrCategoryValues = $this->arrCategoryValues; // #7 / #176 (Banner)
			$this->Template = new FrontendTemplate($this->strTemplate);
			BannerLog::writeLog(__METHOD__, __LINE__, 'No BannerDefault, switch to empty template');
		}
		$this->Template->banners = $arrResults;
		$this->Template->bmid = "bmid" . $module_id;

		return $this->Template;
	}

	/**
	 * Get First View Banner
	 *
	 * @return internal Value in $this->strTemplate
	 */
	public function getSingleBannerFirst($module_id)
	{
		$arrBanners = array();
		$arrResults = array();
		$FileSrc = '';

		// first aktiv banner in category
		// $this->arrAllBannersBasic den ersten Datensatz über die ID nutzen
		// seltsamerweise kommt reference Fehler bei Kombination in einer Anweisung, daher getrennt
		$banner_keys = array_keys($this->arrAllBannersBasic);
		$banner_id   = array_shift($banner_keys);
		$objBanners  = Database::getInstance()
							->prepare(
								"SELECT
                            	            TLB.*
                                       FROM
                            	            tl_banner AS TLB
                                       WHERE
                                            TLB.`id`=?"
							)
							->limit(1)
							->execute($banner_id);
		$intRows = $objBanners->numRows;
		// Banner vorhanden?
		if ($intRows > 0)
		{
			$objBanners->next();
			BannerHelper::$arrBannerSeen[] = $objBanners->id;
			// CSS-ID/Klasse(n) je Banner, für den wrapper
			$banner_cssID   = '';
			$banner_class   = '';
			$banner_classes = '';
			$_cssID = StringUtil::deserialize($objBanners->banner_cssid);
			if (\is_array($_cssID))
			{
				if ($_cssID[0] != '')
				{
					$banner_cssID   = ' id="banner_' . $_cssID[0] . '"';
				}
				if ($_cssID[1] != '')
				{
					$banner_classes = explode(" ", $_cssID[1]);

					foreach ($banner_classes as $banner_classone)
					{
						$banner_class .= ' banner_' . $banner_classone;
					}
				}
			}

			switch ($objBanners->banner_type)
			{
				case self::BANNER_TYPE_INTERN:
					$objBannerInternal = new BannerInternal($objBanners, $banner_cssID, $banner_class);
					$objImageData = $objBannerInternal->generateImageData();

					$FileSrc = $objImageData->FileSrc;
					$picture = $objImageData->Picture;
					$arrImageSize = $objImageData->ImageSize;

					$arrBanners = $objBannerInternal->generateTemplateData($arrImageSize, $FileSrc, $picture);

					// anderes Template?
					if (
						($this->banner_template != $this->strTemplate)
						&& ($this->banner_template != '')
					) {
						$this->strTemplate = $this->banner_template;
						$this->Template = new FrontendTemplate($this->strTemplate);
					}
					$this->setStatViewUpdate($arrBanners, $module_id, $objBanners->banner_useragent);
					$this->Template->banners = $arrBanners;
					$this->Template->bmid = "bmid" . $module_id;

					return $this->Template;

					break;
				case self::BANNER_TYPE_EXTERN:
					$objBannerExternal = new BannerExternal($objBanners, $banner_cssID, $banner_class);
					$objImageData = $objBannerExternal->generateImageData();

					$FileSrc = $objImageData->FileSrc;
					$picture = $objImageData->Picture;
					$arrImageSize = $objImageData->ImageSize;

					$arrBanners = $objBannerExternal->generateTemplateData($arrImageSize, $FileSrc, $picture);

					// anderes Template?
					if (
						($this->banner_template != $this->strTemplate)
						&& ($this->banner_template != '')
					) {
						$this->strTemplate = $this->banner_template;
						$this->Template = new FrontendTemplate($this->strTemplate);
					}
					$this->setStatViewUpdate($arrBanners, $module_id, $objBanners->banner_useragent);
					$this->Template->banners = $arrBanners;
					$this->Template->bmid = "bmid" . $module_id;

					return $this->Template;

					break;
				case self::BANNER_TYPE_TEXT:
					$arrImageSize = false;
					break;
			}

			// Text Banner
			if ($objBanners->banner_type == 'banner_text')
			{
				$objBannerText = new BannerText($objBanners, $banner_cssID, $banner_class);
				$arrBanners = $objBannerText->generateTemplateData();

				// anderes Template?
				if (
					($this->banner_template != $this->strTemplate)
				 && ($this->banner_template != '')
				) {
					$this->strTemplate = $this->banner_template;
					$this->Template = new FrontendTemplate($this->strTemplate);
				}
				$arrResults[] = $arrBanners[0];
				$this->Template->banners = $arrResults;
				$this->Template->bmid = "bmid" . $module_id;

				$this->setStatViewUpdate($arrResults, $module_id, $objBanners->banner_useragent);

				return $this->Template;
			}

			// Video Banner
			if ($objBanners->banner_type === BannerVideo::BANNER_TYPE_VIDEO)
			{
				$objBannerVideo = new BannerVideo($objBanners, $banner_cssID, $banner_class);
				$arrBanners = $objBannerVideo->generateTemplateData();

				// anderes Template?
				if (
					($this->banner_template != $this->strTemplate)
					&& ($this->banner_template != '')
				) {
					$this->strTemplate = $this->banner_template;
					$this->Template = new FrontendTemplate($this->strTemplate);
				}
				$arrResults[] = $arrBanners[0];
				$this->Template->banners = $arrResults;
				$this->Template->bmid = "bmid" . $module_id;

				$this->setStatViewUpdate($arrResults, $module_id, $objBanners->banner_useragent);

				return $this->Template;
			}
		}// Banner vorhanden
		// falls $arrImageSize = false  und kein Text Banner
		$this->Template->banners = $arrBanners; // leeres array
		$this->Template->bmid = "bmid" . $module_id;

		return $this->Template;
	}

	public function getSingleBanner($module_id)
	{
		$objBannerLogic = new BannerLogic();

		// RandomBlocker entfernen falls möglich und nötig
		if (\count($this->arrAllBannersBasic) >1) // einer muss ja übrig bleiben
		{
			$intRandomBlockerID = $objBannerLogic->getRandomBlockerId($module_id);
			if (isset($this->arrAllBannersBasic[$intRandomBlockerID]))
			{
				unset($this->arrAllBannersBasic[$intRandomBlockerID]);
			}
		}

		// Gewichtung nach vorhandenen Wichtungen
		$SingleBannerWeighting = $objBannerLogic->getSingleWeighting($this->arrAllBannersBasic);

		// alle Basic Daten durchgehen und die löschen die nicht der Wichtung entsprechen
		foreach ($this->arrAllBannersBasic as $key => $val)
		{
			if ($val != $SingleBannerWeighting)
			{
				unset($this->arrAllBannersBasic[$key]);
			}
		}
		reset($this->arrAllBannersBasic); // sicher ist sicher

		// Zufallszahl
		// array_shuffle und array_rand zu "ungenau"
		$intShowBanner =  random_int(1, \count($this->arrAllBannersBasic));
		$banner_keys = array_keys($this->arrAllBannersBasic);

		for ($xx=1; $xx<=$intShowBanner; $xx++)
		{
			$banner_id   = array_shift($banner_keys);
		}

		// Random Blocker setzen
		$objBannerLogic->setRandomBlockerId($banner_id, $module_id);

		$objBanners  = Database::getInstance()
							->prepare(
								"SELECT
                            	            TLB.*
                                       FROM
                            	            tl_banner AS TLB
                                       WHERE
                                            TLB.`id`=?"
							)
							->limit(1)
							->execute($banner_id);
		$intRows = $objBanners->numRows;
		// Banner vorhanden?
		if ($intRows > 0)
		{
			$objBanners->next();
			BannerHelper::$arrBannerSeen[] = $objBanners->id;
			// CSS-ID/Klasse(n) je Banner, für den wrapper
			$banner_cssID   = '';
			$banner_class   = '';
			$banner_classes = '';
			$_cssID = StringUtil::deserialize($objBanners->banner_cssid);
			if (\is_array($_cssID))
			{
				if ($_cssID[0] != '')
				{
					$banner_cssID   = ' id="banner_' . $_cssID[0] . '"';
				}
				if ($_cssID[1] != '')
				{
					$banner_classes = explode(" ", $_cssID[1]);

					foreach ($banner_classes as $banner_classone)
					{
						$banner_class .= ' banner_' . $banner_classone;
					}
				}
			}

			switch ($objBanners->banner_type)
			{
				case self::BANNER_TYPE_INTERN:
					$objBannerInternal = new BannerInternal($objBanners, $banner_cssID, $banner_class);
					$objImageData = $objBannerInternal->generateImageData();

					$FileSrc = $objImageData->FileSrc;
					$picture = $objImageData->Picture;
					$arrImageSize = $objImageData->ImageSize;

					$arrBanners = $objBannerInternal->generateTemplateData($arrImageSize, $FileSrc, $picture);

					// anderes Template?
					if (
						($this->banner_template != $this->strTemplate)
						&& ($this->banner_template != '')
					) {
						$this->strTemplate = $this->banner_template;
						$this->Template = new FrontendTemplate($this->strTemplate);
					}
					$this->setStatViewUpdate($arrBanners, $module_id, $objBanners->banner_useragent);
					$this->Template->banners = $arrBanners;
					$this->Template->bmid = "bmid" . $module_id;

					return $this->Template;

					break;
				case self::BANNER_TYPE_EXTERN:
					$objBannerExternal = new BannerExternal($objBanners, $banner_cssID, $banner_class);
					$objImageData = $objBannerExternal->generateImageData();

					$FileSrc = $objImageData->FileSrc;
					$picture = $objImageData->Picture;
					$arrImageSize = $objImageData->ImageSize;

					$arrBanners = $objBannerExternal->generateTemplateData($arrImageSize, $FileSrc, $picture);

					// anderes Template?
					if (
						($this->banner_template != $this->strTemplate)
						&& ($this->banner_template != '')
					) {
						$this->strTemplate = $this->banner_template;
						$this->Template = new FrontendTemplate($this->strTemplate);
					}
					$this->setStatViewUpdate($arrBanners, $module_id, $objBanners->banner_useragent);
					$this->Template->banners = $arrBanners;
					$this->Template->bmid = "bmid" . $module_id;

					return $this->Template;
					break;
				case self::BANNER_TYPE_TEXT:
					$arrImageSize = false;
					break;
			}

			// Text Banner
			if ($objBanners->banner_type == 'banner_text')
			{
				$objBannerText = new BannerText($objBanners, $banner_cssID, $banner_class);
				$arrBanners = $objBannerText->generateTemplateData();

				// anderes Template?
				if (
					($this->banner_template != $this->strTemplate)
				 && ($this->banner_template != '')
				) {
					$this->strTemplate = $this->banner_template;
					$this->Template = new FrontendTemplate($this->strTemplate);
				}
				$arrResults[] = $arrBanners[0];
				$this->Template->banners = $arrResults;
				$this->Template->bmid = "bmid" . $module_id;

				$this->setStatViewUpdate($arrResults, $module_id, $objBanners->banner_useragent);

				return $this->Template;
			}

			// Video Banner
			if ($objBanners->banner_type === BannerVideo::BANNER_TYPE_VIDEO)
			{
				$objBannerVideo = new BannerVideo($objBanners, $banner_cssID, $banner_class);
				$arrBanners = $objBannerVideo->generateTemplateData();

				// anderes Template?
				if (
					($this->banner_template != $this->strTemplate)
					&& ($this->banner_template != '')
				) {
					$this->strTemplate = $this->banner_template;
					$this->Template = new FrontendTemplate($this->strTemplate);
				}
				$arrResults[] = $arrBanners[0];
				$this->Template->banners = $arrResults;
				$this->Template->bmid = "bmid" . $module_id;

				$this->setStatViewUpdate($arrResults, $module_id, $objBanners->banner_useragent);

				return $this->Template;
			}
		}// Banner vorhanden
		// falls $arrImageSize = false  und kein Text Banner
		$this->Template->banners = array(); // leeres array
		$this->Template->bmid = "bmid" . $module_id;

		return $this->Template;
	}

	protected function setStatViewUpdate($arrBannerData, $module_id, $banner_useragent)
	{
		$objBannerCount = new BannerCount($arrBannerData, $banner_useragent, $module_id);
		$objBannerCount->setStatViewUpdate();
		unset($objBannerCount);
	}
}
