<?php

/**
 * Contao Open Source CMS, Copyright (C) 2005-2022 Leo Feyer
 *
 * Contao Module "Banner" - DCA Helper Class DcaModuleBanner
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

/**
 * DCA Helper Class DcaModuleBanner
 *
 * @copyright  Glen Langer 2012..2022 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 */
class DcaModuleBanner extends \Contao\Backend
{
    public function getBannerTemplates()
    {
        return $this->getTemplateGroup('mod_banner_list_');
    }
}
