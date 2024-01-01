<?php

declare(strict_types=1);

/*
 * This file is part of a BugBuster Contao Bundle.
 *
 * @copyright  Glen Langer 2024 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Contao Banner Bundle
 * @link       https://github.com/BugBuster1701/contao-banner-bundle
 *
 * @license    LGPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 */

namespace BugBuster\BannerBundle\Controller;

use BugBuster\Banner\FrontendBanner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bbfebanner', defaults: ['_scope' => 'frontend', '_token_check' => false])]
class BannerFeController extends AbstractController
{
    #[Route('/banclicks/{strbid}/{bid}', name: 'bugbuster_banner_frontend_clicks', requirements: ['bid' => '\d+'])]
    public function banclicksAction($strbid = '', $bid = 0): Response
    {
        if ('bid' !== $strbid && 'defbid' !== $strbid) {
            return new Response('Invalid Banner Action ('.$strbid.')', 501);
        }
        if (('bid' === $strbid && 0 === $bid) || ('bid' === $strbid && 0 > $bid)) {
            return new Response('Invalid Banner ID ('.$bid.')', 501);
        }
        if (('defbid' === $strbid && 0 === $bid) || ('defbid' === $strbid && 0 > $bid)) {
            return new Response('Invalid Default Banner ID ('.$bid.')', 501);
        }

        $this->container->get('contao.framework')->initialize();

        $controller = new FrontendBanner();

        return $controller->run($strbid, $bid);
    }
}
