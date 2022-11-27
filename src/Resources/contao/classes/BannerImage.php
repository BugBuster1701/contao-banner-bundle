<?php

/**
 * Extension for Contao Open Source CMS, Copyright (C) 2005-2022 Leo Feyer
 *
 * Class BannerImage - Frontend
 *
 * @copyright  Glen Langer 2022 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @licence    LGPL
 * @filesource
 * @see	       https://github.com/BugBuster1701/contao-banner-bundle
 */

/**
 * Run in a custom namespace, so the class can be replaced
 */

namespace BugBuster\Banner;

use BugBuster\Banner\BannerLog;

/**
 * Class BannerImage
 *
 * @copyright  Glen Langer 2022 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @license    LGPL
 */
class BannerImage extends \System
{
    /**
     * Current version of the class.
     * @var string
     */
    public const BANNER_IMAGE_VERSION = '4.0.0';

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

    /**
     * public constructor for phpunit
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns the version number
     *
     * @return string
     */
    public function getVersion()
    {
        return self::BANNER_IMAGE_VERSION;
    }

    /**
     * Get the size of an image
     *
     * @param  string $BannerImage Image path/link
     * @param  string $BannerType  intern,extern,text
     * @return mixed  $array / false
     */
    public function getBannerImageSize($BannerImage, $BannerType)
    {
        switch ($BannerType) {
            case self::BANNER_TYPE_INTERN:
                return $this->getImageSizeInternal($BannerImage);
                break;
            case self::BANNER_TYPE_EXTERN:
                return $this->getImageSizeExternal($BannerImage);
                break;
            case self::BANNER_TYPE_TEXT:
                return false;
                break;
            default:
                return false;
                break;
        }
    }

    /**
     * Get the size of an internal image
     *
     * @param  string $BannerImage Image path
     * @return mixed  $array / false
     */
    protected function getImageSizeInternal($BannerImage)
    {
        $arrImageSize = false;

        try {
            $arrImageSize = getimagesize(TL_ROOT . '/' . $BannerImage);
        } catch (\Exception $e) {
            $arrImageSize = false;
        }

        BannerLog::writeLog(__METHOD__, __LINE__, 'Image Size: '. print_r($arrImageSize, true));

        return $arrImageSize;
    }

    /**
     * Get the size of an external image
     *
     * @param  string $BannerImage Image link
     * @return mixed  $array / false
     */
    protected function getImageSizeExternal($BannerImage)
    {
        $token = md5(uniqid(rand(), true));
        $tmpImage = 'system/tmp/mod_banner_fe_'.$token.'.tmp';

        $objRequest = new \Request();
        $objRequest->redirect = true; // #75: Unterstützung der redirects für externe Affiliat Banner
        $objRequest->rlimit = 5;      // #75: Unterstützung der redirects für externe Affiliat Banner
        $objRequest->send(html_entity_decode($BannerImage, ENT_NOQUOTES, 'UTF-8'));

        //old: Test auf chunked, nicht noetig solange Contao bei HTTP/1.0 bleibt
        try {
            $objFile = new \File($tmpImage);
            $objFile->write($objRequest->response);
            $objFile->close();
        }
        // Temp directory not writeable
        catch (\Exception $e) {
            if ($e->getCode() == 0) {
                BannerLog::logMessage('[getImageSizeExternal] tmpFile Problem: notWriteable');
            } else {
                BannerLog::logMessage('[getImageSizeExternal] tmpFile Problem: error');
            }

            return false;
        }
        $objRequest=null;
        unset($objRequest);
        $arrImageSize = $this->getImageSizeInternal($tmpImage);

        $objFile->delete();
        $objFile = null;
        unset($objFile);

        BannerLog::writeLog(__METHOD__, __LINE__, 'Image Size: '. print_r($arrImageSize, true));

        return $arrImageSize;
    }

    /**
     * Calculate the new size for witdh and height
     *
     * @param  int   $oldWidth  ,mandatory
     * @param  int   $oldHeight ,mandatory
     * @param  int   $newWidth  ,optional
     * @param  int   $newHeight ,optional
     * @return array $Width,$Height,$oriSize
     */
    public function getBannerImageSizeNew($oldWidth, $oldHeight, $newWidth=0, $newHeight=0)
    {
        $Width   = $oldWidth;  //Default, and flash require this
        $Height  = $oldHeight; //Default, and flash require this
        $oriSize = true;       //Attribute for images without conversion

        if ($oldWidth == $newWidth && $oldHeight == $newHeight) {
            return [$Width, $Height, $oriSize];
        }

        if ($newWidth > 0 && $newHeight > 0) {
            $Width   = $newWidth;
            $Height  = $newHeight;
            $oriSize = false;
        } elseif ($newWidth > 0) {
            $Width   = $newWidth;
            $Height  = ceil($newWidth * $oldHeight / $oldWidth);
            $oriSize = false;
        } elseif ($newHeight > 0) {
            $Width   = ceil($newHeight * $oldWidth / $oldHeight);
            $Height  = $newHeight;
            $oriSize = false;
        }

        return [$Width, $Height, $oriSize];
    }

    /**
     * Calculate the new size if necessary by comparing with maxWidth and maxHeight
     *
     * @param  array $arrImageSize
     * @param  int   $maxWidth
     * @param  int   $maxHeight
     * @return array $Width,$Height,$oriSize
     */
    public function getCheckBannerImageSize($arrImageSize, $maxWidth, $maxHeight)
    {
        //$arrImageSize[0] Breite (max 250px in BE)
        //$arrImageSize[1] Hoehe  (max  40px in BE)
        //$arrImageSize[2] Type
        if ($arrImageSize[0] > $arrImageSize[1]) { // Breite > Hoehe = Landscape ==
            if ($arrImageSize[0] > $maxWidth) {	//neue feste Breite
                $newImageSize = $this->getBannerImageSizeNew($arrImageSize[0], $arrImageSize[1], $maxWidth, 0);
                $intWidth  = $newImageSize[0];
                $intHeight = $newImageSize[1];
                $oriSize   = $newImageSize[2];
            } else {
                $intWidth  = $arrImageSize[0];
                $intHeight = $arrImageSize[1];
                $oriSize   = true; // Merkmal fuer Bilder ohne Umrechnung
            }
        } else { 	// Hoehe >= Breite, ggf. Hoehe verkleinern
            if ($arrImageSize[1] > $maxWidth) { // Hoehe > max Breite = Portrait ||
                // pruefen ob bei neuer Hoehe die Breite zu klein wird
                if (($maxWidth*$arrImageSize[0]/$arrImageSize[1]) < $maxHeight) {
                    // Breite statt Hoehe setzen, Breite auf maximale Hoehe
                    $newImageSize = $this->getBannerImageSizeNew($arrImageSize[0], $arrImageSize[1], $maxHeight, 0);
                    $intWidth  = $newImageSize[0];
                    $intHeight = $newImageSize[1];
                    $oriSize   = $newImageSize[2];
                } else {
                    $newImageSize = $this->getBannerImageSizeNew($arrImageSize[0], $arrImageSize[1], 0, $maxHeight);
                    $intWidth  = $newImageSize[0];
                    $intHeight = $newImageSize[1];
                    $oriSize   = $newImageSize[2];
                }
            } else {
                $intWidth  = $arrImageSize[0];
                $intHeight = $arrImageSize[1];
                $oriSize = true; // Merkmal fuer Bilder ohne Umrechnung
            }
        }

        return [$intWidth, $intHeight, $oriSize];
    }
}
