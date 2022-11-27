<?php
/**
 * Extension for Contao Open Source CMS, Copyright (C) 2005-2022 Leo Feyer
 *
 * BannerVideo - Frontend Helper Class
 *
 * @copyright  Glen Langer 2022 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @licence    LGPL
 * @filesource
 * @see	       https://github.com/BugBuster1701/contao-banner-bundle
 */

namespace BugBuster\Banner;

use function array_filter;
use Contao\Environment;
use Contao\File;
use Contao\FilesModel;
use Contao\PageModel;

use Contao\StringUtil;

/**
 * Class BannerVideo
 *
 * @copyright  Glen Langer 2022 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @license    LGPL
 */
class BannerVideo
{
    public const BANNER_TYPE_VIDEO = 'banner_video';

    protected $objBanners;
    protected $banner_cssID;
    protected $banner_class;

    public function __construct($objBanners, $banner_cssID, $banner_class)
    {
        $this->objBanners   = $objBanners;
        $this->banner_cssID = $banner_cssID;
        $this->banner_class = $banner_class;
    }

    public function generateTemplateData()
    {
        $banner_target = ($this->objBanners->banner_target == '1') ? '' : ' target="_blank"';
        $banner_comment = (string) ampersand(nl2br($this->objBanners->banner_comment));

        $this->adjustBannerUrl();
        $banner_url_kurz = $this->getShortBannerUrl();
        
        $strCaption = '';
        $video_files = $this->fetchVideoFiles($strCaption); //hier wird $strCaption aus Meta gesetzt falls möglich
        // Bannerkommentar hat Vorrang gegenüber Meta Caption, Meta Caption wenn Bannerkommentar leer ist
        if (strlen($banner_comment) == 0) {
            $banner_comment = $strCaption;    
        }
        

        return [
            [
                'banner_key'        => 'bid',
                'banner_wrap_id'    => $this->banner_cssID,
                'banner_wrap_class' => $this->banner_class,
                'banner_id'         => $this->objBanners->id,
                'banner_name'       => StringUtil::specialchars(ampersand($this->objBanners->banner_name)),
                'banner_url'        => $this->objBanners->banner_url,
                'banner_url_kurz'   => $banner_url_kurz,
                'banner_target'     => $banner_target,
                'banner_comment'    => $banner_comment,
                'banner_pic'        => false,
                'banner_flash'      => false,
                'banner_text'       => false,
                'banner_empty'      => false,    // issues 733
                'banner_video'      => true,
                'video_files'       => $video_files,
                'video_poster'      => $this->getPoster(),
                'video_size'        => $this->getVideoSize(),
                'video_range'       => $this->getVideoRange(),
            ],
        ];
    }

    /**
     * @return void
     */
    private function adjustBannerUrl(): void
    {
        // Banner Seite als Ziel?
        if ($this->objBanners->banner_jumpTo > 0) {
            $domain = Environment::get('base');
            $objParent = PageModel::findWithDetails($this->objBanners->banner_jumpTo);
            if ($objParent !== null) { // is null when page not exist anymore
                if ($objParent->domain != '') {
                    $domain = (Environment::get('ssl') ? 'https://' : 'http://') . $objParent->domain . TL_PATH . '/';
                }
                $this->objBanners->banner_url = $domain . BannerHelper::frontendUrlGenerator(
                    $objParent->row(),
                    null,
                    $objParent->language
                );
            }
        }
    }

    /**
     * @return mixed|string
     */
    private function getShortBannerUrl()
    {
        // Kurz URL (nur Domain)
        $treffer         = parse_url(BannerHelper::decodePunycode($this->objBanners->banner_url)); // #79
        $banner_url_kurz = $treffer['host'];
        if (isset($treffer['port'])) {
            $banner_url_kurz .= ':' . $treffer['port'];
        }

        return $banner_url_kurz;
    }

    /** @return list<File> */
    private function fetchVideoFiles(&$strCaption): array
    {
        global $objPage;

        if (!$this->objBanners->banner_playerSRC) {
            return [];
        }

        $source = StringUtil::deserialize($this->objBanners->banner_playerSRC);
        if (empty($source) || !\is_array($source)) {
            return [];
        }

        $objFiles = FilesModel::findMultipleByUuidsAndExtensions($source, ['mp4', 'm4v', 'mov', 'wmv', 'webm', 'ogv']);
        if ($objFiles === null) {
            return [];
        }

        $arrFiles = ['webm' => null, 'mp4' => null, 'm4v' => null, 'mov' => null, 'wmv' => null, 'ogv' => null];

        // Convert the language to a locale (see #5678)
        $strLanguage = str_replace('-', '_', $objPage->language);

        // Pass File objects to the template
        foreach ($objFiles as $objFileModel) {
            /** @var FilesModel $objFileModel */
            $objMeta = $objFileModel->getMetadata($strLanguage);
            $strTitle = null;

            if (null !== $objMeta) {
                $strTitle = $objMeta->getTitle();

                if (empty($strCaption)) {
                    $strCaption = $objMeta->getCaption();
                }
            }

            $objFile = new File($objFileModel->path);
            $objFile->title = StringUtil::specialchars($strTitle ?: $objFile->name);

            $arrFiles[$objFile->extension] = $objFile;
        }

        return array_values(array_filter($arrFiles));
    }

    /** @return string|bool */
    private function getPoster()
    {
        // Optional poster
        if ($this->objBanners->banner_posterSRC && ($objFile = FilesModel::findByUuid($this->objBanners->banner_posterSRC)) !== null) {
            return $objFile->path;
        }

        return false;
    }

    private function getVideoSize(): string
    {
        $size = StringUtil::deserialize($this->objBanners->banner_playerSize);

        if (\is_array($size) && !empty($size[0]) && !empty($size[1])) {
            return ' width="' . $size[0] . '" height="' . $size[1] . '"';
        }

        return '';
    }

    private function getVideoRange(): ?string
    {
        if ($this->objBanners->banner_playerStart || $this->objBanners->banner_playerStop) {
            $range = '#t=';

            if ($this->objBanners->banner_playerStart) {
                $range .= $this->objBanners->banner_playerStart;
            }

            if ($this->objBanners->banner_playerStop) {
                $range .= ',' . $this->objBanners->banner_playerStop;
            }

            return $range;
        }

        return null;
    }
}
