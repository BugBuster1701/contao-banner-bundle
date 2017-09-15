<?php 

/**
 * Contao Open Source CMS, Copyright (C) 2005-2017 Leo Feyer
 *
 * Contao Module "Banner" - DCA Helper Class DcaModuleBanner
 *
 * @copyright  Glen Langer 2012..2017 <http://contao.ninja>
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

/**
 * DCA Helper Class DcaModuleBanner
 *
 * @copyright  Glen Langer 2012..2017 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Banner
 *
 */
class DcaModuleBanner extends \Backend
{
    public function getBannerTemplates($dc)
    {
        return $this->getTemplateGroup('mod_banner_list_', $dc->activeRecord->pid);
    }
}
