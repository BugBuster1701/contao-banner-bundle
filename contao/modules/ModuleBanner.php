<?php

/*
 * This file is part of a BugBuster Contao Bundle.
 *
 * @copyright  Glen Langer 2023 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Contao Banner Bundle
 * @link       https://github.com/BugBuster1701/contao-banner-bundle
 *
 * @license    LGPL-3.0-or-later
 */

/**
 * Run in a custom namespace, so the class can be replaced
 */

namespace BugBuster\Banner;

use Contao\BackendTemplate;
use Contao\Module;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ModuleBanner
 *
 * @copyright  Glen Langer 2007..2022 <http://contao.ninja>
 * @license    LGPL
 */
class ModuleBanner extends Module
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
		if (
			System::getContainer()->get('contao.routing.scope_matcher')
					->isBackendRequest(System::getContainer()->get('request_stack')
					->getCurrentRequest() ?? Request::create(''))
		) {
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### BANNER MODUL ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', array('do'=>'themes', 'table'=>'tl_module', 'act'=>'edit', 'id'=>$this->id)));

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
