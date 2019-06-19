<?php

/**
 * Contao Open Source CMS, Copyright (C) 2005-2017 Leo Feyer
 *
 * Contao Module "Banner" - DCA Helper Class DcaBannerCategory
 *
 * @copyright  Glen Langer 2012..2017 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @license    LGPL
 * @filesource
 * @see	       https://github.com/BugBuster1701/contao-banner-bundle
 */

/**
 * Run in a custom namespace, so the class can be replaced
 */

namespace BugBuster\Banner;

class DcaBannerCategory extends \Backend
{
    public function labelCallback($arrRow)
    {
        $label_1 = $arrRow['title'];
        $version_warning = '';

        $bpc = $GLOBALS['TL_LANG']['tl_banner_category']['banner_protected_catagory'];
        if (!empty($arrRow['banner_protected']) && \strlen($arrRow['banner_groups']))
        {
            $label_2 = '<img height="16" width="14" alt="'.$bpc.'" title="'.$bpc.'" src="bundles/bugbusterbanner/default/protect_.gif">';
            //$label_2 = " (".$bpc.")"; // ab Contao 3.1 fehlt das protect_.gif :-(
        } 
        else 
        {
            $label_2 = '';
        }

        return $label_1 . ' ' . $label_2 . $version_warning;
    }

    public function getAdminCheckbox($varValue)
    {
        return '1';
    }
}
