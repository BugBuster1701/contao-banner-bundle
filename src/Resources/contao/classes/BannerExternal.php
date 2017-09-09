<?php
/**
 * Extension for Contao Open Source CMS, Copyright (C) 2005-2017 Leo Feyer
 *
 * BannerExternal - Frontend Helper Class
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

/**
 * Class BannerExternal
 *
 * @copyright  Glen Langer 2017 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Banner
 * @license    LGPL
 */
class BannerExternal
{

    /**
     * Banner extern
     * @var string
     */
    const BANNER_TYPE_EXTERN = 'banner_image_extern';
    
    protected $objBanners = null;
    protected $banner_cssID = null;
    protected $banner_class = null;
    
    public function __construct ($objBanners, $banner_cssID, $banner_class)
    {
        $this->objBanners   = $objBanners;
        $this->banner_cssID = $banner_cssID;
        $this->banner_class = $banner_class;
    }
    
    
    /**
     *
     * @return \stdClass
     */
    public function generateImageData()
    {
        //BannerImage Class
        $this->BannerImage = new BannerImage();
        //Banner Art und Größe bestimmen
        $arrImageSize = $this->BannerImage->getBannerImageSize($this->objBanners->banner_image_extern, self::BANNER_TYPE_EXTERN);
        //Falls Datei gelöscht wurde, Abbruch
        if (false === $arrImageSize)
        {
            $arrImageSize[2] = 0;
            BannerLog::log('Banner Image with ID "'.$this->objBanners->id.'" not found', __METHOD__ .':'. __LINE__, TL_ERROR);
            
            $objReturn = new \stdClass;
            $objReturn->FileSrc = null;
            $objReturn->Picture = null;
            $objReturn->ImageSize = $arrImageSize;
            
            return $objReturn;
        }
        //Banner Neue Größe 0:$Width 1:$Height
        $arrNewSizeValues = deserialize($this->objBanners->banner_imgSize);
        //Banner Neue Größe ermitteln, return array $Width,$Height,$oriSize
        $arrImageSizenNew = $this->BannerImage->getBannerImageSizeNew($arrImageSize[0],$arrImageSize[1],$arrNewSizeValues[0],$arrNewSizeValues[1]);
        //Umwandlung bei Parametern
        $FileSrc = html_entity_decode($this->objBanners->banner_image_extern, ENT_NOQUOTES, 'UTF-8');
        
        //fake the Picture::create
        $picture['img']   = array
        (
            'src'    => \StringUtil::specialchars(ampersand($FileSrc)),
            'width'  => $arrImageSizenNew[0],
            'height' => $arrImageSizenNew[1],
            'srcset' => \StringUtil::specialchars(ampersand($FileSrc))
        );
        $picture['alt']   = \StringUtil::specialchars(ampersand($this->objBanners->banner_name));
        $picture['title'] = \StringUtil::specialchars(ampersand($this->objBanners->banner_comment));
        
        $arrImageSize[0] = $arrImageSizenNew[0];
        $arrImageSize[1] = $arrImageSizenNew[1];
        $arrImageSize[3] = ' height="'.$arrImageSizenNew[1].'" width="'.$arrImageSizenNew[0].'"';
        
        $objReturn = new \stdClass;
        $objReturn->FileSrc = $FileSrc;
        $objReturn->Picture = $picture;
        $objReturn->ImageSize = $arrImageSize;
        
        return $objReturn;
    }
    
    public function generateTemplateData($arrImageSize, $FileSrc, $picture)
    {
        $banner_target = ($this->objBanners->banner_target == '1') ? '' : ' target="_blank"';
        
        if ( strlen($this->objBanners->banner_comment) > 1 )
        {
            $banner_comment_pos = strpos($this->objBanners->banner_comment,"\n",1);
            if ($banner_comment_pos !== false)
            {
                $this->objBanners->banner_comment = substr($this->objBanners->banner_comment,0,$banner_comment_pos);
            }
        }
        
        // Banner Seite als Ziel?
        if ($this->objBanners->banner_jumpTo > 0)
        {
            $domain = \Environment::get('base');
            $objParent = \PageModel::findWithDetails($this->objBanners->banner_jumpTo);
            if ($objParent !== null) // is null when page not exist anymore
            {
                if ($objParent->domain != '')
                {
                    $domain = (\Environment::get('ssl') ? 'https://' : 'http://') . $objParent->domain . TL_PATH . '/';
                }
                $this->objBanners->banner_url = $domain . \Controller::generateFrontendUrl($objParent->row(), '', $objParent->language);
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
                'banner_wrap_id'    => $this->banner_cssID,
                'banner_wrap_class' => $this->banner_class,
                'banner_id'      => $this->objBanners->id,
                'banner_name'    => \StringUtil::specialchars(ampersand($this->objBanners->banner_name)),
                'banner_url'     => $this->objBanners->banner_url,
                'banner_target'  => $banner_target,
                'banner_comment' => \StringUtil::specialchars(ampersand($this->objBanners->banner_comment)),
                'src'            => \StringUtil::specialchars(ampersand($FileSrc)),//specialchars(ampersand($this->urlEncode($FileSrc))),
                'alt'            => \StringUtil::specialchars(ampersand($this->objBanners->banner_name)),
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
                'banner_wrap_id'    => $this->banner_cssID,
                'banner_wrap_class' => $this->banner_class,
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
        
        return $arrBanners;
    }
    
}

