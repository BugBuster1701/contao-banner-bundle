<?php
/**
 * Extension for Contao Open Source CMS, Copyright (C) 2005-2017 Leo Feyer
 *
 * BannerSingle - Frontend Helper Class
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
use BugBuster\Banner\BannerCount;

/**
 * Class BannerSingle
 *
 * @copyright  Glen Langer 2017 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Banner
 * @license    LGPL
 */
class BannerSingle extends \Frontend
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

    protected $arrCategoryValues = array();
    protected $BannerImage;
    protected $banner_template;
    protected $strTemplate;
    protected $Template;
    protected $arrAllBannersBasic;

    function __construct ($arrCategoryValues, $banner_template, $strTemplate, $objTemplate, $arrAllBannersBasic)
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
     * @return  $this->strTemplate
     */
    public function getDefaultBanner()
    {
        $arrImageSize = array();
        //CSS-ID/Klasse(n) je Banner, für den wrapper
        $banner_cssID   = '';
        $banner_class   = ' banner_default';
    
        //BannerDefault gewünscht und vorhanden?
        if ( $this->arrCategoryValues['banner_default'] == '1'
            && strlen($this->arrCategoryValues['banner_default_image']) > 0
            )
        {
            //Template setzen
            if ( ($this->banner_template != $this->strTemplate)
              && ($this->banner_template != '') )
            {
                $this->strTemplate = $this->banner_template;
                $this->Template = new \FrontendTemplate($this->strTemplate);
            }

            $banner_default_target = ($this->arrCategoryValues['banner_default_target'] == '1') ? '' : ' target="_blank"';

            //BannerImage Class
            $this->BannerImage = new BannerImage();
    
            //Banner Art bestimmen
            $arrImageSize = $this->BannerImage->getBannerImageSize($this->arrCategoryValues['banner_default_image'], self::BANNER_TYPE_INTERN);
            // 1 = GIF, 2 = JPG, 3 = PNG
            // 4 = SWF, 13 = SWC (zip-like swf file)
            // 5 = PSD, 6 = BMP, 7 = TIFF(intel byte order), 8 = TIFF(motorola byte order)
            // 9 = JPC, 10 = JP2, 11 = JPX, 12 = JB2, 13 = SWC, 14 = IFF
    
            //fake the Picture::create
            $picture['img']   = array
            (
                'src'    => $this->urlEncode($this->arrCategoryValues['banner_default_image']),
                'width'  => $arrImageSize[0],
                'height' => $arrImageSize[1],
                'srcset' => $this->urlEncode($this->arrCategoryValues['banner_default_image'])
            );
            $picture['alt']   = specialchars(ampersand($this->arrCategoryValues['banner_default_name']));
            $picture['title'] = '';
            	
            BannerLog::writeLog(__METHOD__ , __LINE__ , 'Fake Picture: '. print_r($picture,true));
            	
            switch ($arrImageSize[2])
            {
                case 1:// GIF
                case 2:// JPG
                case 3:// PNG
                    $arrBanners[] = array
                                    (
                                    'banner_key'     => 'defbid=',
                                    'banner_wrap_id'    => $banner_cssID,
                                    'banner_wrap_class' => $banner_class,
                                    'banner_id'      => $this->arrCategoryValues['id'],
                                    'banner_name'    => specialchars(ampersand($this->arrCategoryValues['banner_default_name'])),
                                    'banner_url'     => $this->arrCategoryValues['banner_default_url'],
                                    'banner_target'  => $banner_default_target,
                                    'banner_comment' => specialchars(ampersand($this->arrCategoryValues['banner_default_name'])),
                                    'src'            => $this->urlEncode(      $this->arrCategoryValues['banner_default_image']),
                                    'alt'            => specialchars(ampersand($this->arrCategoryValues['banner_default_name'])),
                                    'size'     		 => '',
                                    'banner_pic'     => true,
                                    'banner_flash'   => false,
                                    'banner_text'    => false,
                                    'banner_empty'   => false,	// issues 733
                                    'picture'        => $picture
                                    );
                    break;
            }
            $arrResults[] = $arrBanners[0];
            $this->Template->banners = $arrResults;
            return $this->Template;
        }
        //Kein BannerDefault
        $NoBannerFound = ($GLOBALS['TL_LANG']['MSC']['tl_banner']['noBanner']) ? $GLOBALS['TL_LANG']['MSC']['tl_banner']['noBanner'] : 'no banner, no default banner';
        $arrBanners[] = array
                        (
                            'banner_key'  => 'bid=',
                            'banner_wrap_id'    => $banner_cssID,
                            'banner_wrap_class' => $banner_class,
                            'banner_id'   => 0,
                            'banner_name' => specialchars(ampersand($NoBannerFound)),
                            'banner_url'  => '',
                            'banner_target'  => '',
                            'banner_comment' => '',
                            'src' 			=> '',
                            'alt' 			=> '',
                            'size'     		=> '',
                            'banner_pic' 	=> false,
                            'banner_flash'  => false,
                            'banner_text'   => false,
                            'banner_empty'  => true	// issues 733
                        );
        $arrResults[] = $arrBanners[0];
        //Ausblenden wenn leer?
        if ($this->banner_hideempty == 1)
        {
            // auf Leer umschalten
            $this->strTemplate='mod_banner_empty';
            $this->Template = new \FrontendTemplate($this->strTemplate);
        }
        $this->Template->banners = $arrResults;
        return $this->Template;
    }
    
    /**
     * Get First View Banner
     *
     * @return internal    Value in $this->strTemplate
     */
    public function getSingleBannerFirst($module_id)
    {
        $arrBanners = array();
        $arrResults = array();
        $FileSrc = '';
         
        //first aktiv banner in category
        //$this->arrAllBannersBasic den ersten Datensatz über die ID nutzen
        //seltsamerweise kommt reference Fehler bei Kombination in einer Anweisung, daher getrennt
        $banner_keys = array_keys($this->arrAllBannersBasic);
        $banner_id   = array_shift($banner_keys);
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
                $objBanners->next();
                BannerHelper::$arrBannerSeen[] = $objBanners->id; 
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
                            BannerLog::log('Banner Image with ID "'.$objBanners->id.'" not found', 'BannerHelper getSingleBannerFirst', TL_ERROR);
                            break;
                        }
                        //Banner Neue Größe 0:$Width 1:$Height 2:resize mode
                        $arrNewSizeValues = deserialize($objBanners->banner_imgSize);
                        //Banner Neue Größe ermitteln, return array $Width,$Height,$oriSize
                        $arrImageSizenNew = $this->BannerImage->getBannerImageSizeNew($arrImageSize[0],$arrImageSize[1],$arrNewSizeValues[0],$arrNewSizeValues[1]);
    
                        //wenn oriSize = true, oder bei GIF/SWF/SWC = original Pfad nehmen
                        if ($arrImageSizenNew[2] === true //oriSize
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
                                'src'    => specialchars(ampersand($FileSrc)),
                                'width'  => $arrImageSizenNew[0],
                                'height' => $arrImageSizenNew[1],
                                'srcset' => specialchars(ampersand($FileSrc))
                            );
                            $picture['alt']   = specialchars(ampersand($objBanners->banner_name));
                            $picture['title'] = specialchars(ampersand($objBanners->banner_comment));
    
                            BannerLog::writeLog(__METHOD__ , __LINE__ , 'Orisize Picture: '. print_r($picture,true));
                        }
                        else
                        {
                            //Resize an image and store the resized version in the assets/images folder
                            //return The path of the resized image or null
                            $FileSrc = \Image::get($this->urlEncode($objFile->path), $arrImageSizenNew[0], $arrImageSizenNew[1],'proportional');
    
                            $picture = \Picture::create($this->urlEncode($objFile->path), array($arrImageSizenNew[0], $arrImageSizenNew[1], $arrNewSizeValues[2]))->getTemplateData();
                            $picture['alt']   = specialchars(ampersand($objBanners->banner_name));
                            $picture['title'] = specialchars(ampersand($objBanners->banner_comment));
    
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
                            BannerLog::log('Banner Image with ID "'.$objBanners->id.'" not found', 'BannerHelper getSingleBannerFirst', TL_ERROR);
                            break;
                        }
                        //Banner Neue Größe 0:$Width 1:$Height
                        $arrNewSizeValues = deserialize($objBanners->banner_imgSize);
                        //Banner Neue Größe ermitteln, return array $Width,$Height,$oriSize
                        $arrImageSizenNew = $this->BannerImage->getBannerImageSizeNew($arrImageSize[0],$arrImageSize[1],$arrNewSizeValues[0],$arrNewSizeValues[1]);
                        //Umwandlung bei Parametern
                        $FileSrc = html_entity_decode($objBanners->banner_image_extern, ENT_NOQUOTES, 'UTF-8');
    
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
                        case 1:// GIF
                        case 2:// JPG
                        case 3:// PNG
                            $arrBanners[] = array
                                            (
                                            'banner_key'     => 'bid=',
                                            'banner_wrap_id'    => $banner_cssID,
                                            'banner_wrap_class' => $banner_class,
                                            'banner_id'      => $objBanners->id,
                                            'banner_name'    => specialchars(ampersand($objBanners->banner_name)),
                                            'banner_url'     => $objBanners->banner_url,
                                            'banner_target'  => $banner_target,
                                            'banner_comment' => specialchars(ampersand($objBanners->banner_comment)),
                                            'src'            => specialchars(ampersand($FileSrc)),//specialchars(ampersand($this->urlEncode($FileSrc))),
                                            'alt'            => specialchars(ampersand($objBanners->banner_name)),
                                            'size'           => $arrImageSize[3],
                                            'banner_pic'     => true,
                                            'banner_flash'   => false,
                                            'banner_text'    => false,
                                            'banner_empty'   => false,
                                            'picture'        => $picture
                                            );
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
    
                    //anderes Template?
                    if (($this->banner_template != $this->strTemplate)
                     && ($this->banner_template != '')
                       )
                    {
                        $this->strTemplate = $this->banner_template;
                        $this->Template = new \FrontendTemplate($this->strTemplate);
                    }
                    $this->setStatViewUpdate($arrBanners, $module_id, $objBanners->banner_useragent);
                    $this->Template->banners = $arrBanners;
                    return $this->Template;
    
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
                                        'banner_name'    => specialchars(ampersand($objBanners->banner_name)),
                                        'banner_url'     => $objBanners->banner_url,
                                        'banner_url_kurz'=> $banner_url_kurz,
                                        'banner_target'  => $banner_target,
                                        'banner_comment' => ampersand(nl2br($objBanners->banner_comment)),
                                        'banner_pic'     => false,
                                        'banner_flash'   => false,
                                        'banner_text'    => true,
                                        'banner_empty'   => false	// issues 733
                                    );
                    if (($this->banner_template != $this->strTemplate)
                     && ($this->banner_template != '')
                       )
                    {
                        $this->strTemplate = $this->banner_template;
                        $this->Template = new \FrontendTemplate($this->strTemplate);
                    }
                    $arrResults[] = $arrBanners[0];
                    $this->Template->banners = $arrResults;
                     
                    $this->setStatViewUpdate($arrResults, $module_id, $objBanners->banner_useragent);
                    return $this->Template;
                }
            }//Banner vorhanden
            //falls $arrImageSize = false  und kein Text Banner
            $this->Template->banners = $arrBanners; // leeres array
            return $this->Template;
    }
    
    public function getSingleBanner($module_id)
    {
        $objBannerLogic = new BannerLogic();
        
        //RandomBlocker entfernen falls möglich und nötig
        if ( count($this->arrAllBannersBasic) >1 ) // einer muss ja übrig bleiben
        {
            $intRandomBlockerID = $objBannerLogic->getRandomBlockerId($module_id);
            if (isset($this->arrAllBannersBasic[$intRandomBlockerID]))
            {
                unset($this->arrAllBannersBasic[$intRandomBlockerID]);
            }
        }
         
        //Gewichtung nach vorhandenen Wichtungen
        $SingleBannerWeighting = $objBannerLogic->getSingleWeighting();
    
        //alle Basic Daten durchgehen und die löschen die nicht der Wichtung entsprechen
        while ( list($key, $val) = each($this->arrAllBannersBasic) )    // each deprecated PHP 7.2
        {
            if ($val != $SingleBannerWeighting)
            {
                unset($this->arrAllBannersBasic[$key]);
            }
        }
        reset($this->arrAllBannersBasic); //sicher ist sicher
         
        //Zufallszahl
        //array_shuffle und array_rand zu "ungenau"
        $intShowBanner =  mt_rand(1,count($this->arrAllBannersBasic));
        $banner_keys = array_keys($this->arrAllBannersBasic);
        for ($xx=1;$xx<=$intShowBanner;$xx++)
        {
            $banner_id   = array_shift($banner_keys);
        }
         
        //Random Blocker setzen
        $objBannerLogic->setRandomBlockerId($banner_id, $module_id);
         
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
            $objBanners->next();
            BannerHelper::$arrBannerSeen[] = $objBanners->id;
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
                        BannerLog::log('Banner Image with ID "'.$objBanners->id.'" not found', 'BannerHelper getSingleBanner', TL_ERROR);
                        break;
                    }
                    //Banner Neue Größe 0:$Width 1:$Height
                    $arrNewSizeValues = deserialize($objBanners->banner_imgSize);
                    //Banner Neue Größe ermitteln, return array $Width,$Height,$oriSize
                    $arrImageSizenNew = $this->BannerImage->getBannerImageSizeNew($arrImageSize[0],$arrImageSize[1],$arrNewSizeValues[0],$arrNewSizeValues[1]);
                     
                    //wenn oriSize = true, oder bei GIF = original Pfad nehmen
                    if (true === $arrImageSizenNew[2]
                         || 1 == $arrImageSize[2] // GIF
                       )
                    {
                        $FileSrc = $objFile->path;
                        $arrImageSize[0] = $arrImageSizenNew[0];
                        $arrImageSize[1] = $arrImageSizenNew[1];
                        $arrImageSize[3] = ' height="'.$arrImageSizenNew[1].'" width="'.$arrImageSizenNew[0].'"';
                         
                        //fake the Picture::create
                        $picture['img']   = array
                        (
                            'src'    => specialchars(ampersand($FileSrc)),
                            'width'  => $arrImageSizenNew[0],
                            'height' => $arrImageSizenNew[1],
                            'srcset' => specialchars(ampersand($FileSrc))
                        );
                        $picture['alt']   = specialchars(ampersand($objBanners->banner_name));
                        $picture['title'] = specialchars(ampersand($objBanners->banner_comment));
                         
                        BannerLog::writeLog(__METHOD__ , __LINE__ , 'Orisize Picture: '. print_r($picture,true));
                    }
                    else
                    {
                        $FileSrc = \Image::get($this->urlEncode($objFile->path), $arrImageSizenNew[0], $arrImageSizenNew[1],'proportional');
                         
                        $picture = \Picture::create($this->urlEncode($objFile->path), array($arrImageSizenNew[0], $arrImageSizenNew[1], $arrNewSizeValues[2]))->getTemplateData();
                        $picture['alt']   = specialchars(ampersand($objBanners->banner_name));
                        $picture['title'] = specialchars(ampersand($objBanners->banner_comment));
                         
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
                        BannerLog::log('Banner Image with ID "'.$objBanners->id.'" not found', 'BannerHelper getSingleBanner', TL_ERROR);
                        break;
                    }
                    //Banner Neue Größe 0:$Width 1:$Height
                    $arrNewSizeValues = deserialize($objBanners->banner_imgSize);
                    //Banner Neue Größe ermitteln, return array $Width,$Height,$oriSize
                    $arrImageSizenNew = $this->BannerImage->getBannerImageSizeNew($arrImageSize[0],$arrImageSize[1],$arrNewSizeValues[0],$arrNewSizeValues[1]);
                    //Umwandlung bei Parametern
                    $FileSrc = html_entity_decode($objBanners->banner_image_extern, ENT_NOQUOTES, 'UTF-8');

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
                                        'banner_name'    => specialchars(ampersand($objBanners->banner_name)),
                                        'banner_url'     => $objBanners->banner_url,
                                        'banner_target'  => $banner_target,
                                        'banner_comment' => specialchars(ampersand($objBanners->banner_comment)),
                                        'src'            => specialchars(ampersand($FileSrc)),//specialchars(ampersand($this->urlEncode($FileSrc))),
                                        'alt'            => specialchars(ampersand($objBanners->banner_name)),
                                        'size'           => $arrImageSize[3],
                                        'banner_pic'     => true,
                                        'banner_flash'   => false,
                                        'banner_text'    => false,
                                        'banner_empty'   => false,
                                        'picture'        => $picture
                                        );
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
                 
                //anderes Template?
                if (($this->banner_template != $this->strTemplate)
                 && ($this->banner_template != '')
                   )
                {
                    $this->strTemplate = $this->banner_template;
                    $this->Template = new \FrontendTemplate($this->strTemplate);
                }
                $this->setStatViewUpdate($arrBanners, $module_id, $objBanners->banner_useragent);
                $this->Template->banners = $arrBanners;
                return $this->Template;
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
                                    'banner_name'    => specialchars(ampersand($objBanners->banner_name)),
                                    'banner_url'     => $objBanners->banner_url,
                                    'banner_url_kurz'=> $banner_url_kurz,
                                    'banner_target'  => $banner_target,
                                    'banner_comment' => ampersand(nl2br($objBanners->banner_comment)),
                                    'banner_pic'     => false,
                                    'banner_flash'   => false,
                                    'banner_text'    => true,
                                    'banner_empty'   => false	// issues 733
                                );
                if (($this->banner_template != $this->strTemplate)
                 && ($this->banner_template != '')
                   )
                {
                    $this->strTemplate = $this->banner_template;
                    $this->Template = new \FrontendTemplate($this->strTemplate);
                }
                $arrResults[] = $arrBanners[0];
                $this->Template->banners = $arrResults;

                $this->setStatViewUpdate($arrResults, $module_id, $objBanners->banner_useragent);
                return $this->Template;
            }
        }//Banner vorhanden
        //falls $arrImageSize = false  und kein Text Banner
        $this->Template->banners = $arrBanners; // leeres array
        return $this->Template;
    }
    
    protected function setStatViewUpdate($arrBannerData, $module_id, $banner_useragent)
    {
        $objBannerCount = new BannerCount($arrBannerData, $banner_useragent, $module_id);
        $objBannerCount->setStatViewUpdate();
        unset($objBannerCount);
    }
    
}

