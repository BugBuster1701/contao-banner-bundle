<?php

/**
 * Contao Open Source CMS, Copyright (C) 2005-2013 Leo Feyer
 *
 * Modul Banner - Frontend , only Insert Tag
 *
 * @copyright  Glen Langer 2007..2015 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Banner
 * @license    LGPL
 * @filesource
 * @see        https://github.com/BugBuster1701/banner
 */

/**
 * Run in a custom namespace, so the class can be replaced
 */
namespace BugBuster\Banner;

/**
 * Class ModuleBanner
 *
 * @copyright  Glen Langer 2007..2015 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Banner
 * @license    LGPL
 */
class ModuleBanner extends \Module
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
	    if (TL_MODE == 'BE')
	    {
	        $objTemplate = new \BackendTemplate('be_wildcard');
	        $objTemplate->wildcard = '### BANNER MODUL ###';
	        $objTemplate->title = $this->headline;
	        $objTemplate->id = $this->id;
	        $objTemplate->link = $this->name;
	        $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;
	        
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
