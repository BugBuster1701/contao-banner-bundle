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

namespace BugBuster\BannerStatistics;

use BugBuster\Banner\BannerHelper;
use BugBuster\Banner\BannerLog;
use Contao\BackendModule;
use Contao\BackendUser;
use Contao\Database;
use Contao\Input;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\User;

/**
 * Class BotStatisticsHelper
 */
class BannerStatisticsHelper extends BackendModule
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

	/**
	 * Current object instance
	 * @var object
	 */
	protected static $instance;

	protected User $User;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		// $this->import('BackendUser', 'User');
		$this->User = BackendUser::getInstance();
		parent::__construct();
	}

	protected function compile()
	{
	}

	/**
	 * Return the current object instance (Singleton)
	 * @return BannerStatisticsHelper
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get min category id
	 *
	 * @deprecated  why? TODO
	 * @return number CatID    0|min(pid)
	 */
	protected function getCatID()
	{
		$objBannerCatID = Database::getInstance()->prepare("SELECT
                                                                MIN(pid) AS ID
                                                             FROM
                                                                tl_banner")
												  ->execute();
		$objBannerCatID->next();
		if ($objBannerCatID->ID === null)
		{
			return 0;
		}

		return $objBannerCatID->ID;
	}

	/**
	 * Get first category id by arrCategories
	 *
	 * @param  array  $arrBannerCategories
	 * @return number CatID
	 */
	protected function getCatIdByCategories($arrBannerCategories)
	{
		$arrFirstCat = array_shift($arrBannerCategories);

		return $arrFirstCat['id'];
	}

	/**
	 * Get banners by category id
	 *
	 * @param  integer $CatID
	 * @return array
	 */
	protected function getBannersByCatID($CatID = 0)
	{
		$arrBanners = array();

		if ($CatID == -1) // all Categories
		{
			$objBanners = Database::getInstance()
								->prepare("SELECT
                                            tb.id
                                          , tb.banner_type
                                          , tb.banner_name
                                          , tb.banner_url
                                          , tb.banner_jumpTo
                                          , tb.banner_image
                                          , tb.banner_image_extern
                                          , tb.banner_weighting
                                          , tb.banner_start
                                          , tb.banner_stop
                                          , tb.banner_published
                                          , tb.banner_until
                                          , tb.banner_comment
                                          , tb.banner_views_until
                                          , tb.banner_clicks_until
                                          , tb.banner_playerSRC
                                          , tb.banner_posterSRC
                                          , tbs.banner_views
                                          , tbs.banner_clicks
                                       FROM
                                            tl_banner tb
                                          , tl_banner_stat tbs
                                       WHERE
                                            tb.id=tbs.id
                                       ORDER BY
                                            tb.pid
                                          , tb.sorting")
								->execute();
		}
		else
		{
			$objBanners = Database::getInstance()
							->prepare("SELECT
                                            tb.id
                                          , tb.banner_type
                                          , tb.banner_name
                                          , tb.banner_url
                                          , tb.banner_jumpTo
                                          , tb.banner_image
                                          , tb.banner_image_extern
                                          , tb.banner_weighting
                                          , tb.banner_start
                                          , tb.banner_stop
                                          , tb.banner_published
                                          , tb.banner_until
                                          , tb.banner_comment
                                          , tb.banner_views_until
                                          , tb.banner_clicks_until
                                          , tb.banner_playerSRC
                                          , tb.banner_posterSRC
                                          , tbs.banner_views
                                          , tbs.banner_clicks
                                       FROM
                                            tl_banner tb
                                          , tl_banner_stat tbs
                                       WHERE
                                            tb.id=tbs.id
                                       AND
                                            tb.pid =?
                                       ORDER BY
                                            tb.sorting")
							->execute($CatID);
		}
		$intRows = $objBanners->numRows;
		if ($intRows > 0)
		{
			while ($objBanners->next())
			{
				$arrBanners[] = array('id'                   => $objBanners->id, 'banner_type'         => $objBanners->banner_type, 'banner_name'         => $objBanners->banner_name, 'banner_url'          => $objBanners->banner_url, 'banner_jumpTo'       => $objBanners->banner_jumpTo, 'banner_image'        => $objBanners->banner_image, 'banner_image_extern' => $objBanners->banner_image_extern, 'banner_weighting'    => $objBanners->banner_weighting, 'banner_start'        => $objBanners->banner_start, 'banner_stop'         => $objBanners->banner_stop, 'banner_published'    => $objBanners->banner_published, 'banner_until'        => $objBanners->banner_until, 'banner_comment'      => $objBanners->banner_comment, 'banner_views_until'  => $objBanners->banner_views_until, 'banner_clicks_until' => $objBanners->banner_clicks_until, 'banner_views'        => $objBanners->banner_views, 'banner_clicks'       => $objBanners->banner_clicks, 'banner_playerSRC'    => $objBanners->banner_playerSRC, 'banner_posterSRC'    => $objBanners->banner_posterSRC
				);
			} // while
		}

		return $arrBanners;
	} // getBannersByCatID

	/**
	 * Get banner categories
	 *
	 * @deprecated  why? TODO
	 * @param  integer $banner_number
	 * @return array
	 */
	protected function getBannerCategories($banner_number)
	{
		// Kat sammeln
		$objBannerCat = Database::getInstance()
							->prepare("SELECT
                                            id
                                          , title
                                       FROM
                                            tl_banner_category
                                        WHERE
                                            id
                                        IN
                                            (SELECT
                                                 pid
                                             FROM
                                                 tl_banner
                                             LEFT JOIN
                                                 tl_banner_category
                                             ON
                                                 tl_banner.pid = tl_banner_category.id
                                             GROUP BY
                                                 tl_banner.pid
                                             )
                                        ORDER BY
                                            title")
							->execute();

		if ($objBannerCat->numRows > 0)
		{
			if ($banner_number == 0) // gewählte Kategorie hat keine Banner, es gibt aber weitere Kategorien
			{
				$arrBannerCats[] =
					array(
						'id'    => '0',
						'title' => $GLOBALS['TL_LANG']['tl_banner_stat']['select']
					);
				$this->intCatID = 0; // template soll nichts anzeigen
			}
			$arrBannerCats[] =
			array(
				'id'    => '-1',
				'title' => $GLOBALS['TL_LANG']['tl_banner_stat']['allkat']
			);

			while ($objBannerCat->next())
			{
				$arrBannerCats[] =
				array(
					'id'    => $objBannerCat->id,
					'title' => $objBannerCat->title
				);
			}
		}
		else // es gibt keine Kategorie mit Banner
		{
			$arrBannerCats[] =
				array(
					'id'    => '0',
					'title' => '---------'
				);
		}

		return $arrBannerCats;
	} // getBannerCategories

	/**
	 * Get banner categories by usergroups
	 *
	 * @param  array $Usergroups
	 * @return array
	 */
	protected function getBannerCategoriesByUsergroups()
	{
		$arrBannerCats = array();

		$objBannerCat = Database::getInstance()
							->prepare("SELECT
                                            `id`
                                          , `title`
                                          , `banner_stat_protected`
                                          , `banner_stat_groups`
                                       FROM
                                            tl_banner_category
                                        WHERE 1
                                        ORDER BY
                                            title
                                        ")
							->execute();

		while ($objBannerCat->next())
		{
			if (
				true === $this->isUserInBannerStatGroups(
					$objBannerCat->banner_stat_groups,
					(bool) $objBannerCat->banner_stat_protected
				)
			) {
				$arrBannerCats[] =
				array(
					'id'    => $objBannerCat->id,
					'title' => $objBannerCat->title
				);
			}
		}

		if (0 == \count($arrBannerCats))
		{
			$arrBannerCats[] = array('id' => '0', 'title' => '---------');
		}

		return $arrBannerCats;
	}

	/**
	 * Set banner_url
	 *
	 * @param referenz $Banner
	 */
	protected function setBannerURL(&$Banner)
	{
		// Banner Ziel per Page?
		if ($Banner['banner_jumpTo'] > 0)
		{
			// url generieren
			$objBannerNextPage = Database::getInstance()
									->prepare("SELECT
                                                    id
                                                  , alias
                                               FROM
                                                    tl_page
                                               WHERE
                                                    id=?")
									->limit(1)
									->execute($Banner['banner_jumpTo']);
			if ($objBannerNextPage->numRows)
			{
				// old $Banner['banner_url'] = \Controller::generateFrontendUrl($objBannerNextPage->fetchAssoc());
				$objParent = PageModel::findWithDetails($Banner['banner_jumpTo']);
				$Banner['banner_url'] = BannerHelper::frontendUrlGenerator($objBannerNextPage->fetchAssoc(), null, $objParent->language);
				BannerLog::writeLog(__METHOD__, __LINE__, 'banner_url jumpto: ' . $Banner['banner_url']);
			}
		}
		if (empty($Banner['banner_url']))
		{
			$Banner['banner_url'] = $GLOBALS['TL_LANG']['tl_banner_stat']['NoURL'];
			if ($Banner['banner_clicks'] == 0)
			{
				$Banner['banner_clicks'] = '--';
			}
		}
		BannerLog::writeLog(__METHOD__, __LINE__, 'banner_url: ' . $Banner['banner_url']);
	}

	/**
	 * Set banner_published
	 *
	 * @param referenz $Banner
	 */
	protected function setBannerPublishedActive(&$Banner)
	{
		if (
			($Banner['banner_published'] == 1)
		   &&  (empty($Banner['banner_start']) || $Banner['banner_start'] <= time())
		   &&  (empty($Banner['banner_stop'])  || $Banner['banner_stop']   > time())
		) {
			$Banner['banner_active'] = '<span class="banner_stat_yes">' . $GLOBALS['TL_LANG']['tl_banner_stat']['pub_yes'] . '</span>';
			$Banner['banner_published_class'] = 'published';

			if (
				$Banner['banner_until'] == 1
			 && $Banner['banner_views_until'] != ''
			 && $Banner['banner_views'] >= $Banner['banner_views_until']
			) {
				// max views erreicht
				$Banner['banner_active'] = '<span class="banner_stat_no">' . $GLOBALS['TL_LANG']['tl_banner_stat']['pub_no'] . '</span>';
				$Banner['banner_published_class'] = 'unpublished';
			}

			if (
				$Banner['banner_until'] == 1
			 && $Banner['banner_clicks_until'] !=''
			 && $Banner['banner_clicks'] >= $Banner['banner_clicks_until']
			) {
				// max clicks erreicht
				$Banner['banner_active'] = '<span class="banner_stat_no">' . $GLOBALS['TL_LANG']['tl_banner_stat']['pub_no'] . '</span>';
				$Banner['banner_published_class'] = 'unpublished';
			}
		}
		else
		{
			$Banner['banner_active'] = '<span class="banner_stat_no">' . $GLOBALS['TL_LANG']['tl_banner_stat']['pub_no'] . '</span>';
			$Banner['banner_published_class'] = 'unpublished';
		}
	}

	/**
	 * Get status of maxviews and maxclicks
	 *
	 * @param  array $Banner
	 * @return array array(bool $intMaxViews, bool $intMaxClicks)
	 */
	protected function getMaxViewsClicksStatus(&$Banner)
	{
		$intMaxViews = false;
		$intMaxClicks= false;

		if (
			$Banner['banner_until'] == 1
		 && $Banner['banner_views_until'] != ''
		 && $Banner['banner_views'] >= $Banner['banner_views_until']
		) {
			// max views erreicht
			$intMaxViews =  true;
		}

		if (
			$Banner['banner_until'] == 1
		 && $Banner['banner_clicks_until'] !=''
		 && $Banner['banner_clicks'] >= $Banner['banner_clicks_until']
		) {
			// max clicks erreicht
			$intMaxClicks = true;
		}

		return array($intMaxViews, $intMaxClicks);
	}

	/**
	 * Statistic, set on zero
	 */
	protected function setZero()
	{
		// Banner
		$intBID = (int) Input::post('zid', true);
		if ($intBID>0)
		{
			Database::getInstance()->prepare("UPDATE
                                                    tl_banner_stat
                                               SET
                                                    tstamp=?
                                                  , banner_views=0
                                                  , banner_clicks=0
                                               WHERE
                                                    id=?")
									->execute(time(), $intBID);

			return;
		}
		// Category
		$intCatBID = (int) Input::post('catzid', true);
		if ($intCatBID>0)
		{
			Database::getInstance()->prepare("UPDATE
                                                    tl_banner_stat
                                               INNER JOIN
                                                    tl_banner
                                               USING ( id )
                                               SET
                                                    tl_banner_stat.tstamp=?
                                                  , banner_views=0
                                                  , banner_clicks=0
                                               WHERE
                                                    pid=?")
									->execute(time(), $intCatBID);
		}
	}

	/**
	 * Check if User member of group in banner statistik groups
	 *
	 * @param  string $banner_stat_groups DB Field "banner_stat_groups", serialized array
	 * @return bool   true / false
	 */
	protected function isUserInBannerStatGroups($banner_stat_groups, $banner_stat_protected)
	{
		if (true === $this->User->isAdmin)
		{
			// DEBUG log_message('Ich bin Admin', 'banner.log');
			return true; // Admin darf immer
		}
		// wenn  Schutz nicht aktiviert ist, darf jeder
		if (false === $banner_stat_protected)
		{
			// Debug log_message('Schutz nicht aktiviert', 'banner.log');
			return true;
		}
		// Schutz aktiviert, Einschränkungen vorhanden?
		if (0 == \strlen($banner_stat_groups))
		{
			// DEBUG log_message('banner_stat_groups ist leer', 'banner.log');
			return false; // nicht gefiltert, also darf keiner außer Admin
		}

		// mit isMemberOf ermitteln, ob user Member einer der Cat Groups ist
		foreach (StringUtil::deserialize($banner_stat_groups) as $id => $groupid)
		{
			if (true === $this->User->isMemberOf($groupid))
			{
				// DEBUG log_message('Ich bin in der richtigen Gruppe '.$groupid, 'banner.log');
				return true; // User is Member of banner_stat_group
			}
		}

		// Debug log_message('Ich bin in der falschen Gruppe', 'banner.log');
		return false;
	}
} // class
