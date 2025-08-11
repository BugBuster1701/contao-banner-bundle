<?php

declare(strict_types=1);

/*
 * This file is part of a BugBuster Contao Bundle.
 *
 * @copyright  Glen Langer 2025 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Contao Banner Bundle
 * @link       https://github.com/BugBuster1701/contao-banner-bundle
 *
 * @license    LGPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 */

namespace BugBuster\BannerBundle\Tests;

use BugBuster\BannerBundle\BugBusterBannerBundle;
use PHPUnit\Framework\TestCase;

class BugBusterBannerBundleTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $bundle = new BugBusterBannerBundle();

        $this->assertInstanceOf('BugBuster\BannerBundle\BugBusterBannerBundle', $bundle);
    }
}
