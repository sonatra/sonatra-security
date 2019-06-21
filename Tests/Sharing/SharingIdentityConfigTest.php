<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Security\Tests\Sharing;

use Fxp\Component\Security\Exception\InvalidArgumentException;
use Fxp\Component\Security\Sharing\SharingIdentityConfig;
use Fxp\Component\Security\Tests\Fixtures\Model\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class SharingIdentityConfigTest extends TestCase
{
    public function testSharingIdentityConfigByDefault(): void
    {
        $config = new SharingIdentityConfig(MockObject::class);

        static::assertSame(MockObject::class, $config->getType());
        static::assertSame('mockobject', $config->getAlias());
        static::assertFalse($config->isRoleable());
        static::assertFalse($config->isPermissible());
    }

    public function testSharingIdentityConfig(): void
    {
        $config = new SharingIdentityConfig(MockObject::class, 'mock_object', true, true);

        static::assertSame(MockObject::class, $config->getType());
        static::assertSame('mock_object', $config->getAlias());
        static::assertTrue($config->isRoleable());
        static::assertTrue($config->isPermissible());
    }

    public function testMerge(): void
    {
        $config = new SharingIdentityConfig(MockObject::class, 'mock_object', false, false);

        static::assertSame('mock_object', $config->getAlias());
        static::assertFalse($config->isRoleable());
        static::assertFalse($config->isPermissible());

        $config->merge(new SharingIdentityConfig(MockObject::class, 'new_mock_object', true, true));

        static::assertSame('new_mock_object', $config->getAlias());
        static::assertTrue($config->isRoleable());
        static::assertTrue($config->isPermissible());
    }

    public function testMergeWithInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The sharing identity config of "Fxp\Component\Security\Tests\Fixtures\Model\MockObject" can be merged only with the same type, given: "stdClass"');

        $config = new SharingIdentityConfig(MockObject::class);

        $config->merge(new SharingIdentityConfig(\stdClass::class));
    }
}
