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

namespace BugBuster\BannerStatistics;

use BugBuster\Banner\BannerHelper;
use BugBuster\Banner\BannerImage;
use BugBuster\Banner\BannerLog;
use BugBuster\Banner\BannerVideo;
use Contao\Environment;
use Contao\File;
use Contao\FilesModel;
use Contao\Image;
use Contao\Image\ResizeConfiguration;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Imagine\Exception\RuntimeException;

/**
 * Class ModuleBannerStatistics
 */
class ModuleBannerStatistics extends BannerStatisticsHelper
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_banner_stat';

	/**
	 * Kat ID
	 * @var int
	 */
	protected $intCatID;

	protected $BannerImage;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		System::loadLanguageFile('tl_banner_stat');

		if ((int) Input::get('id') == 0)
		{
			$this->intCatID = (int) Input::post('id'); // id for redirect on banner reset, category reset
		}
		else
		{
			$this->intCatID = (int) Input::get('id'); // directly category link
		}

		if (Input::post('act', true)=='zero') // action banner reset, category reset
		{
			$this->setZero();
		}
		// Get Debug Settings
		$objBannerHelper = new BannerHelper();
		$objBannerHelper->setDebugSettings($this->intCatID);
	}

	/**
	 * Generate module
	 */
	protected function compile()
	{
		$arrBanners      = array();
		$arrBannersStat  = array();
		$intCatIdAllowed = false;
		$number_clicks   = 0;
		$number_views    = 0;

		// alle Kategorien holen die der User sehen darf
		$arrBannerCategories = $this->getBannerCategoriesByUsergroups();
		// no categories : array('id' => '0', 'title' => '---------');
		// empty array   : array('id' => '0', 'title' => '---------');
		// array[0..n]   : array(0, array('id' => '1', ....), 1, ....)

		if ($this->intCatID == 0) // direkter Aufruf ohne ID
		{
			$this->intCatID = $this->getCatIdByCategories($arrBannerCategories);
		}
		else
		{
			// ID des Aufrufes erlaubt?
			foreach ($arrBannerCategories as $value)
			{
				if ($this->intCatID == $value['id'])
				{
					$intCatIdAllowed = true;
				}
			}
			if (false === $intCatIdAllowed)
			{
				$this->intCatID = $this->getCatIdByCategories($arrBannerCategories);
			}
		}
		$arrBanners = $this->getBannersByCatID($this->intCatID);
		$number_active   = 0;
		$number_inactive = 0;

		foreach ($arrBanners as $Banner)
		{
			// Aufteilen nach intern, extern, text Banner
			switch ($Banner['banner_type'])
			{
				case self::BANNER_TYPE_INTERN:
					// generate data
					$arrBannersStat[] = $this->addBannerIntern($Banner);
					break;
				case self::BANNER_TYPE_EXTERN:
					// generate data
					$arrBannersStat[] = $this->addBannerExtern($Banner);
					break;
				case self::BANNER_TYPE_TEXT:
					// generate data
					$arrBannersStat[] = $this->addBannerText($Banner);
					break;
				case BannerVideo::BANNER_TYPE_VIDEO:
					// generate data
					$arrBannersStat[] = $this->addBannerVideo($Banner);
					break;
			}
			// Gesamt Aktiv / Inaktiv zählen
			if ($Banner['banner_published_class'] == 'published')
			{
				$number_active++;
			}
			else
			{
				$number_inactive++;
			}
			// Gesamt Views / Klicks zählen
			$number_clicks += (int) $Banner['banner_clicks'];
			$number_views  += (int) $Banner['banner_views'];
		}

		$this->Template->bannersstat      = $arrBannersStat;
		$this->Template->number_active    = $number_active;
		$this->Template->number_inactive  = $number_inactive;
		$this->Template->number_clicks    = $number_clicks;
		$this->Template->number_views     = $number_views;
		$this->Template->header_id        = $GLOBALS['TL_LANG']['tl_banner_stat']['id'];
		$this->Template->header_picture   = $GLOBALS['TL_LANG']['tl_banner_stat']['picture'];
		$this->Template->header_name      = $GLOBALS['TL_LANG']['tl_banner_stat']['name'];
		$this->Template->header_url       = $GLOBALS['TL_LANG']['tl_banner_stat']['URL'];
		$this->Template->header_active    = $GLOBALS['TL_LANG']['tl_banner_stat']['active'];
		$this->Template->header_prio      = $GLOBALS['TL_LANG']['tl_banner_stat']['Prio'];
		$this->Template->header_clicks    = $GLOBALS['TL_LANG']['tl_banner_stat']['clicks'];
		$this->Template->header_views     = $GLOBALS['TL_LANG']['tl_banner_stat']['views'];
		$this->Template->banner_version   = $GLOBALS['TL_LANG']['tl_banner_stat']['modname'] . ' ' . BANNER_VERSION . '.' . BANNER_BUILD;
		$this->Template->banner_footer    = $GLOBALS['TL_LANG']['tl_banner_stat']['comment'] ?? '';
		$this->Template->banner_base      = Environment::get('base');
		$this->Template->banner_base_be   = Environment::get('base') . 'contao'; // TODO deprecated
		$this->Template->theme            = $this->getTheme();
		$this->Template->theme0           = 'default';
		$this->Template->requestToken     = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();

		$this->Template->bannercats    = $arrBannerCategories;
		$this->Template->bannercatid   = $this->intCatID;
		$this->Template->bannerstatcat = $GLOBALS['TL_LANG']['tl_banner_stat']['kat'];
		$this->Template->bannerzero    = $GLOBALS['TL_LANG']['tl_banner_stat']['banner_zero'];
		$this->Template->bannercatzero        = $GLOBALS['TL_LANG']['tl_banner_stat']['cat_zero'];
		$this->Template->bannercatzerobutton  = $GLOBALS['TL_LANG']['tl_banner_stat']['cat_zero_button'];
		$this->Template->bannercatzerotext    = $GLOBALS['TL_LANG']['tl_banner_stat']['cat_zero_text'];
		$this->Template->bannercatzeroconfirm = $GLOBALS['TL_LANG']['tl_banner_stat']['cat_zero_confirm'];
		$this->Template->bannerclickthroughrate     = $GLOBALS['TL_LANG']['tl_banner_stat']['click_through_rate'];
		$this->Template->bannernumberactiveinactive = $GLOBALS['TL_LANG']['tl_banner_stat']['number_active_inactive'];
		$this->Template->bannernumberviewsclicks    = $GLOBALS['TL_LANG']['tl_banner_stat']['number_views_clicks'];

		$this->Template->banner_hook_panels = $this->addStatisticPanelLineHook();
	} // compile

	/**
	 * Add textbanner
	 *
	 * @param  referenz $Banner
	 * @return array
	 */
	protected function addBannerText(&$Banner)
	{
		$arrBannersStat = array();
		// Kurz URL (nur Domain)
		$this->setBannerURL($Banner);
		BannerLog::writeLog(__METHOD__, __LINE__, 'banner_url: ', $Banner['banner_url']);
		$Banner['banner_url'] = BannerHelper::decodePunycode($Banner['banner_url']); // #79
		BannerLog::writeLog(__METHOD__, __LINE__, 'banner_url punydecode: ', $Banner['banner_url']);
		$treffer = parse_url($Banner['banner_url']);
		$banner_url_kurz = $treffer['host'] ?? '';
		if (isset($treffer['port']))
		{
			$banner_url_kurz .= ':' . $treffer['port'];
		}
		$MaxViewsClicks = $this->getMaxViewsClicksStatus($Banner);
		$this->setBannerPublishedActive($Banner);

		$arrBannersStat['banner_id']    = $Banner['id'];
		$arrBannersStat['banner_name']    = StringUtil::specialchars(StringUtil::ampersand($Banner['banner_name']));
		$arrBannersStat['banner_comment']    = nl2br($Banner['banner_comment']);
		$arrBannersStat['banner_url_kurz']    = $banner_url_kurz;
		$arrBannersStat['banner_url']    = \strlen($Banner['banner_url']) <61 ? $Banner['banner_url'] : substr($Banner['banner_url'], 0, 28) . "[...]" . substr($Banner['banner_url'], -24, 24);
		$arrBannersStat['banner_prio']    = $GLOBALS['TL_LANG']['tl_banner_stat']['prio'][$Banner['banner_weighting']];
		$arrBannersStat['banner_views']    = ($MaxViewsClicks[0]) ? $Banner['banner_views'] . '<br>' . $GLOBALS['TL_LANG']['tl_banner_stat']['max_yes'] : $Banner['banner_views'];
		$arrBannersStat['banner_clicks']    = ($MaxViewsClicks[1]) ? $Banner['banner_clicks'] . '<br>' . $GLOBALS['TL_LANG']['tl_banner_stat']['max_yes'] : $Banner['banner_clicks'];
		$arrBannersStat['banner_active']    = $Banner['banner_active'];
		$arrBannersStat['banner_pub_class']    = $Banner['banner_published_class'];
		$arrBannersStat['banner_zero']    = $GLOBALS['TL_LANG']['tl_banner_stat']['zero_text'];
		$arrBannersStat['banner_confirm']    = $GLOBALS['TL_LANG']['tl_banner_stat']['zero_confirm'];
		$arrBannersStat['banner_pic']    = false; // Es ist kein Bild
		$arrBannersStat['banner_flash']    = false;
		$arrBannersStat['banner_text']    = true;   // Es ist ein Textbanner
		$arrBannersStat['banner_video']   = false;

		return $arrBannersStat;
	}

	/**
	 * Add videobanner
	 *
	 * @param  referenz $Banner
	 * @return array
	 */
	protected function addBannerVideo(&$Banner)
	{
		$arrBannersStat = array();
		// Kurz URL (nur Domain)
		$this->setBannerURL($Banner);
		BannerLog::writeLog(__METHOD__, __LINE__, 'banner_url: ', $Banner['banner_url']);
		$Banner['banner_url'] = BannerHelper::decodePunycode($Banner['banner_url']); // #79
		BannerLog::writeLog(__METHOD__, __LINE__, 'banner_url punydecode: ', $Banner['banner_url']);
		$treffer = parse_url($Banner['banner_url']);
		$banner_url_kurz = $treffer['host'] ?? '';
		if (isset($treffer['port']))
		{
			$banner_url_kurz .= ':' . $treffer['port'];
		}
		$MaxViewsClicks = $this->getMaxViewsClicksStatus($Banner);
		$this->setBannerPublishedActive($Banner);

		$objFiles = FilesModel::findMultipleByUuidsAndExtensions(
			StringUtil::deserialize($Banner['banner_playerSRC'], true),
			array('mp4', 'm4v', 'mov', 'wmv', 'webm', 'ogv')
		);
		$filelist = '<ul>';

		while ($objFiles && $objFiles->next())
		{
			$objFile = new File($objFiles->path);
			$filelist .= '<li>' . Image::getHtml($objFile->icon, '', 'class="mime_icon"') . ' <span>' . $objFile->name . '</span> <span class="size">(' . $this->getReadableSize($objFile->size) . ')</span></li>';
		}

		$filelist .= '</ul>';

		// Poster
		$thumbnail = '';
		if ($Banner['banner_posterSRC'] && ($objFileThumb = FilesModel::findByUuid($Banner['banner_posterSRC'])) !== null)
		{
			try
			{
				$thumbnail = '<span style="font-weight: bold;">' . $GLOBALS['TL_LANG']['tl_banner_stat']['poster'] . ':</span><br>';
				$thumbnailPath = $objFileThumb->path;
				$rootDir = System::getContainer()->getParameter('kernel.project_dir');
				$thumbnail .= Image::getHtml(
					System::getContainer()
						->get('contao.image.factory') // 4.13 contao.image.factory
						->create(
							$rootDir . '/' . $thumbnailPath,
							(new ResizeConfiguration())
								->setWidth(120)
								->setHeight(120)
								->setMode(ResizeConfiguration::MODE_BOX)
								->setZoomLevel(100)
						)
						->getUrl($rootDir),
					'poster-image',
					'class="poster-image"'
				);
				$thumbnail .= '<br>';
			}
			catch (RuntimeException $e)
			{
				$thumbnail = '<br><p class="preview-image broken-image">Broken poster image!</p><br>';
			}
		}

		$arrBannersStat['banner_id']       = $Banner['id'];
		$arrBannersStat['banner_name']     = StringUtil::specialchars(StringUtil::ampersand($Banner['banner_name']));
		$arrBannersStat['banner_comment']  = nl2br($Banner['banner_comment']);
		$arrBannersStat['banner_url_kurz'] = $banner_url_kurz;
		$arrBannersStat['banner_url']      = \strlen($Banner['banner_url']) <61 ? $Banner['banner_url'] : substr($Banner['banner_url'], 0, 28) . "[...]" . substr($Banner['banner_url'], -24, 24);
		$arrBannersStat['banner_prio']     = $GLOBALS['TL_LANG']['tl_banner_stat']['prio'][$Banner['banner_weighting']];
		$arrBannersStat['banner_views']    = ($MaxViewsClicks[0]) ? $Banner['banner_views'] . '<br>' . $GLOBALS['TL_LANG']['tl_banner_stat']['max_yes'] : $Banner['banner_views'];
		$arrBannersStat['banner_clicks']   = ($MaxViewsClicks[1]) ? $Banner['banner_clicks'] . '<br>' . $GLOBALS['TL_LANG']['tl_banner_stat']['max_yes'] : $Banner['banner_clicks'];
		$arrBannersStat['banner_active']   = $Banner['banner_active'];
		$arrBannersStat['banner_pub_class'] = $Banner['banner_published_class'];
		$arrBannersStat['banner_zero']     = $GLOBALS['TL_LANG']['tl_banner_stat']['zero_text'];
		$arrBannersStat['banner_confirm']  = $GLOBALS['TL_LANG']['tl_banner_stat']['zero_confirm'];
		$arrBannersStat['banner_pic']      = false; // Es ist kein Bild
		$arrBannersStat['banner_flash']    = false;
		$arrBannersStat['banner_text']     = false;
		$arrBannersStat['banner_video']    = true;
		$arrBannersStat['banner_videos']   = '<span style="font-weight: bold;">' . $GLOBALS['TL_LANG']['tl_banner_stat']['player_src'] . ':</span><br>' . $filelist;
		$arrBannersStat['banner_poster']   = $thumbnail;

		return $arrBannersStat;
	}

	/**
	 * Add internal banner
	 *
	 * @param  referenz $Banner
	 * @return array
	 */
	protected function addBannerIntern(&$Banner)
	{
		$oriSize = false;

		// return array(bool $intMaxViews, bool $intMaxClicks)
		$MaxViewsClicks = $this->getMaxViewsClicksStatus($Banner);

		// set $Banner['banner_active'] as HTML Text
		// and $Banner['banner_published_class'] published/unpublished
		$this->setBannerPublishedActive($Banner);
		$this->setBannerURL($Banner);
		$Banner['banner_url'] = BannerHelper::decodePunycode($Banner['banner_url']); // #79
		$Banner['banner_url'] = preg_replace('/^app_dev\.php\//', '', $Banner['banner_url']); // #22
		// Pfad+Dateiname holen ueber UUID (findByPk leitet um auf findByUuid)
		$objFile = FilesModel::findByPk($Banner['banner_image']);
		// BannerImage Class
		$this->BannerImage = new BannerImage();

		// Banner Art und Größe bestimmen
		$arrImageSize = $this->BannerImage->getBannerImageSize($objFile->path, self::BANNER_TYPE_INTERN);

		// 1 = GIF, 2 = JPG, 3 = PNG
		// 4 = SWF, 13 = SWC (zip-like swf file)
		// 5 = PSD, 6 = BMP, 7 = TIFF(intel byte order), 8 = TIFF(motorola byte order)
		// 9 = JPC, 10 = JP2, 11 = JPX, 12 = JB2, 13 = SWC, 14 = IFF
		switch ($arrImageSize[2])
		{
			case 1:  // GIF
			case 2:  // JPG
			case 3:  // PNG
			case 18: // WEBP
			case 19: // AVIF
				// Check ob Banner zu groß für Anzeige, @return array $Width,$Height,$oriSize
				$arrNewBannerImageSize = $this->BannerImage->getCheckBannerImageSize($arrImageSize, 250, 200);
				break;
			default:
				break;
		}
		$intWidth  = $arrNewBannerImageSize[0];
		$intHeight = $arrNewBannerImageSize[1];
		$oriSize   = $arrNewBannerImageSize[2];

		return $this->generateTemplateData(self::BANNER_TYPE_INTERN, $Banner, $arrImageSize, $intWidth, $intHeight, $MaxViewsClicks, $oriSize, $objFile);
	} // addBannerIntern

	protected function addBannerExtern(&$Banner)
	{
		$oriSize = false;

		// return array(bool $intMaxViews, bool $intMaxClicks)
		$MaxViewsClicks = $this->getMaxViewsClicksStatus($Banner);

		// set $Banner['banner_active'] as HTML Text
		// and $Banner['banner_published_class'] published/unpublished
		$this->setBannerPublishedActive($Banner);
		$this->setBannerURL($Banner);
		$Banner['banner_url']   = BannerHelper::decodePunycode($Banner['banner_url']);

		// BannerImage Class
		$this->BannerImage = new BannerImage();

		// Banner Art und Größe bestimmen
		$arrImageSize = $this->BannerImage->getBannerImageSize($Banner['banner_image_extern'], self::BANNER_TYPE_EXTERN);

		// 1 = GIF, 2 = JPG, 3 = PNG
		// 4 = SWF, 13 = SWC (zip-like swf file)
		// 5 = PSD, 6 = BMP, 7 = TIFF(intel byte order), 8 = TIFF(motorola byte order)
		// 9 = JPC, 10 = JP2, 11 = JPX, 12 = JB2, 13 = SWC, 14 = IFF
		// 18 = WEBP, 10 = AVIF
		switch ($arrImageSize[2])
		{
			case 1:  // GIF
			case 2:  // JPG
			case 3:  // PNG
			case 18: // WEBP
			case 19: // AVIF
				// Check ob Banner zu groß für Anzeige, @return array $Width,$Height,$oriSize
				$arrNewBannerImageSize = $this->BannerImage->getCheckBannerImageSize($arrImageSize, 250, 200);
				break;
			default:
				break;
		}
		$intWidth  = $arrNewBannerImageSize[0];
		$intHeight = $arrNewBannerImageSize[1];
		$oriSize   = $arrNewBannerImageSize[2];
		unset($oriSize);

		return $this->generateTemplateData(self::BANNER_TYPE_EXTERN, $Banner, $arrImageSize, $intWidth, $intHeight, $MaxViewsClicks);
	} // addBannerExtern

	/**
	 * Hook: addStatisticPanelLine
	 * Search for registered BANNER HOOK: addStatisticPanelLine
	 *
	 * @return string HTML5 sourcecode | false
	 *                <code>
	 *                <!-- output minimum -->
	 *                <div class="tl_panel">
	 *                <!-- <p>hello world</p> -->
	 *                </div>
	 *                </code>
	 */
	protected function addStatisticPanelLineHook()
	{
		if (
			isset($GLOBALS['TL_BANNER_HOOKS']['addStatisticPanelLine'])
			&& \is_array($GLOBALS['TL_BANNER_HOOKS']['addStatisticPanelLine'])
		) {
			foreach ($GLOBALS['TL_BANNER_HOOKS']['addStatisticPanelLine'] as $callback)
			{
				$this->import($callback[0]);
				$result[] = $this->{$callback[0]}->{$callback[1]}($this->intCatID); // #170
			}

			return $result;
		}

		return false;
	}

	protected function generateTemplateData($strBannerType, &$Banner, $arrImageSize, $intWidth, $intHeight, $MaxViewsClicks, $oriSize=null, $objFile=null)
	{
		$arrBannersStat = array();

		switch ($arrImageSize[2])
		{
			case 1: // GIF
			case 2: // JPG
			case 3: // PNG
			case 18: // WEBP
			case 19: // AVIF
				if (self::BANNER_TYPE_EXTERN == $strBannerType)
				{
					$Banner['banner_image'] = $Banner['banner_image_extern']; // Banner URL
				}
				else
				{
					if ($oriSize || $arrImageSize[2] == 1) // GIF
					{
						$Banner['banner_image'] = $this->urlEncode($objFile->path);
					}
					else
					{
						$container = System::getContainer();
						$rootDir = $container->getParameter('kernel.project_dir');
						$Banner['banner_image'] = $container
													->get('contao.image.factory')
													->create($rootDir . '/' . $objFile->path, array($intWidth, $intHeight, 'proportional'))
													->getUrl($rootDir);
					}
				}

				$arrBannersStat['banner_id']     = $Banner['id'];
				$arrBannersStat['banner_style']     = 'padding-bottom: 4px;';
				$arrBannersStat['banner_name']     = StringUtil::specialchars(StringUtil::ampersand($Banner['banner_name']));
				$arrBannersStat['banner_alt']     = StringUtil::specialchars(StringUtil::ampersand($Banner['banner_name']));
				$arrBannersStat['banner_title']     = $Banner['banner_url'];
				$arrBannersStat['banner_url']     = \strlen($Banner['banner_url']) <61 ? $Banner['banner_url'] : substr($Banner['banner_url'], 0, 28) . "[...]" . substr($Banner['banner_url'], -24, 24);
				$arrBannersStat['banner_image']     = $Banner['banner_image'];
				$arrBannersStat['banner_width']     = $intWidth;
				$arrBannersStat['banner_height']     = $intHeight;
				$arrBannersStat['banner_prio']     = $GLOBALS['TL_LANG']['tl_banner_stat']['prio'][$Banner['banner_weighting']];
				$arrBannersStat['banner_views']     = ($MaxViewsClicks[0]) ? $Banner['banner_views'] . '<br>' . $GLOBALS['TL_LANG']['tl_banner_stat']['max_yes'] : $Banner['banner_views'];
				$arrBannersStat['banner_clicks']     = ($MaxViewsClicks[1]) ? $Banner['banner_clicks'] . '<br>' . $GLOBALS['TL_LANG']['tl_banner_stat']['max_yes'] : $Banner['banner_clicks'];
				$arrBannersStat['banner_active']     = $Banner['banner_active'];
				$arrBannersStat['banner_pub_class']    = $Banner['banner_published_class'];
				$arrBannersStat['banner_zero']     = $GLOBALS['TL_LANG']['tl_banner_stat']['zero_text'];
				$arrBannersStat['banner_confirm']     = $GLOBALS['TL_LANG']['tl_banner_stat']['zero_confirm'];
				$arrBannersStat['banner_pic']     = true; // Es ist ein Bild
				$arrBannersStat['banner_flash']     = false;
				$arrBannersStat['banner_text']     = false;
				$arrBannersStat['banner_video']   = false;
				break;
			default:
				if (self::BANNER_TYPE_EXTERN == $strBannerType)
				{
					$Banner['banner_image'] = $Banner['banner_image_extern']; // Banner URL
				}
				else
				{
					$Banner['banner_image'] = $this->urlEncode($objFile->path);
				}

				$arrBannersStat['banner_pic']     = true;
				$arrBannersStat['banner_flash']     = false;
				$arrBannersStat['banner_text']     = false;
				$arrBannersStat['banner_video']   = false;
				$arrBannersStat['banner_prio']     = $GLOBALS['TL_LANG']['tl_banner_stat']['prio'][$Banner['banner_weighting']];
				$arrBannersStat['banner_views']     = ($MaxViewsClicks[0]) ? $Banner['banner_views'] . '<br>' . $GLOBALS['TL_LANG']['tl_banner_stat']['max_yes'] : $Banner['banner_views'];
				$arrBannersStat['banner_clicks']     = ($MaxViewsClicks[1]) ? $Banner['banner_clicks'] . '<br>' . $GLOBALS['TL_LANG']['tl_banner_stat']['max_yes'] : $Banner['banner_clicks'];
				$arrBannersStat['banner_active']     = $Banner['banner_active'];
				$arrBannersStat['banner_style']     = 'color:red;font-weight:bold;';
				$arrBannersStat['banner_alt']     = $GLOBALS['TL_LANG']['tl_banner_stat']['read_error'];
				$arrBannersStat['banner_url']     = $Banner['banner_image'];
				break;
		} // switch

		return $arrBannersStat;
	}
} // class
