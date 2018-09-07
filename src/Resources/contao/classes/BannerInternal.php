<?php
/**
 * Extension for Contao Open Source CMS, Copyright (C) 2005-2017 Leo Feyer
 *
 * BannerInternal - Frontend Helper Class
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
use BugBuster\Banner\BannerTemplate;

/**
 * Class BannerInternal
 *
 * @copyright  Glen Langer 2017 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Banner
 * @license    LGPL
 */
class BannerInternal
{

    /**
     * Banner intern
     * @var string
     */
    const BANNER_TYPE_INTERN = 'banner_image';
    
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
        //Pfad+Dateiname holen ueber UUID (findByPk leitet um auf findByUuid)
        $objFile = \FilesModel::findByPk($this->objBanners->banner_image);
        //BannerImage Class
        $this->BannerImage = new BannerImage();
        //Banner Art und Größe bestimmen
        $arrImageSize = $this->BannerImage->getBannerImageSize($objFile->path, self::BANNER_TYPE_INTERN);
        //Falls Datei gelöscht wurde, Abbruch
        if (false === $arrImageSize)
        {
            $arrImageSize[2] = 0;
            BannerLog::log('Banner Image with ID "'.$this->objBanners->id.'" not found', __METHOD__ .':'. __LINE__ , TL_ERROR);

            $objReturn = new \stdClass;
            $objReturn->FileSrc = null;
            $objReturn->Picture = null;
            $objReturn->ImageSize = $arrImageSize;
            
            return $objReturn;
        }
        //Banner Neue Größe 0:$Width 1:$Height 2:resize mode
        $arrNewSizeValues = deserialize($this->objBanners->banner_imgSize);
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
                'src'    => \StringUtil::specialchars(ampersand($FileSrc)),
                'width'  => $arrImageSizenNew[0],
                'height' => $arrImageSizenNew[1],
                'srcset' => \StringUtil::specialchars(ampersand($FileSrc))
            );
            $picture['alt']   = \StringUtil::specialchars(ampersand($this->objBanners->banner_name));
            $picture['title'] = \StringUtil::specialchars(ampersand($this->objBanners->banner_comment));
        
            BannerLog::writeLog(__METHOD__ , __LINE__ , 'Orisize Picture: '. print_r($picture,true));
        }
        else
        {
            //Resize an image and store the resized version in the assets/images folder
            //return The path of the resized image or null
            //alt $FileSrc = \Image::get(\System::urlEncode($objFile->path), $arrImageSizenNew[0], $arrImageSizenNew[1],'proportional');
            $container = \System::getContainer();
            $rootDir = $container->getParameter('kernel.project_dir');
            $FileSrc = $container
                        ->get('contao.image.image_factory')
                        ->create($rootDir.'/' . $objFile->path, [$arrImageSizenNew[0], $arrImageSizenNew[1], 'proportional'])
                        ->getUrl($rootDir);
        
            //alt $picture = \Picture::create(\System::urlEncode($objFile->path), array($arrImageSizenNew[0], $arrImageSizenNew[1], $arrNewSizeValues[2]))->getTemplateData();
            $picture = $container
                        ->get('contao.image.picture_factory')
                        ->create($rootDir . '/' . $objFile->path, $arrImageSizenNew);
            $picture = array
            (
                'img' => $picture->getImg(TL_ROOT, TL_FILES_URL),
                'sources' => $picture->getSources(TL_ROOT, TL_FILES_URL)
            );
            
            $picture['alt']   = \StringUtil::specialchars(ampersand($this->objBanners->banner_name));
            $picture['title'] = \StringUtil::specialchars(ampersand($this->objBanners->banner_comment));
        
            BannerLog::writeLog(__METHOD__ , __LINE__ , 'Resize Picture: '. print_r($picture,true));
        
            $arrImageSize[0] = $arrImageSizenNew[0];
            $arrImageSize[1] = $arrImageSizenNew[1];
            $arrImageSize[3] = ' height="'.$arrImageSizenNew[1].'" width="'.$arrImageSizenNew[0].'"';
        }
        
        $objReturn = new \stdClass;
        $objReturn->FileSrc = $FileSrc;
        $objReturn->Picture = $picture;
        $objReturn->ImageSize = $arrImageSize;
        
        return $objReturn;
    }
    
    /**
     * Generate Template Data
     * 
     * @param array     $arrImageSize
     * @param string    $FileSrc
     * @param array     $picture
     * @return array    $arrBanners
     */
    public function generateTemplateData($arrImageSize, $FileSrc, $picture)
    {
        return BannerTemplate::generateTemplateData($arrImageSize, $FileSrc, $picture, $this->objBanners, $this->banner_cssID, $this->banner_class);
    }
}
