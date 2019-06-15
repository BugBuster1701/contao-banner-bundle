<?php

/**
 * @copyright  Glen Langer 2019 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Banner
 * @license    LGPL-3.0+
 * @see	       https://github.com/BugBuster1701/contao-banner-bundle
 */

namespace BugBuster\BannerBundle\Controller;

use BugBuster\Banner\FrontendBanner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Handles the Banner front end routes.
 *
 * @copyright  Glen Langer 2019 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 *
 * @Route("/bbfebanner", defaults={"_scope" = "frontend", "_token_check" = false})
 */
class BannerFeController extends AbstractController
{
    /**
     * Renders the alerts content.
     *
     * @return Response
     *
     * @Route("/banclicks/{strbid}/{bid}", name="bugbuster_banner_frontend_clicks", requirements={"bid"="\d+"})
     */
    public function banclicksAction($strbid = '',$bid = 0)
    {
        if ($strbid != 'bid' && $strbid != 'defbid')
        {
            $objResponse = new Response( 'Invalid Banner Action (' . $strbid . ')' , 501);
            return $objResponse;
        }
        if ( ('bid' == $strbid && 0 == $bid) || ('bid' == $strbid && 0 > $bid) ) 
        {
            $objResponse = new Response( 'Invalid Banner ID (' . $bid . ')' , 501);
            return $objResponse;
        }
        if ( ('defbid' == $strbid && 0 == $bid) || ('defbid' == $strbid && 0 > $bid) )
        {
            $objResponse = new Response( 'Invalid Default Banner ID (' . $bid . ')' , 501);
            return $objResponse;
        }

        //$this->container->get('contao.framework')->initialize();
        $this->get('contao.framework')->initialize();
    
        $controller = new FrontendBanner();
    
        return $controller->run($strbid,$bid);
    }
}
