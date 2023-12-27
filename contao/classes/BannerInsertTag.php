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

/**
 * Run in a custom namespace, so the class can be replaced
 */

namespace BugBuster\Banner;

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Database;
use Contao\FrontendTemplate;
use Contao\StringUtil;

/**
 * Class BannerInsertTag
 *
 * @copyright  Glen Langer 2007..2022 <http://contao.ninja>
 * @license    LGPL
 */
class BannerInsertTag extends BannerHelper
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_banner_list_all';

	protected $typePrefix = ''; // wie wurde das Modul eingebaut
	// ce_  als Artikelelement
	// mod_ als Modul direkt im Layout

	// backward compatibility over getModuleData()
	protected $banner_hideempty  = '';

	protected $banner_firstview  = '';

	protected $banner_categories = '';

	protected $banner_template   = '';

	protected $banner_redirect   = '';

	protected $banner_useragent  = '';

	protected $cssID             = ''; // serialisiertes Array

	protected $headline          = ''; // serialisiertes Array, unit und value

	protected $article_class     = ''; // mod_banner artikelcssclass

	protected $article_cssID     = ''; // id="artikelcssid"

	protected $article_style     = ''; // margin-top:55px; margin-bottom:66px;

	protected $outputFormat      = 'html5';

	protected $templatepfad      = 'templates';

	protected $module_id          = 0;

	protected $Template;

	/**
	 * replaceInsertTagsBanner
	 *
	 * @param  unknown      $strTag
	 * @return boolean|void
	 */
	public function replaceInsertTagsBanner($strTag)
	{
		$arrTag = StringUtil::trimsplit('::', $strTag);
		if ($arrTag[0] != 'banner_module')
		{
			if ($arrTag[0] != 'cache_banner_module')
			{
				return false; // nicht für uns
			}
		}

		if (isset($arrTag[1])) // banner_module_id
		{
			$retModuleData = $this->getModuleData($arrTag[1]);
			if (false === $retModuleData)
			{
				// kein Banner Modul mit dieser ID
				BannerLog::log('No banner module with this id "' . $arrTag[1] . '"', 'ModuleBannerTag replaceInsertTagsBanner', ContaoContext::ERROR);

				return false;
			}
		}
		else
		{
			// keine Banner Modul ID
			BannerLog::log('Missing parameter (1): banner module id', 'ModuleBannerTag replaceInsertTagsBanner', ContaoContext::ERROR);

			return false;
		}
		// Get Debug Settings
		$this->setDebugSettings($this->banner_categories);
		BannerLog::writeLog(__METHOD__, __LINE__, 'banner_categories: ' . $this->banner_categories);

		if (isset($arrTag[2]))
		{
			$this->typePrefix    = $arrTag[2];
		} // ce_ / mod_
		if (isset($arrTag[3]))
		{
			$this->article_class = $arrTag[3];
		}
		if (isset($arrTag[4]))
		{
			$this->article_cssID = $arrTag[4];
		}
		if (isset($arrTag[5]))
		{
			$this->article_style = $arrTag[5];
		}
		if (isset($arrTag[6]))
		{
			$this->outputFormat  = $arrTag[6];
		}
		if (isset($arrTag[7]))
		{
			$this->templatepfad  = $arrTag[7];
		}

		BannerLog::writeLog(__METHOD__, __LINE__, 'Insert Tag Parameter: ' . print_r($arrTag, true));

		return $this->generateBanner();
	}

	/**
	 * getModuleData
	 *
	 * Wrapper for backward compatibility
	 *
	 * @param  integer $moduleId
	 * @return boolean
	 */
	protected function getModuleData($moduleId)
	{
		$this->module_id = $moduleId; // for RandomBlocker Session
		$objBannerModule = Database::getInstance()->prepare("SELECT
                                                                    banner_hideempty,
                                                        	        banner_firstview,
                                                        	        banner_categories,
                                                        	        banner_template,
                                                        	        banner_redirect,
                                                        	        banner_useragent,
                                                                    cssID,
                                                                    headline
                                                                FROM
                                                                    tl_module
                                                                WHERE
                                                                    id=?
                                                                AND
                                                                    type=?")
													 ->execute($moduleId, 'banner');
		if ($objBannerModule->numRows == 0)
		{
			return false;
		}
		$this->banner_hideempty  = $objBannerModule->banner_hideempty;
		$this->banner_firstview  = $objBannerModule->banner_firstview;
		$this->banner_categories = $objBannerModule->banner_categories;
		$this->banner_template   = $objBannerModule->banner_template;
		$this->banner_redirect   = $objBannerModule->banner_redirect;
		$this->banner_useragent  = $objBannerModule->banner_useragent;
		$this->cssID             = $objBannerModule->cssID;
		$this->headline          = $objBannerModule->headline;

		return true;
	}

	/**
	 * generateBanner
	 *
	 * @return boolean|string
	 */
	protected function generateBanner()
	{
		// DEBUG log_message('generateBanner banner_categories:'.$this->banner_categories,'Banner.log');
		if ($this->bannerHelperInit() === false)
		{
			BannerLog::log('Problem in bannerHelperInit', 'ModuleBannerTag generateBanner', ContaoContext::ERROR);

			return false;
		}

		if ($this->statusBannerFrontendGroupView === false)
		{
			// Eingeloggter FE Nutzer darf nichts sehen, falsche Gruppe
			// auf Leer umschalten
			$this->strTemplate='mod_banner_empty';
			$this->Template = new FrontendTemplate($this->strTemplate);

			return $this->Template->parse();
		}
		$this->Template = new FrontendTemplate($this->strTemplate);

		if ($this->statusAllBannersBasic === false)
		{
			// keine Banner vorhanden in der Kategorie
			// default Banner holen
			// kein default Banner, ausblenden wenn leer?
			// alt $this->getDefaultBanner();
			$objBannerSingle = new BannerSingle($this->arrCategoryValues, $this->banner_template, $this->strTemplate, $this->Template, $this->arrAllBannersBasic);
			$this->Template = $objBannerSingle->getDefaultBanner($this->banner_hideempty, $this->module_id);
			// Css generieren
			$this->setCssClassIdStyle();

			// Template parsen und Ergebnis zurückgeben
			return $this->Template->parse();
		}

		// OK, Banner vorhanden, dann weiter
		// BannerSeen vorhanden? Dann beachten.
		if (\count(BannerHelper::$arrBannerSeen))  // TODO
		{// $arrAllBannersBasic dezimieren um die bereits angezeigten
			foreach (BannerHelper::$arrBannerSeen as $BannerSeenID)
			{
				if (\array_key_exists($BannerSeenID, $this->arrAllBannersBasic))
				{
					unset($this->arrAllBannersBasic[$BannerSeenID]);
				}
			}
			// noch Banner übrig?
			if (\count($this->arrAllBannersBasic) == 0)
			{
				// default Banner holen
				// kein default Banner, ausblenden wenn leer?
				// alt $this->getDefaultBanner();
				$objBannerSingle = new BannerSingle($this->arrCategoryValues, $this->banner_template, $this->strTemplate, $this->Template, $this->arrAllBannersBasic);
				$this->Template = $objBannerSingle->getDefaultBanner($this->banner_hideempty, $this->module_id);
				// Css generieren
				$this->setCssClassIdStyle();

				return $this->Template->parse();
			}
		}

		// OK, noch Banner übrig, weiter gehts
		// Single Banner?
		if ($this->arrCategoryValues['banner_numbers'] != 1)
		{
			// FirstViewBanner?
			$objBannerLogic = new BannerLogic();

			if ($objBannerLogic->getSetFirstView($this->banner_firstview, $this->banner_categories, $this->module_id) === true)
			{
				// alt $this->getSingleBannerFirst();
				$objBannerSingle = new BannerSingle($this->arrCategoryValues, $this->banner_template, $this->strTemplate, $this->Template, $this->arrAllBannersBasic);
				$this->Template = $objBannerSingle->getSingleBannerFirst($this->module_id);
				// Css generieren
				$this->setCssClassIdStyle();

				return $this->Template->parse();
			}
			// single banner
			$objBannerSingle = new BannerSingle($this->arrCategoryValues, $this->banner_template, $this->strTemplate, $this->Template, $this->arrAllBannersBasic);
			$this->Template = $objBannerSingle->getSingleBanner($this->module_id);
			// Css generieren
			$this->setCssClassIdStyle();

			return $this->Template->parse();
		}
		// multi banner
		$objBannerMultiple = new BannerMultiple($this->arrCategoryValues, $this->banner_template, $this->strTemplate, $this->Template, $this->arrAllBannersBasic);
		$this->Template = $objBannerMultiple->getMultiBanner($this->module_id);
		// Css generieren
		$this->setCssClassIdStyle();

		return $this->Template->parse();
	}

	/**
	 * setCssClassIdStyle
	 */
	protected function setCssClassIdStyle()
	{
		// Modul direkt im Layout
		if ('mod_' == $this->typePrefix)
		{
			// CSS-ID/Klasse
			$_cssID = StringUtil::deserialize($this->cssID);
			$this->Template->cssID = '';
			$this->Template->class = 'mod_banner';
			if ($_cssID[0] != '')
			{
				$this->Template->cssID = ' id="' . $_cssID[0] . '"';
			}
			if ($_cssID[1] != '')
			{
				$this->Template->class .= ' ' . $_cssID[1];
			}

			// Abstand davor und dahinter, wenn vorhanden
			if (!empty($this->space))
			{
				$_style = StringUtil::deserialize($this->space);
				if ("" != $_style[0])
				{
					$this->Template->style .= 'margin-top:' . $_style[0] . 'px; ';
				}
				if ("" != $_style[1])
				{
					$this->Template->style .= 'margin-bottom:' . $_style[1] . 'px;';
				}
			}
		}
		// Modul als Artikelelement
		if ('ce_' == $this->typePrefix)
		{
			$this->Template->cssID = '';
			if ($this->article_cssID)
			{
				$this->Template->cssID = $this->article_cssID;
			}
			$this->Template->class = $this->article_class;
			$this->Template->style = $this->article_style;
		}
		// headline
		$_headline = StringUtil::deserialize($this->headline);
		if ("" != $_headline['value'])
		{
			$this->Template->hl       = $_headline['unit'];
			$this->Template->headline = $_headline['value'];
		}
	}
}
