<?php

/**
 * Contao Open Source CMS, Copyright (C) 2005-2022 Leo Feyer
 *
 * Contao Module "Banner" - DCA Helper Class DcaBanner
 *
 * @copyright  Glen Langer 2012..2022 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @license    LGPL
 * @filesource
 * @see	       https://github.com/BugBuster1701/contao-banner-bundle
 */

/**
 * Run in a custom namespace, so the class can be replaced
 */

namespace BugBuster\Banner;

use BugBuster\Banner\BannerHelper;
use BugBuster\Banner\BannerImage;
use BugBuster\Banner\BannerLog;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\File;
use Contao\FilesModel;
use Contao\Image;
use Contao\StringUtil;
use Imagine\Exception\RuntimeException;
use Psr\Log\LogLevel;

class DcaBanner extends \Contao\Backend
{
    /**
     * Banner intern
     * @var string
     */
    public const BANNER_TYPE_INTERN = 'banner_image';

    /**
     * Banner extern
     * @var string
     */
    public const BANNER_TYPE_EXTERN = 'banner_image_extern';

    /**
     * Banner text
     * @var string
     */
    public const BANNER_TYPE_TEXT   = 'banner_text';

    protected $BannerImage;

    /**
     * Import the back end user object
     * and the BannerImage object
     */
    public function __construct()
    {
        parent::__construct();
        $this->import('BackendUser', 'User');
        $this->BannerImage = new BannerImage();
    }

    /**
     * Add Header Rows, call from header_callback
     */
    public function addHeader($add, $dca)
    {
        //Get Debug Settings
        $objBannerHelper = new BannerHelper();
        $objBannerHelper->setDebugSettings($add['id']);
        BannerLog::writeLog(__METHOD__, __LINE__, '###');
        $catId = $add['id'];
        unset($add['id']); //delete the helper

        $sql = 'SELECT CAST(`banner_published` AS UNSIGNED INTEGER) AS published
                	,count(id) AS numbers 
                FROM `tl_banner` 
                WHERE `pid`=?
                GROUP BY 1';
        $objNumbers = \Contao\Database::getInstance()->prepare($sql)->execute($catId);
        if ($objNumbers->numRows == 0) {
            return $add;
        }
        $published     = 0;
        $not_published = 0;
        while ($objNumbers->next()) {
            if ($objNumbers->published == 0) {
                $not_published = $objNumbers->numbers;
            }
            if ($objNumbers->published == 1) {
                $published = $objNumbers->numbers;
            }
        }

        $add[$GLOBALS['TL_LANG']['tl_banner']['banner_number_of']] = $published." "
                        . $GLOBALS['TL_LANG']['tl_banner']['banner_active']
                        . " / "
                        . $not_published." "
                        . $GLOBALS['TL_LANG']['tl_banner']['banner_inactive'];

        return $add;
    }

    /**
     * List banner record
     *
     * @param object $row
     */
    public function listBanner($row)
    {
        switch ($row['banner_type']) {
            case self::BANNER_TYPE_INTERN:
                return $this->listBannerInternal($row);
                break;
            case self::BANNER_TYPE_EXTERN:
                return $this->listBannerExternal($row);
                break;
            case self::BANNER_TYPE_TEXT:
                return $this->listBannerText($row);
                break;
            case BannerVideo::BANNER_TYPE_VIDEO:
                return $this->listBannerVideo($row);
                break;
            default:
                return false;
                break;
        }
    }

    /**
     * List internal banner record
     *
     * @param  object $row
     * @return string record as html
     */
    protected function listBannerInternal($row)
    {
        if (empty($row['banner_image'])) {
            return '<p class="error">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_read_error'].' (1)</p>';
        }
        //convert DB file ID into file path ($objFile->path)
        $objFile = \Contao\FilesModel::findByUuid($row['banner_image']);
        if ($objFile === null) {
            // Check for version 3 format
            if (!\Contao\Validator::isUuid($row['banner_image'])) {
                return '<p class="error">'.$GLOBALS['TL_LANG']['ERR']['version2format'].'</p>';
            }

            return '<p class="error">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_read_error'].' (2)</p>';
        }

        //get image size
        $arrImageSize = $this->BannerImage->getBannerImageSize($objFile->path, self::BANNER_TYPE_INTERN);

        //resize if necessary
        $arrImageSizeNew = [];
        switch ($arrImageSize[2]) {
            case 1: // GIF
            case 2: // JPG/JPEG
            case 3: // PNG
            case 18: // WEBP
                $arrImageSizeNew = $this->BannerImage->getCheckBannerImageSize($arrImageSize, 250, 200);
                $intWidth  = $arrImageSizeNew[0];
                $intHeight = $arrImageSizeNew[1];
                $oriSize   = $arrImageSizeNew[2];
                if ($oriSize || $arrImageSize[2] == 1) { // GIF)
                    $banner_image = \Contao\System::urlEncode($objFile->path);
                } else {
                    $container = \Contao\System::getContainer();
                    $rootDir = $container->getParameter('kernel.project_dir');
                    $banner_image = $container
                                        ->get('contao.image.image_factory')
                                        ->create($rootDir.'/'.$objFile->path, [$intWidth, $intHeight, 'proportional'])
                                        ->getUrl($rootDir);
                }
                break;
            default:
                break;
        }

        //Banner Ziel per Page?
        if ($row['banner_jumpTo'] >0) {
            //url generieren
            $objBannerNextPage = \Contao\Database::getInstance()->prepare("SELECT id, alias FROM tl_page WHERE id=?")
                                                ->limit(1)
                                                ->execute($row['banner_jumpTo']);
            if ($objBannerNextPage->numRows) {
                //old $row['banner_url'] = \Controller::generateFrontendUrl($objBannerNextPage->fetchAssoc());
                $objParent = \Contao\PageModel::findWithDetails($row['banner_jumpTo']);
                $row['banner_url'] = BannerHelper::frontendUrlGenerator($objBannerNextPage->fetchAssoc(), null, $objParent->language);
                BannerLog::writeLog(__METHOD__, __LINE__, 'banner_url: ' . $row['banner_url']);
            }
        }
        $banner_url = \Contao\StringUtil::ampersand(BannerHelper::decodePunycode($row['banner_url']));
        $banner_url = preg_replace('/^app_dev\.php\//', '', $banner_url);
        $banner_url_text = $GLOBALS['TL_LANG']['tl_banner']['banner_url'][0].': ';
        BannerLog::writeLog(__METHOD__, __LINE__, 'banner_url: ' . $banner_url);

        if (\strlen($banner_url) <1 && $row['banner_jumpTo'] <1) {
            //weder externe URL noch interne Seite definiert
            $banner_url = $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined'];
        }
        if (\strlen($banner_url) <1 && $row['banner_jumpTo'] >0) {
            //externe Seite definiert die aber nicht mehr existiert ($banner_url<1)
            $banner_url = '<span class="tl_gerror">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_page_not_found'].'</span>';
        }

        //Output
        switch ($arrImageSize[2]) {
            case 1: // GIF
            case 2: // JPG / JPEG
            case 3: // PNG
            case 18: // WEBP
                $output = '<div class="mod_banner_be">
                    <div class="name">
                        <img alt="'.\Contao\StringUtil::specialchars(\Contao\StringUtil::ampersand($row['banner_name'])).'" src="'. $banner_image .'" height="'.$intHeight.'" width="'.$intWidth.'">
                    </div>';
                break;
            default:
                break;
        }//switch

        if ($arrImageSize === false) {
            //Interne Banner Grafik, Bannerdatei nicht gefunden oder Lesefehler
            $output = '<div class="mod_banner_be">
                <div class="name">
                    <span style="color:red;">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_read_error'].'</span><br>'.$this->urlEncode($objFile->path).'
                </div>';
        }
        $output .= '
            <div class="right">
                <div class="left">
                    <div class="published_head">'.$GLOBALS['TL_LANG']['tl_banner']['banner_published'][0].'</div>
                    <div class="published_data">'.(empty($row['banner_published']) ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_no'] : $GLOBALS['TL_LANG']['tl_banner']['tl_be_yes']).' </div>
                </div>
                <div class="left">
                    <div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['banner_type'][0].'</div>
                    <div class="date_data">'.$GLOBALS['TL_LANG']['tl_banner']['source_intern'] .'</div>
                </div>
                <div style="clear:both;"></div>
                <div class="left">
                    <div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_start'].'</div>
                    <div class="date_data">' . (empty($row['banner_start']) ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_start'] : date($GLOBALS['TL_CONFIG']['datimFormat'], $row['banner_start'])) . '</div>
                </div>
                <div class="left">
                    <div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_stop'].'</div>
                    <div class="date_data">' . (empty($row['banner_stop']) ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_stop'] : date($GLOBALS['TL_CONFIG']['datimFormat'], $row['banner_stop'])) . '</div>
                </div>
                <div style="clear:both;"></div>
                <div class="left">
                    <div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_max_views'].'</div>
                    <div class="date_data">' . (empty($row['banner_views_until']) ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_max'] : $row['banner_views_until']) . '</div>
                </div>
                <div class="left">
                    <div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_max_clicks'].'</div>
                    <div class="date_data">' . (empty($row['banner_clicks_until']) ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_max'] : $row['banner_clicks_until']) . '</div>
                </div>
                <div style="clear:both;"></div>
                <div class="left">
                    <div class="meta_head">'.$GLOBALS['TL_LANG']['tl_banner']['using_meta_data'].'</div>
                    <div class="meta_data">' . (empty($row['banner_overwritemeta']) ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_no'] : $GLOBALS['TL_LANG']['tl_banner']['tl_be_yes']) . '</div>
                </div>
                <div style="clear:both;"></div>
            </div>
            <div class="url name">'.$banner_url_text . '<span style="font-weight:normal;">' . (\strlen($banner_url)<80 ? $banner_url : substr($banner_url, 0, 36)."[...]".substr($banner_url, -36, 36)).'</span></div>
        </div>';

        $key = $row['banner_published'] ? 'published' : 'unpublished';
        $style = 'class="tl_label"';
        $output_h = '<div class="cte_type ' . $key . '"><span ' . $style . '>' . \Contao\StringUtil::specialchars(\Contao\StringUtil::ampersand($row['banner_name'])) . '</span></div>';

        return $output_h . $output;
    }

    /**
     * List external banner record
     *
     * @param  object $row
     * @return string record as html
     */
    protected function listBannerExternal($row)
    {
        $arrImageSize = $this->BannerImage->getBannerImageSize($row['banner_image_extern'], self::BANNER_TYPE_EXTERN);

        //resize if necessary
        $arrImageSizeNew = [];
        switch ($arrImageSize[2]) {
            case 1: // GIF
            case 2: // JPG
            case 3: // PNG
            case 18: // WEBP
                $arrImageSizeNew = $this->BannerImage->getCheckBannerImageSize($arrImageSize, 250, 200);
                $intWidth  = $arrImageSizeNew[0];
                $intHeight = $arrImageSizeNew[1];
                $oriSize   = $arrImageSizeNew[2];
                break;
            default:
                break;
        }
        unset($oriSize);

        $banner_image = $row['banner_image_extern'];

        //Banner Ziel per Page?
        if ($row['banner_jumpTo'] >0) {
            //url generieren
            $objBannerNextPage = \Contao\Database::getInstance()->prepare("SELECT id, alias FROM tl_page WHERE id=?")
                                                        ->limit(1)
                                                        ->execute($row['banner_jumpTo']);
            if ($objBannerNextPage->numRows) {
                //old $row['banner_url'] = \Controller::generateFrontendUrl($objBannerNextPage->fetchAssoc());
                $objParent = \Contao\PageModel::findWithDetails($row['banner_jumpTo']);
                $row['banner_url'] = BannerHelper::frontendUrlGenerator($objBannerNextPage->fetchAssoc(), null, $objParent->language);
                BannerLog::writeLog(__METHOD__, __LINE__, 'banner_url: ' . $row['banner_url']);
            }
        }
        $banner_url = \Contao\StringUtil::ampersand(BannerHelper::decodePunycode($row['banner_url']));
        if (\strlen($banner_url)>0) {
            $banner_url_text = $GLOBALS['TL_LANG']['tl_banner']['banner_url'][0].': ';
        } else {
            $banner_url_text = '';
        }

        //Output
        switch ($arrImageSize[2]) {
            case 1: // GIF
            case 2: // JPG
            case 3: // PNG
            case 18: // WEBP
                $output = '<div class="mod_banner_be">
                    <div class="name">
                        <img alt="'.\Contao\StringUtil::specialchars(\Contao\StringUtil::ampersand($row['banner_name'])).'" src="'. $banner_image .'" height="'.$intHeight.'" width="'.$intWidth.'">
                    </div>';
                break;
            default:
                break;
        }//switch

        if ($arrImageSize === false) {
            //Externe Banner Grafik, Bannerdatei nicht gefunden oder Lesefehler
            $output = '<div class="mod_banner_be">
                <div class="name">
                    <span style="color:red;">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_read_error'].'</span><br>'.$banner_image.'
                </div>';
        }
        $output .= '
            <div class="right">
                <div class="left">
                    <div class="published_head">'.$GLOBALS['TL_LANG']['tl_banner']['banner_published'][0].'</div>
                    <div class="published_data">'.(empty($row['banner_published']) ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_no'] : $GLOBALS['TL_LANG']['tl_banner']['tl_be_yes']).' </div>
                </div>
                <div class="left">
                    <div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['banner_type'][0].'</div>
                    <div class="date_data">'.$GLOBALS['TL_LANG']['tl_banner']['source_extern'] .'</div>
                </div>
                <div style="clear:both;"></div>
                <div class="left">
                    <div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_start'].'</div>
                    <div class="date_data">' . (empty($row['banner_start']) ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_start'] : date($GLOBALS['TL_CONFIG']['datimFormat'], $row['banner_start'])) . '</div>
                </div>
                <div class="left">
                    <div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_stop'].'</div>
                    <div class="date_data">' . (empty($row['banner_stop']) ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_stop'] : date($GLOBALS['TL_CONFIG']['datimFormat'], $row['banner_stop'])) . '</div>
                </div>
                <div style="clear:both;"></div>
                <div class="left">
                    <div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_max_views'].'</div>
                    <div class="date_data">' . (empty($row['banner_views_until']) ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_max'] : $row['banner_views_until']) . '</div>
                </div>
                <div class="left">
                    <div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_max_clicks'].'</div>
                    <div class="date_data">' . (empty($row['banner_clicks_until']) ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_max'] : $row['banner_clicks_until']) . '</div>
                </div>
                <div style="clear:both;"></div>
            </div>
            <div class="url name">'.$banner_url_text . '<span style="font-weight:normal;">' . (\strlen($banner_url)<80 ? $banner_url : substr($banner_url, 0, 36)."[...]".substr($banner_url, -36, 36)).'</span></div>
        </div>';

        $key = $row['banner_published'] ? 'published' : 'unpublished';
        $style = 'class="tl_label"';
        $output_h = '<div class="cte_type ' . $key . '"><span ' . $style . '>' . \Contao\StringUtil::specialchars(\Contao\StringUtil::ampersand($row['banner_name'])) . '</span></div>';

        return $output_h . $output;
    }

    /**
     * List text banner record
     *
     * @param  object $row
     * @return string record as html
     */
    protected function listBannerText($row)
    {
        //Banner Ziel per Page?
        if ($row['banner_jumpTo'] >0) {
            //url generieren
            $objBannerNextPage = \Contao\Database::getInstance()->prepare("SELECT id, alias FROM tl_page WHERE id=?")
                                                ->limit(1)
                                                ->execute($row['banner_jumpTo']);
            if ($objBannerNextPage->numRows) {
                //old $row['banner_url'] = \Controller::generateFrontendUrl($objBannerNextPage->fetchAssoc());
                $objParent = \Contao\PageModel::findWithDetails($row['banner_jumpTo']);
                $row['banner_url'] = BannerHelper::frontendUrlGenerator($objBannerNextPage->fetchAssoc(), null, $objParent->language);
                BannerLog::writeLog(__METHOD__, __LINE__, 'banner_url: ' . $row['banner_url']);
            }
        }

        $banner_url = \Contao\StringUtil::ampersand(BannerHelper::decodePunycode($row['banner_url']));
        if (\strlen($banner_url)>0) {
            $banner_url_text = $GLOBALS['TL_LANG']['tl_banner']['banner_url'][0].': ';
        } else {
            $banner_url_text = '';
        }
        //Output
        $output = '<div class="mod_banner_be">
            <div class="name"><br>'.$row['banner_name'].'<br>
                <span style="font-weight:normal;">'.nl2br($row['banner_comment']).'</span>
                <br><br>'.$banner_url_text . '<span style="font-weight:normal;">' . (\strlen($banner_url)<60 ? $banner_url : substr($banner_url, 0, 31)."[...]".substr($banner_url, -21, 21)).'</span>
            </div>
            <div class="right">
                <div class="left">
                    <div class="published_head">'.$GLOBALS['TL_LANG']['tl_banner']['banner_published'][0].'</div>
                    <div class="published_data">'.(empty($row['banner_published']) ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_no'] : $GLOBALS['TL_LANG']['tl_banner']['tl_be_yes']).' </div>
                </div>
                <div class="left">
                    <div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['banner_type'][0].'</div>
                    <div class="date_data">'.$GLOBALS['TL_LANG']['tl_banner_type']['banner_text'].'</div>
                </div>
                <div style="clear:both;"></div>
                <div class="left">
                    <div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_start'].'</div>
                    <div class="date_data">' . (empty($row['banner_start']) ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_start'] : date($GLOBALS['TL_CONFIG']['datimFormat'], $row['banner_start'])) . '</div>
                </div>
                <div class="left">
                    <div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_stop'].'</div>
                    <div class="date_data">' . (empty($row['banner_stop']) ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_stop'] : date($GLOBALS['TL_CONFIG']['datimFormat'], $row['banner_stop'])) . '</div>
                </div>
                <div style="clear:both;"></div>
                <div class="left">
                    <div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_max_views'].'</div>
                    <div class="date_data">' . (empty($row['banner_views_until']) ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_max'] : $row['banner_views_until']) . '</div>
                </div>
                <div class="left">
                    <div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_max_clicks'].'</div>
                    <div class="date_data">' . (empty($row['banner_clicks_until']) ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_max'] : $row['banner_clicks_until']) . '</div>
                </div>
                <div style="clear:both;"></div>
            </div>
        </div>';

        $key = $row['banner_published'] ? 'published' : 'unpublished';
        $style = 'class="tl_label"';
        $output_h = '<div class="cte_type ' . $key . '" ' . $style . '><span ' . $style . '>' . \Contao\StringUtil::specialchars(\Contao\StringUtil::ampersand($row['banner_name'])) . '</span></div>';

        return $output_h . $output;
    }

    /**
     * List video banner record
     *
     * @param  object $row
     * @return string record as html
     */
    protected function listBannerVideo($row)
    {
        //Banner Ziel per Page?
        if ($row['banner_jumpTo'] >0) {
            //url generieren
            $objBannerNextPage = \Contao\Database::getInstance()->prepare("SELECT id, alias FROM tl_page WHERE id=?")
                ->limit(1)
                ->execute($row['banner_jumpTo']);
            if ($objBannerNextPage->numRows) {
                //old $row['banner_url'] = \Controller::generateFrontendUrl($objBannerNextPage->fetchAssoc());
                $objParent = \Contao\PageModel::findWithDetails($row['banner_jumpTo']);
                $row['banner_url'] = BannerHelper::frontendUrlGenerator($objBannerNextPage->fetchAssoc(), null, $objParent->language);
                BannerLog::writeLog(__METHOD__, __LINE__, 'banner_url: ' . $row['banner_url']);
            }
        }

        $banner_url = \Contao\StringUtil::ampersand(BannerHelper::decodePunycode($row['banner_url']));
        if (\strlen($banner_url)>0) {
            $banner_url_text = $GLOBALS['TL_LANG']['tl_banner']['banner_url'][0].': ';
        } else {
            $banner_url_text = '';
        }

        $objFiles = FilesModel::findMultipleByUuidsAndExtensions(
            StringUtil::deserialize($row['banner_playerSRC'], true),
            ['mp4', 'm4v', 'mov', 'wmv', 'webm', 'ogv']
        );
        $filelist = '<ul>';

        while ($objFiles && $objFiles->next()) {
            $objFile = new File($objFiles->path);
            $filelist .= '<li>' . Image::getHtml($objFile->icon, '', 'class="mime_icon"') . ' <span style="font-weight:normal;">' . $objFile->name . '</span> <span class="size" style="font-weight:normal;">(' . $this->getReadableSize($objFile->size) . ')</span></li>';
        }

        $filelist .= '</ul>';

        //Poster
        $thumbnail = '';
        if ($row['banner_posterSRC'] && ($objFileThumb = FilesModel::findByUuid($row['banner_posterSRC'])) !== null) {
            try {
                $thumbnail = $GLOBALS['TL_LANG']['tl_banner']['banner_posterSRC']['0'] .':<br>';
                $thumbnailPath = $objFileThumb->path;
                $rootDir = \Contao\System::getContainer()->getParameter('kernel.project_dir');
                $thumbnail .= Image::getHtml(
                    \Contao\System::getContainer()
                        ->get('contao.image.image_factory') //4.13 contao.image.factory
                        ->create(
                            $rootDir . '/' . $thumbnailPath,
                            (new \Contao\Image\ResizeConfiguration())
                                ->setWidth(120)
                                ->setHeight(120)
                                ->setMode(\Contao\Image\ResizeConfiguration::MODE_BOX)
                                ->setZoomLevel(100)
                        )
                        ->getUrl($rootDir),
                    'poster-image',
                    'class="poster-image"'
                );
                $thumbnail .= '<br><br>';
            } catch (RuntimeException $e) {
                $thumbnail = '<br><p class="preview-image broken-image">Broken poster image!</p><br>';
            }
        }

        //Output
        $output = '<div class="mod_banner_be">
            <div class="name video">
                ' . $thumbnail . '
                '.$GLOBALS['TL_LANG']['tl_banner']['banner_playerSRC']['0'].':
                <br>'.$filelist.'
                <br>'.$GLOBALS['TL_LANG']['tl_banner']['banner_comment']['0'] . ': <span style="font-weight:normal;">'.nl2br($row['banner_comment']).'</span>
                <br>'.$banner_url_text . '<span style="font-weight:normal;">' . (\strlen($banner_url)<60 ? $banner_url : substr($banner_url, 0, 31)."[...]".substr($banner_url, -21, 21)).'</span>
            </div>
            <div class="right">
                <div class="left">
                    <div class="published_head">'.$GLOBALS['TL_LANG']['tl_banner']['banner_published'][0].'</div>
                    <div class="published_data">'.(empty($row['banner_published']) ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_no'] : $GLOBALS['TL_LANG']['tl_banner']['tl_be_yes']).' </div>
                </div>
                <div class="left">
                    <div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['banner_type'][0].'</div>
                    <div class="date_data">'.$GLOBALS['TL_LANG']['tl_banner']['source_intern'].'</div>
                </div>
                <div style="clear:both;"></div>
                <div class="left">
                    <div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_start'].'</div>
                    <div class="date_data">' . (empty($row['banner_start']) ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_start'] : date($GLOBALS['TL_CONFIG']['datimFormat'], $row['banner_start'])) . '</div>
                </div>
                <div class="left">
                    <div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_stop'].'</div>
                    <div class="date_data">' . (empty($row['banner_stop']) ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_stop'] : date($GLOBALS['TL_CONFIG']['datimFormat'], $row['banner_stop'])) . '</div>
                </div>
                <div style="clear:both;"></div>
                <div class="left">
                    <div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_max_views'].'</div>
                    <div class="date_data">' . (empty($row['banner_views_until']) ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_max'] : $row['banner_views_until']) . '</div>
                </div>
                <div class="left">
                    <div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_max_clicks'].'</div>
                    <div class="date_data">' . (empty($row['banner_clicks_until']) ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_max'] : $row['banner_clicks_until']) . '</div>
                </div>
                <div style="clear:both;"></div>
                <div class="left">
                    <div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['banner_playerStart']['0'].'</div>
                    <div class="date_data">' . (int) $row['banner_playerStart']. '</div>
                </div>
                <div class="left">
                    <div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['banner_playerStop']['0'].'</div>
                    <div class="date_data">' . ((int) $row['banner_playerStop'] == 0 ? '' : (int) $row['banner_playerStop']) . '</div>
                </div>
                <div style="clear:both;"></div>
            </div>
        </div>';

        $key = $row['banner_published'] ? 'published' : 'unpublished';
        $style = 'class="tl_label"';
        $output_h = '<div class="cte_type ' . $key . '" ' . $style . '><span ' . $style . '>' . \Contao\StringUtil::specialchars(\Contao\StringUtil::ampersand($row['banner_name'])) . '</span></div>';

        return $output_h . $output;
    }

    /**
     * Return the "toggle visibility" button
     * @param array
     * @param string
     * @param string
     * @param string
     * @param string
     * @param string
     * @return string
     */
    public function toggleIcon($row, $href, $label, $title, $icon, $attributes)
    {
        if (\strlen(\Contao\Input::get('tid'))) {
            $this->toggleVisibility(\Contao\Input::get('tid'), (\Contao\Input::get('state') == 1));
            $this->redirect($this->getReferer());
        }

        // Check permissions AFTER checking the tid, so hacking attempts are logged
        if (!$this->User->isAdmin && !$this->User->hasAccess('tl_banner::banner_published', 'alexf')) {
            return '';
        }

        $href .= '&amp;tid='.$row['id'].'&amp;state='. ($row['banner_published'] ? '' : 1);

        if (!$row['banner_published']) {
            $icon = 'invisible.svg';
        }

        return '<a href="'.$this->addToUrl($href).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label, 'data-state="' . ($row['banner_published'] ? 1 : 0) . '"').'</a> ';
    }

    /**
     * Disable/enable banner
     * @param integer
     * @param boolean
     */
    public function toggleVisibility($intId, $blnVisible)
    {
        // Check permissions to publish
        if (!$this->User->isAdmin && !$this->User->hasAccess('tl_banner::banner_published', 'alexf')) {
            \Contao\System::getContainer()
                ->get('monolog.logger.contao')
                ->log(
                    LogLevel::ERROR,
                    'Not enough permissions to publish/unpublish Banner ID "'.$intId.'"',
                    ['contao' => new ContaoContext('tl_banner toggleVisibility', TL_ERROR)]
                );

            $this->redirect('contao/main.php?act=error');
        }

        // Update database
        \Contao\Database::getInstance()->prepare("UPDATE 
                                                tl_banner 
                                           SET 
                                                banner_published='" . ($blnVisible ? 1 : '') . "' 
                                           WHERE 
                                                id=?")
                                ->execute($intId);
    }

    public function fieldLabelCallback($dc)
    {
        if (!$this->supportsWebp()) {
            \Contao\System::loadLanguageFile('tl_banner_category');
            $GLOBALS['TL_LANG']['tl_banner']['banner_image'][1] .= ' (' . $GLOBALS['TL_LANG']['tl_banner_category']['formatsWebpNotSupported'] .')';
        }

        return  '';
    }

    /**
     * Check if WEBP is supported
     *
     * @return boolean
     */
    private function supportsWebp()
    {
        $imagine = \Contao\System::getContainer()->get('contao.image.imagine');
        $imagineclass = \get_class($imagine);

        if ($imagineclass == "Imagine\\Imagick\\Imagine") {
            return \in_array('WEBP', \Imagick::queryFormats('WEBP'), true);
        }

        if ($imagineclass == "Imagine\\Gmagick\\Imagine") {
            return \in_array('WEBP', (new \Gmagick())->queryformats('WEBP'), true);
        }

        if ($imagineclass == "Imagine\\Gd\\Imagine") {
            return \function_exists('imagewebp');
        }

        return false;
    }
}
