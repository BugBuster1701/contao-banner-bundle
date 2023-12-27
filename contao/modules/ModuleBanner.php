<?php

/**
 * Contao Open Source CMS, Copyright (C) 2005-2022 Leo Feyer
 *
 * Modul Banner - Frontend , only Insert Tag
 *
 * @copyright  Glen Langer 2007..2022 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @license    LGPL
 * @filesource
 * @see        https://github.com/BugBuster1701/contao-banner-bundle
 */

/**
 * Run in a custom namespace, so the class can be replaced
 */

namespace BugBuster\Banner;

use Contao\System;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ModuleBanner
 *
 * @copyright  Glen Langer 2007..2022 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @license    LGPL
 */
class ModuleBanner extends \Contao\Module
{
    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_banner_tag';

    /**
     * Display a wildcard in the back end
     * @return string
     */
    public function generate()
    {
        if (System::getContainer()->get('contao.routing.scope_matcher')
                    ->isBackendRequest(System::getContainer()->get('request_stack')
                    ->getCurrentRequest() ?? Request::create(''))) 
        {
            $objTemplate = new \Contao\BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### BANNER MODUL ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = \Contao\StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', array('do'=>'themes', 'table'=>'tl_module', 'act'=>'edit', 'id'=>$this->id)));

            return $objTemplate->parse();
        }

        return parent::generate();
    }

    protected function compile()
    {
        $this->Template->banner_module_id    = $this->id;
        $this->Template->banner_outputFormat = $GLOBALS['objPage']->outputFormat;
        $this->Template->banner_templatepfad = $GLOBALS['objPage']->templateGroup;
    }
}
