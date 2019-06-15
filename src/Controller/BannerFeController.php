<?php

/**
 * @copyright  Glen Langer 2017 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Banner
 * @license    LGPL-3.0+
 * @see	       https://github.com/BugBuster1701/contao-banner-bundle
 */

namespace BugBuster\BannerBundle\Controller;

use BugBuster\Banner\FrontendBanner;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Handles the Banner front end routes.
 *
 * @copyright  Glen Langer 2017 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 *
 * @Route("/bbfebanner", defaults={"_scope" = "frontend", "_token_check" = false})
 */
class BannerFeController extends Controller
{
    /**
     * Renders the alerts content.
     *
     * @return Response
     *
     * @Route("/banclicks", name="bugbuster_banner_frontend_clicks")
     */
    public function banclicksAction()
    {
        $this->container->get('contao.framework')->initialize();
    
        $controller = new FrontendBanner();
    
        return $controller->run();
    }
}
