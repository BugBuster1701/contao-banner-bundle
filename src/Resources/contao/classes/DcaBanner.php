<?php 

/**
 * Contao Open Source CMS, Copyright (C) 2005-2013 Leo Feyer
 *
 * Contao Module "Banner" - DCA Helper Class DcaBanner
 *
 * @copyright  Glen Langer 2012..2015 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Banner
 * @license    LGPL
 * @filesource
 * @see	       https://github.com/BugBuster1701/contao-banner-bundle
 */

/**
 * Run in a custom namespace, so the class can be replaced
 */
namespace BugBuster\Banner;

use BugBuster\Banner\BannerImage;
use Psr\Log\LogLevel;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\StringUtil;
use Contao\Image;

class DcaBanner extends \Backend
{
    /**
     * Banner intern
     * @var string
     */
    const BANNER_TYPE_INTERN = 'banner_image';
    
    /**
     * Banner extern
     * @var string
     */
    const BANNER_TYPE_EXTERN = 'banner_image_extern';
    
    /**
     * Banner text
     * @var string
     */
    const BANNER_TYPE_TEXT   = 'banner_text';
    
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
        $catId = $add['id'];
        unset($add['id']); //delete the helper
        
        $sql = 'SELECT CAST(`banner_published` AS UNSIGNED INTEGER) AS published
                	,count(id) AS numbers 
                FROM `tl_banner` 
                WHERE `pid`=?
                GROUP BY 1';
        $objNumbers = \Database::getInstance()->prepare($sql)->execute($catId);
        if ($objNumbers->numRows == 0)
        {
            return $add;
        }
        $published     = 0;
        $not_published = 0;
        while ($objNumbers->next())
        {
            if ($objNumbers->published == 0) 
            {
            	$not_published = $objNumbers->numbers;
            }
            if ($objNumbers->published == 1)
            {
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
        switch ($row['banner_type'])
        {
            case self::BANNER_TYPE_INTERN :
                return $this->listBannerInternal($row);
                break;
            case self::BANNER_TYPE_EXTERN :
                return $this->listBannerExternal($row);
                break;
            case self::BANNER_TYPE_TEXT :
                return $this->listBannerText($row);
                break;
            default :
                return false;
                break;
        }
    }
    
    /**
     * List internal banner record
     *
     * @param object $row
     * @return string	record as html
     */
    protected function listBannerInternal($row)
    {
        if ($row['banner_image'] == '')
        {
            return '<p class="error">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_read_error'].' (1)</p>';
        }
        //convert DB file ID into file path ($objFile->path)
        $objFile = \FilesModel::findByUuid($row['banner_image']);
        if ($objFile === null)
        {
            // Check for version 3 format
            if (!\Validator::isUuid($row['banner_image']))
            {
                return '<p class="error">'.$GLOBALS['TL_LANG']['ERR']['version2format'].'</p>';
            }
            return '<p class="error">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_read_error'].' (2)</p>';
        }
    
        //get image size
        $arrImageSize = $this->BannerImage->getBannerImageSize($objFile->path, self::BANNER_TYPE_INTERN);
    
        //resize if necessary
        $arrImageSizeNew = array();
        switch ($arrImageSize[2])
        {
            case 1: // GIF
            case 2: // JPG
            case 3: // PNG
                $arrImageSizeNew = $this->BannerImage->getCheckBannerImageSize($arrImageSize,250,200);
                $intWidth  = $arrImageSizeNew[0];
                $intHeight = $arrImageSizeNew[1];
                $oriSize   = $arrImageSizeNew[2];
                if ($oriSize || $arrImageSize[2] == 1) // GIF)
                {
                    $banner_image = $this->urlEncode($objFile->path);
                }
                else
                {
                    $banner_image = \Image::get($this->urlEncode($objFile->path), $intWidth, $intHeight);
                }
                break;
            default:
                break;
        }
    
        //Banner Ziel per Page?
        if ($row['banner_jumpTo'] >0)
        {
            //url generieren
            $objBannerNextPage = \Database::getInstance()->prepare("SELECT id, alias FROM tl_page WHERE id=?")
                                                ->limit(1)
                                                ->execute($row['banner_jumpTo']);
            if ($objBannerNextPage->numRows)
            {
                $row['banner_url'] = \Controller::generateFrontendUrl($objBannerNextPage->fetchAssoc());
            }
        }
        $banner_url = ampersand(\Idna::decode($row['banner_url']));
        $banner_url_text = $GLOBALS['TL_LANG']['tl_banner']['banner_url'][0].': ';
        
        if ( strlen($banner_url) <1 && $row['banner_jumpTo'] <1 )
        {
            //weder externe URL noch interne Seite definiert
            $banner_url = $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined'];
        }
        if ( strlen($banner_url) <1 && $row['banner_jumpTo'] >0 )
        {
            //externe Seite definiert die aber nicht mehr existiert ($banner_url<1)
            $banner_url = '<span class="tl_gerror">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_page_not_found'].'</span>';
        }
    
        //Output
        switch ($arrImageSize[2])
        {
            case 1: // GIF
            case 2: // JPG
            case 3: // PNG
                $output = '<div class="mod_banner_be">' .
                        '<div class="name"><img alt="'.\StringUtil::specialchars(ampersand($row['banner_name'])).'" src="'. $banner_image .'" height="'.$intHeight.'" width="'.$intWidth.'" /></div>' .
                        '<div class="right">' .
                        '<div class="left">'.
                        '<div class="published_head">'.$GLOBALS['TL_LANG']['tl_banner']['banner_published'][0].'</div>'.
                        '<div class="published_data">'.($row['banner_published'] =='' ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_no'] : $GLOBALS['TL_LANG']['tl_banner']['tl_be_yes']).' </div>'.
                        '</div>'.
                        '<div class="left">' .
                        '<div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['banner_type'][0].'</div>' .
                        '<div class="date_data">'.$GLOBALS['TL_LANG']['tl_banner']['source_intern'] .'</div>' .
                        '</div>' .
                        '<div style="clear:both;"></div>'.
                        '<div class="left">' .
                        '<div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_start'].'</div>' .
                        '<div class="date_data">' . ($row['banner_start']=='' ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_start'] : date($GLOBALS['TL_CONFIG']['datimFormat'], $row['banner_start'])) . '</div>' .
                        '</div>' .
                        '<div class="left">' .
                        '<div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_stop'].'</div>' .
                        '<div class="date_data">' . ($row['banner_stop'] =='' ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_stop'] : date($GLOBALS['TL_CONFIG']['datimFormat'], $row['banner_stop'])) . '</div>' .
                        '</div>' .
                        '<div style="clear:both;"></div>'.
                        '<div class="left">' .
                        '<div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_max_views'].'</div>' .
                        '<div class="date_data">' . ($row['banner_views_until']=='' ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_max'] : $row['banner_views_until']) . '</div>' .
                        '</div>' .
                        '<div class="left">' .
                        '<div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_max_clicks'].'</div>' .
                        '<div class="date_data">' . ($row['banner_clicks_until'] =='' ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_max'] : $row['banner_clicks_until']) . '</div>' .
                        '</div>' .
                        '<div style="clear:both;"></div>'.
                        '</div>' .
                        '<div class="url">'.$banner_url_text . (strlen($banner_url)<80 ? $banner_url : substr($banner_url, 0, 36)."[...]".substr($banner_url,-36,36) ).'</div>' .
                        '</div>';
                break;
            default:
                break;
        }//switch
    
        if ($arrImageSize === false)
        {
            //Interne Banner Grafik
            $output = '<div class="mod_banner_be">' .
                    '<div class="name"><span style="color:red;">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_read_error'].'</span><br />'.$this->urlEncode($objFile->path).'</div>' .
                    '<div class="right">' .
                    '<div class="left">'.
                    '<div class="published_head">'.$GLOBALS['TL_LANG']['tl_banner']['banner_published'][0].'</div>'.
                    '<div class="published_data">'.($row['banner_published'] =='' ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_no'] : $GLOBALS['TL_LANG']['tl_banner']['tl_be_yes']).' </div>'.
                    '</div>'.
                    '<div class="left">' .
                    '<div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['banner_type'][0].'</div>' .
                    '<div class="date_data">'.$GLOBALS['TL_LANG']['tl_banner']['source_intern'] .'</div>' .
                    '</div>' .
                    '<div style="clear:both;"></div>'.
                    '<div class="left">' .
                    '<div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_start'].'</div>' .
                    '<div class="date_data">' . ($row['banner_start']=='' ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_start'] : date($GLOBALS['TL_CONFIG']['datimFormat'], $row['banner_start'])) . '</div>' .
                    '</div>' .
                    '<div class="left">' .
                    '<div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_stop'].'</div>' .
                    '<div class="date_data">' . ($row['banner_stop'] =='' ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_stop'] : date($GLOBALS['TL_CONFIG']['datimFormat'], $row['banner_stop'])) . '</div>' .
                    '</div>' .
                    '<div style="clear:both;"></div>'.
                    '<div class="left">' .
                    '<div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_max_views'].'</div>' .
                    '<div class="date_data">' . ($row['banner_views_until']=='' ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_max'] : $row['banner_views_until']) . '</div>' .
                    '</div>' .
                    '<div class="left">' .
                    '<div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_max_clicks'].'</div>' .
                    '<div class="date_data">' . ($row['banner_clicks_until'] =='' ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_max'] : $row['banner_clicks_until']) . '</div>' .
                    '</div>' .
                    '<div style="clear:both;"></div>'.
                    '</div>' .
                    '<div class="url">'.$banner_url_text . (strlen($banner_url)<80 ? $banner_url : substr($banner_url, 0, 36)."[...]".substr($banner_url,-36,36) ).'</div>' .
                    '</div>';
        }
    
        $key = $row['banner_published'] ? 'published' : 'unpublished';
        $style = 'style="font-size:11px;margin-bottom:10px;"';
        $output_h = '<div class="cte_type ' . $key . '" ' . $style . '><strong>' . \StringUtil::specialchars(ampersand($row['banner_name'])) . '</strong></div>';
        return $output_h . $output;
    }
    
    /**
     * List external banner record
     *
     * @param object $row
     * @return string	record as html
     */
    protected function listBannerExternal($row)
    {
        $arrImageSize = $this->BannerImage->getBannerImageSize($row['banner_image_extern'], self::BANNER_TYPE_EXTERN);
    
        //resize if necessary
        $arrImageSizeNew = array();
        switch ($arrImageSize[2])
        {
            case 1: // GIF
            case 2: // JPG
            case 3: // PNG
                $arrImageSizeNew = $this->BannerImage->getCheckBannerImageSize($arrImageSize,250,200);
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
        if ($row['banner_jumpTo'] >0)
        {
            //url generieren
            $objBannerNextPage = \Database::getInstance()->prepare("SELECT id, alias FROM tl_page WHERE id=?")
                                                        ->limit(1)
                                                        ->execute($row['banner_jumpTo']);
            if ($objBannerNextPage->numRows)
            {
                $row['banner_url'] = \Controller::generateFrontendUrl($objBannerNextPage->fetchAssoc());
            }
        }
        $banner_url = ampersand(\Idna::decode($row['banner_url']));
        if (strlen($banner_url)>0)
        {
            $banner_url_text = $GLOBALS['TL_LANG']['tl_banner']['banner_url'][0].': ';
        }
        else
        {
            $banner_url_text = '';
        }
    
        //Output
        switch ($arrImageSize[2])
        {
            case 1: // GIF
            case 2: // JPG
            case 3: // PNG
                $output = '<div class="mod_banner_be">' .
                        '<div class="name"><img alt="'.\StringUtil::specialchars(ampersand($row['banner_name'])).'" src="'. $banner_image .'" height="'.$intHeight.'" width="'.$intWidth.'" /></div>' .
                        '<div class="right">' .
                        '<div class="left">'.
                        '<div class="published_head">'.$GLOBALS['TL_LANG']['tl_banner']['banner_published'][0].'</div>'.
                        '<div class="published_data">'.($row['banner_published'] =='' ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_no'] : $GLOBALS['TL_LANG']['tl_banner']['tl_be_yes']).' </div>'.
                        '</div>'.
                        '<div class="left">' .
                        '<div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['banner_type'][0].'</div>' .
                        '<div class="date_data">'.$GLOBALS['TL_LANG']['tl_banner']['source_extern'] .'</div>' .
                        '</div>' .
                        '<div style="clear:both;"></div>'.
                        '<div class="left">' .
                        '<div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_start'].'</div>' .
                        '<div class="date_data">' . ($row['banner_start']=='' ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_start'] : date($GLOBALS['TL_CONFIG']['datimFormat'], $row['banner_start'])) . '</div>' .
                        '</div>' .
                        '<div class="left">' .
                        '<div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_stop'].'</div>' .
                        '<div class="date_data">' . ($row['banner_stop'] =='' ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_stop'] : date($GLOBALS['TL_CONFIG']['datimFormat'], $row['banner_stop'])) . '</div>' .
                        '</div>' .
                        '<div style="clear:both;"></div>'.
                        '<div class="left">' .
                        '<div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_max_views'].'</div>' .
                        '<div class="date_data">' . ($row['banner_views_until']=='' ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_max'] : $row['banner_views_until']) . '</div>' .
                        '</div>' .
                        '<div class="left">' .
                        '<div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_max_clicks'].'</div>' .
                        '<div class="date_data">' . ($row['banner_clicks_until'] =='' ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_max'] : $row['banner_clicks_until']) . '</div>' .
                        '</div>' .
                        '<div style="clear:both;"></div>'.
                        '</div>' .
                        '<div class="url">'.$banner_url_text . (strlen($banner_url)<80 ? $banner_url : substr($banner_url, 0, 36)."[...]".substr($banner_url,-36,36) ).'</div>' .
                        '</div>';
                break;
            default:
                break;
        }//switch
    
        if ($arrImageSize === false)
        {
            //Externe Banner Grafik
            $output = '<div class="mod_banner_be">' .
                    '<div class="name"><span style="color:red;">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_read_error'].'</span><br />'.$banner_image.'</div>' .
                    '<div class="right">' .
                    '<div class="left">'.
                    '<div class="published_head">'.$GLOBALS['TL_LANG']['tl_banner']['banner_published'][0].'</div>'.
                    '<div class="published_data">'.($row['banner_published'] =='' ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_no'] : $GLOBALS['TL_LANG']['tl_banner']['tl_be_yes']).' </div>'.
                    '</div>'.
                    '<div class="left">' .
                    '<div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['banner_type'][0].'</div>' .
                    '<div class="date_data">'.$GLOBALS['TL_LANG']['tl_banner']['source_intern'] .'</div>' .
                    '</div>' .
                    '<div style="clear:both;"></div>'.
                    '<div class="left">' .
                    '<div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_start'].'</div>' .
                    '<div class="date_data">' . ($row['banner_start']=='' ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_start'] : date($GLOBALS['TL_CONFIG']['datimFormat'], $row['banner_start'])) . '</div>' .
                    '</div>' .
                    '<div class="left">' .
                    '<div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_stop'].'</div>' .
                    '<div class="date_data">' . ($row['banner_stop'] =='' ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_stop'] : date($GLOBALS['TL_CONFIG']['datimFormat'], $row['banner_stop'])) . '</div>' .
                    '</div>' .
                    '<div style="clear:both;"></div>'.
                    '<div class="left">' .
                    '<div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_max_views'].'</div>' .
                    '<div class="date_data">' . ($row['banner_views_until']=='' ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_max'] : $row['banner_views_until']) . '</div>' .
                    '</div>' .
                    '<div class="left">' .
                    '<div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_max_clicks'].'</div>' .
                    '<div class="date_data">' . ($row['banner_clicks_until'] =='' ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_max'] : $row['banner_clicks_until']) . '</div>' .
                    '</div>' .
                    '<div style="clear:both;"></div>'.
                    '</div>' .
                    '<div class="url">'.$banner_url_text . (strlen($banner_url)<80 ? $banner_url : substr($banner_url, 0, 36)."[...]".substr($banner_url,-36,36) ).'</div>' .
                    '</div>';
        }
    
        $key = $row['banner_published'] ? 'published' : 'unpublished';
        $style = 'style="font-size:11px;margin-bottom:10px;"';
        $output_h = '<div class="cte_type ' . $key . '" ' . $style . '><strong>' . \StringUtil::specialchars(ampersand($row['banner_name'])) . '</strong></div>';
        return $output_h . $output;
    }
    
    /**
     * List text banner record
     *
     * @param object $row
     * @return string	record as html
     */
    protected function listBannerText($row)
    {
        //Banner Ziel per Page?
        if ($row['banner_jumpTo'] >0)
        {
            //url generieren
            $objBannerNextPage = \Database::getInstance()->prepare("SELECT id, alias FROM tl_page WHERE id=?")
                                                ->limit(1)
                                                ->execute($row['banner_jumpTo']);
            if ($objBannerNextPage->numRows)
            {
                $row['banner_url'] = \Controller::generateFrontendUrl($objBannerNextPage->fetchAssoc());
            }
        }
        
        $banner_url = ampersand(\Idna::decode($row['banner_url']));
        if (strlen($banner_url)>0)
        {
            $banner_url_text = $GLOBALS['TL_LANG']['tl_banner']['banner_url'][0].': ';
        }
        else
        {
            $banner_url_text = '';
        }
        //Output
        $output = '<div class="mod_banner_be">' .
                '<div class="name"><br />'.$row['banner_name'].'<br /><span style="font-weight:normal;">'.nl2br($row['banner_comment']).'<br /><br />'.$banner_url_text .(strlen($banner_url)<60 ? $banner_url : substr($banner_url, 0, 31)."[...]".substr($banner_url,-21,21) ).'</span></div>' .
                '<div class="right">' .
                '<div class="left">'.
                '<div class="published_head">'.$GLOBALS['TL_LANG']['tl_banner']['banner_published'][0].'</div>'.
                '<div class="published_data">'.($row['banner_published'] =='' ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_no'] : $GLOBALS['TL_LANG']['tl_banner']['tl_be_yes']).' </div>'.
                '</div>'.
                '<div class="left">' .
                '<div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['banner_type'][0].'</div>' .
                '<div class="date_data">'.$GLOBALS['TL_LANG']['tl_banner_type']['banner_text'].'</div>' .
                '</div>' .
                '<div style="clear:both;"></div>'.
                '<div class="left">' .
                '<div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_start'].'</div>' .
                '<div class="date_data">' . ($row['banner_start']=='' ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_start'] : date($GLOBALS['TL_CONFIG']['datimFormat'], $row['banner_start'])) . '</div>' .
                '</div>' .
                '<div class="left">' .
                '<div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_stop'].'</div>' .
                '<div class="date_data">' . ($row['banner_stop'] =='' ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_stop'] : date($GLOBALS['TL_CONFIG']['datimFormat'], $row['banner_stop'])) . '</div>' .
                '</div>' .
                '<div style="clear:both;"></div>'.
                '<div class="left">' .
                '<div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_max_views'].'</div>' .
                '<div class="date_data">' . ($row['banner_views_until']=='' ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_max'] : $row['banner_views_until']) . '</div>' .
                '</div>' .
                '<div class="left">' .
                '<div class="date_head">'.$GLOBALS['TL_LANG']['tl_banner']['tl_be_max_clicks'].'</div>' .
                '<div class="date_data">' . ($row['banner_clicks_until'] =='' ? $GLOBALS['TL_LANG']['tl_banner']['tl_be_not_defined_max'] : $row['banner_clicks_until']) . '</div>' .
                '</div>' .
                '<div style="clear:both;"></div>'.
                '</div>' .
                '</div>';
    
        $key = $row['banner_published'] ? 'published' : 'unpublished';
        $style = 'style="font-size:11px;margin-bottom:10px;"';
        $output_h = '<div class="cte_type ' . $key . '" ' . $style . '><strong>' . \StringUtil::specialchars(ampersand($row['banner_name'])) . '</strong></div>';
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
        if (strlen(\Input::get('tid')))
        {
            $this->toggleVisibility(\Input::get('tid'), (\Input::get('state') == 1));
            $this->redirect($this->getReferer());
        }
    
        // Check permissions AFTER checking the tid, so hacking attempts are logged
        if (!$this->User->isAdmin && !$this->User->hasAccess('tl_banner::banner_published', 'alexf'))
        {
            return '';
        }
    
        $href .= '&amp;tid='.$row['id'].'&amp;state='. ($row['banner_published'] ? '' : 1);
    
        if (!$row['banner_published'])
        {
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
        if (!$this->User->isAdmin && !$this->User->hasAccess('tl_banner::banner_published', 'alexf'))
        {
            \System::getContainer()
                ->get('monolog.logger.contao')
                ->log(LogLevel::ERROR,
                        'Not enough permissions to publish/unpublish Banner ID "'.$intId.'"',
                        array('contao' => new ContaoContext('tl_banner toggleVisibility', TL_ERROR)));
            
            $this->redirect('contao/main.php?act=error');
        }
    
        // Update database
        \Database::getInstance()->prepare("UPDATE 
                                                tl_banner 
                                           SET 
                                                banner_published='" . ($blnVisible ? 1 : '') . "' 
                                           WHERE 
                                                id=?")
                                ->execute($intId);
    }
}
