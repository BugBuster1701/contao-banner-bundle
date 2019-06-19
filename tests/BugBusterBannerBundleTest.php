<?php

/*
 * This file is part of a BugBuster Contao Bundle
 *
 * @copyright  Glen Langer 2019 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Contao Banner Bundle
 * @license    LGPL-3.0-or-later
 * @see        https://github.com/BugBuster1701/contao-banner-bundle
 */

namespace BugBuster\BannerBundle\Tests;

use BugBuster\BannerBundle\BugBusterBannerBundle;
use PHPUnit\Framework\TestCase;

class BugBusterBannerBundleTest extends TestCase
{
    public function testCanBeInstantiated()
    {
        $bundle = new BugBusterBannerBundle();

        $this->assertInstanceOf('BugBuster\BannerBundle\BugBusterBannerBundle', $bundle);
    }
}
