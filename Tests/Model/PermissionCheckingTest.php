<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Security\Tests\Model;

use Sonatra\Component\Security\Model\PermissionChecking;
use Sonatra\Component\Security\Tests\Fixtures\Model\MockPermission;

/**
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class PermissionCheckingTest extends \PHPUnit_Framework_TestCase
{
    public function testModel()
    {
        $perm = new MockPermission();
        $permChecking = new PermissionChecking($perm, true);

        $this->assertSame($perm, $permChecking->getPermission());
        $this->assertTrue($permChecking->isGranted());
    }
}
