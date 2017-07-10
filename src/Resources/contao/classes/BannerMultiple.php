<?php
/**
 * Extension for Contao Open Source CMS, Copyright (C) 2005-2017 Leo Feyer
 *
 * BannerMultiple - Frontend Helper Class
 *
 * @copyright  Glen Langer 2017 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @licence    LGPL
 * @filesource
 * @package    Banner
 * @see	       https://github.com/BugBuster1701/contao-banner-bundle
 */
namespace BugBuster\Banner;

use BugBuster\Banner\BannerImage;
use BugBuster\Banner\BannerLog;

/**
 * Class BannerMultiple
 *
 * @copyright  Glen Langer 2017 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Banner
 * @license    LGPL
 */
class BannerMultiple extends \Frontend
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
    
    protected $arrCategoryValues  = array(); 
    protected $arrAllBannersBasic = array();
    protected $BannerImage;
    
    

    function __construct ($arrCategoryValues, $arrAllBannersBasic)
    {
        $this->arrCategoryValues = $arrCategoryValues;
        $this->arrAllBannersBasic = $arrAllBannersBasic;
    }
    
    protected function getMultiBanner()
    {
        /* $this->arrCategoryValues[...]
         * banner_random
         * banner_limit         - 0 all, other:max
         */
         
        reset($this->arrAllBannersBasic); //sicher ist sicher
    
        //RandomBlocker entfernen falls möglich und nötig
         
        // einer muss mindestens übrig bleiben
        if ( count($this->arrAllBannersBasic) >1
            // bei Alle Banner anzeigen (0) nichts entfernen
            && $this->arrCategoryValues['banner_limit'] >0
            // nur wenn mehr Banner übrig als per limit festgelegt
            && ( count($this->arrAllBannersBasic) > $this->arrCategoryValues['banner_limit'] )
            )
        {
            $intRandomBlockerID = $this->getRandomBlockerId();
            if (isset($this->arrAllBannersBasic[$intRandomBlockerID]))
            {
                unset($this->arrAllBannersBasic[$intRandomBlockerID]);
            }
        }
         
        if ( $this->arrCategoryValues['banner_random'] == 1 )
        {
            $this->shuffleAssoc($this->arrAllBannersBasic);
        }
         
        //wenn limit gesetzt, array arrAllBannersBasic dezimieren
        if ( $this->arrCategoryValues['banner_limit'] >0 )
        {
            $del = count($this->arrAllBannersBasic) - $this->arrCategoryValues['banner_limit'];
            for ($i = 0; $i < $del; $i++)
            {
                array_pop($this->arrAllBannersBasic);
            }
        }
    
        //Rest soll nun angezeigt werden.
        //Schleife
        while ( list($banner_id, $banner_weigth) = each($this->arrAllBannersBasic) ) // each deprecated PHP 7.2
        {
            unset($banner_weigth);
            $objBanners  = \Database::getInstance()
                                ->prepare("SELECT
                                                TLB.*
                                           FROM
                                	            tl_banner AS TLB
                                           WHERE
                                                TLB.`id`=?"
                                         )
                                ->limit(1)
                                ->execute( $banner_id );
                $intRows = $objBanners->numRows;
                //Banner vorhanden?
                if($intRows > 0)
                {
                    $arrBanners = array();
                    $objBanners->next();
                    self::$arrBannerSeen[] = $objBanners->id;
                    //CSS-ID/Klasse(n) je Banner, für den wrapper
                    $banner_cssID   = '';
                    $banner_class   = '';
                    $banner_classes = '';
                    $_cssID = deserialize($objBanners->banner_cssid);
                    if ( is_array($_cssID) )
                    {
                        if ($_cssID[0] != '')
                        {
                            $banner_cssID   = ' id="banner_'.$_cssID[0].'"';
                        }
                        if ($_cssID[1] != '')
                        {
                            $banner_classes = explode(" ", $_cssID[1]);
                            foreach ($banner_classes as $banner_classone)
                            {
                                $banner_class .= ' banner_'.$banner_classone;
                            }
                        }
                    }
    
                    if (!$this->statusRandomBlocker)
                    {
                        //Random Blocker setzen für den ersten Banner
                        $this->setRandomBlockerId($banner_id);
                    }
                     
                    switch ($objBanners->banner_type)
                    {
                        case self::BANNER_TYPE_INTERN :
                            //Pfad+Dateiname holen ueber UUID (findByPk leitet um auf findByUuid)
                            $objFile = \FilesModel::findByPk($objBanners->banner_image);
                            //BannerImage Class
                            $this->BannerImage = new BannerImage();
                            //Banner Art und Größe bestimmen
                            $arrImageSize = $this->BannerImage->getBannerImageSize($objFile->path, self::BANNER_TYPE_INTERN);
                            //Falls Datei gelöscht wurde, Abbruch
                            if (false === $arrImageSize)
                            {
                                $arrImageSize[2] = 0;
                                BannerLog::log('Banner Image with ID "'.$objBanners->id.'" not found', 'BannerHelper getMultiBanner', TL_ERROR);
                                break;
                            }
                            //Banner Neue Größe 0:$Width 1:$Height
                            $arrNewSizeValues = deserialize($objBanners->banner_imgSize);
                            //Banner Neue Größe ermitteln, return array $Width,$Height,$oriSize
                            $arrImageSizenNew = $this->BannerImage->getBannerImageSizeNew($arrImageSize[0],$arrImageSize[1],$arrNewSizeValues[0],$arrNewSizeValues[1]);
    
                            //wenn oriSize = true, oder bei GIF/SWF/SWC = original Pfad nehmen
                            if ($arrImageSizenNew[2] === true
                                || $arrImageSize[2] == 1  // GIF
                                || $arrImageSize[2] == 4  // SWF
                                || $arrImageSize[2] == 13 // SWC
                                )
                            {
                                $FileSrc = $objFile->path;
                                $arrImageSize[0] = $arrImageSizenNew[0];
                                $arrImageSize[1] = $arrImageSizenNew[1];
                                $arrImageSize[3] = ' height="'.$arrImageSizenNew[1].'" width="'.$arrImageSizenNew[0].'"';
                                 
                                //fake the Picture::create
                                $picture['img']   = array
                                (
                                    'src'    => \StringUtil::specialchars(ampersand($FileSrc)),
                                    'width'  => $arrImageSizenNew[0],
                                    'height' => $arrImageSizenNew[1],
                                    'srcset' => \StringUtil::specialchars(ampersand($FileSrc))
                                );
                                $picture['alt']   = \StringUtil::specialchars(ampersand($objBanners->banner_name));
                                $picture['title'] = \StringUtil::specialchars(ampersand($objBanners->banner_comment));
                                 
                                BannerLog::writeLog(__METHOD__ , __LINE__ , 'Orisize Picture: '. print_r($picture,true));
                            }
                            else
                            {
                                $FileSrc = \Image::get($this->urlEncode($objFile->path), $arrImageSizenNew[0], $arrImageSizenNew[1],'proportional');
    
                                $picture = \Picture::create($this->urlEncode($objFile->path), array($arrImageSizenNew[0], $arrImageSizenNew[1], $arrNewSizeValues[2]))->getTemplateData();
                                $picture['alt']   = \StringUtil::specialchars(ampersand($objBanners->banner_name));
                                $picture['title'] = \StringUtil::specialchars(ampersand($objBanners->banner_comment));
                                 
                                BannerLog::writeLog(__METHOD__ , __LINE__ , 'Resize Picture: '. print_r($picture,true));
    
                                $arrImageSize[0] = $arrImageSizenNew[0];
                                $arrImageSize[1] = $arrImageSizenNew[1];
                                $arrImageSize[3] = ' height="'.$arrImageSizenNew[1].'" width="'.$arrImageSizenNew[0].'"';
                            }
                            break;
                        case self::BANNER_TYPE_EXTERN :
                            //BannerImage Class
                            $this->BannerImage = new BannerImage();
                            //Banner Art und Größe bestimmen
                            $arrImageSize = $this->BannerImage->getBannerImageSize($objBanners->banner_image_extern, self::BANNER_TYPE_EXTERN);
                            //Falls Datei gelöscht wurde, Abbruch
                            if (false === $arrImageSize)
                            {
                                $arrImageSize[2] = 0;
                                BannerLog::log('Banner Image with ID "'.$objBanners->id.'" not found', 'BannerHelper getMultiBanner', TL_ERROR);
                                break;
                            }
                            //Banner Neue Größe 0:$Width 1:$Height
                            $arrNewSizeValues = deserialize($objBanners->banner_imgSize);
                            //Banner Neue Größe ermitteln, return array $Width,$Height,$oriSize
                            $arrImageSizenNew = $this->BannerImage->getBannerImageSizeNew($arrImageSize[0],$arrImageSize[1],$arrNewSizeValues[0],$arrNewSizeValues[1]);
                            //Umwandlung bei Parametern
                            $FileSrc = html_entity_decode($objBanners->banner_image_extern, ENT_NOQUOTES, 'UTF-8');
                             
                            //fake the Picture::create
                            $picture['img']   = array
                            (
                                'src'    => \StringUtil::specialchars(ampersand($FileSrc)),
                                'width'  => $arrImageSizenNew[0],
                                'height' => $arrImageSizenNew[1],
                                'srcset' => \StringUtil::specialchars(ampersand($FileSrc))
                            );
                            $picture['alt']   = \StringUtil::specialchars(ampersand($objBanners->banner_name));
                            $picture['title'] = \StringUtil::specialchars(ampersand($objBanners->banner_comment));
                             
                            $arrImageSize[0] = $arrImageSizenNew[0];
                            $arrImageSize[1] = $arrImageSizenNew[1];
                            $arrImageSize[3] = ' height="'.$arrImageSizenNew[1].'" width="'.$arrImageSizenNew[0].'"';
                            break;
                        case self::BANNER_TYPE_TEXT :
                            $arrImageSize = false;
                            break;
                    }
    
                    if ($arrImageSize !== false) //Bilder extern/intern
                    {
                        $banner_target = ($objBanners->banner_target == '1') ? '' : ' target="_blank"';
    
                        if ( strlen($objBanners->banner_comment) > 1 )
                        {
                            $banner_comment_pos = strpos($objBanners->banner_comment,"\n",1);
                            if ($banner_comment_pos !== false)
                            {
                                $objBanners->banner_comment = substr($objBanners->banner_comment,0,$banner_comment_pos);
                            }
                        }
    
                        // Banner Seite als Ziel?
                        if ($objBanners->banner_jumpTo > 0)
                        {
                            $objBanners->banner_url = ''; //default
                            $domain = \Environment::get('base');
                            $objParent = \PageModel::findWithDetails($objBanners->banner_jumpTo);
                            if ($objParent !== null)
                            {
                                if ($objParent->domain != '')
                                {
                                    $domain = (\Environment::get('ssl') ? 'https://' : 'http://') . $objParent->domain . TL_PATH . '/';
                                }
                                $objBanners->banner_url = $domain . $this->generateFrontendUrl($objParent->row(), '', $objParent->language);
                            }
                        }
                         
                        //$arrImageSize[0]  eigene Breite
                        //$arrImageSize[1]  eigene Höhe
                        //$arrImageSize[3]  Breite und Höhe in der Form height="yyy" width="xxx"
                        //$arrImageSize[2]
                        // 1 = GIF, 2 = JPG, 3 = PNG
                        // 4 = SWF, 13 = SWC (zip-like swf file)
                        // 5 = PSD, 6 = BMP, 7 = TIFF(intel byte order), 8 = TIFF(motorola byte order)
                        // 9 = JPC, 10 = JP2, 11 = JPX, 12 = JB2, 13 = SWC, 14 = IFF
                        switch ($arrImageSize[2])
                        {
                            case 1: // GIF
                            case 2: // JPG
                            case 3: // PNG
                                $arrBanners[] = array
                                (
                                'banner_key'     => 'bid=',
                                'banner_wrap_id'    => $banner_cssID,
                                'banner_wrap_class' => $banner_class,
                                'banner_id'      => $objBanners->id,
                                'banner_name'    => \StringUtil::specialchars(ampersand($objBanners->banner_name)),
                                'banner_url'     => $objBanners->banner_url,
                                'banner_target'  => $banner_target,
                                'banner_comment' => \StringUtil::specialchars(ampersand($objBanners->banner_comment)),
                                'src'            => \StringUtil::specialchars(ampersand($FileSrc)),//specialchars(ampersand($this->urlEncode($FileSrc))),
                                'alt'            => \StringUtil::specialchars(ampersand($objBanners->banner_name)),
                                'size'           => $arrImageSize[3],
                                'banner_pic'     => true,
                                'banner_flash'   => false,
                                'banner_text'    => false,
                                'banner_empty'   => false,
                                'picture'        => $picture
                                );
                                $picture = null; unset($picture);
                                break;
                            default:
                                $arrBanners[] = array
                                (
                                'banner_key'     => 'bid=',
                                'banner_wrap_id'    => $banner_cssID,
                                'banner_wrap_class' => $banner_class,
                                'banner_id'      => 0,
                                'banner_name'    => '',
                                'banner_url'     => '',
                                'banner_target'  => '',
                                'banner_comment' => '',
                                'src'            => '',
                                'alt'            => '',
                                'size'           => '',
                                'banner_pic'     => true,
                                );
                                break;
                        }//switch
                        $arrResults[] = $arrBanners[0];
                         
                        $this->arrBannerData = $arrBanners; //wird von setStatViewUpdate genutzt
                        $this->setStatViewUpdate();
                    }//$arrImageSize !== false
    
                    // Text Banner
                    if ($objBanners->banner_type == 'banner_text')
                    {
                        $banner_target = ($objBanners->banner_target == '1') ? '' : ' target="_blank"';
    
                        // Banner Seite als Ziel?
                        if ($objBanners->banner_jumpTo > 0)
                        {
                            $domain = \Environment::get('base');
                            $objParent = \PageModel::findWithDetails($objBanners->banner_jumpTo);
                            if ($objParent !== null) // is null when page not exist anymore
                            {
                                if ($objParent->domain != '')
                                {
                                    $domain = (\Environment::get('ssl') ? 'https://' : 'http://') . $objParent->domain . TL_PATH . '/';
                                }
                                $objBanners->banner_url = $domain . $this->generateFrontendUrl($objParent->row(), '', $objParent->language);
                            }
                        }
    
                        // Kurz URL (nur Domain)
                        $treffer = parse_url(\Idna::decode($objBanners->banner_url)); // #79
                        $banner_url_kurz = $treffer['host'];
                        if (isset($treffer['port']))
                        {
                            $banner_url_kurz .= ':'.$treffer['port'];
                        }
    
                        $arrBanners[] = array
                        (
                            'banner_key'     => 'bid=',
                            'banner_wrap_id'    => $banner_cssID,
                            'banner_wrap_class' => $banner_class,
                            'banner_id'      => $objBanners->id,
                            'banner_name'    => \StringUtil::specialchars(ampersand($objBanners->banner_name)),
                            'banner_url'     => $objBanners->banner_url,
                            'banner_url_kurz'=> $banner_url_kurz,
                            'banner_target'  => $banner_target,
                            'banner_comment' => ampersand(nl2br($objBanners->banner_comment)),
                            'banner_pic'     => false,
                            'banner_flash'   => false,
                            'banner_text'    => true,
                            'banner_empty'   => false	// issues 733
                        );
                         
                        $arrResults[] = $arrBanners[0];
                         
                        $this->arrBannerData = $arrBanners; //wird von setStatViewUpdate genutzt
                        $this->setStatViewUpdate();
                         
                    }//text banner
                     
                }//Banner vorhanden
        } // while each($this->arrAllBannersBasic)
         
        //anderes Template?
        if (($this->banner_template != $this->strTemplate)
         && ($this->banner_template != '')
           )
        {
            $this->strTemplate = $this->banner_template;
            $this->Template = new \FrontendTemplate($this->strTemplate);
        }
         
        //falls $arrImageSize = false  und kein Text Banner ist es ein leeres array
        $this->Template->banners = $arrResults;
    }
    
    /**
     * shuffle for associative arrays, preserves key=>value pairs.
     * http://www.php.net/manual/de/function.shuffle.php
     */
    protected function shuffleAssoc(&$array)
    {
        $keys = array_keys($array);
        shuffle($keys);
        shuffle($keys);
    
        foreach($keys as $key)
        {
            $new[$key] = $array[$key];
            unset($array[$key]); /* save memory */
        }
        $array = $new;
    
        return true;
    }
}

