<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Security\Tests\Permission;

use Fxp\Component\Security\Permission\PermissionConfigCollection;
use Fxp\Component\Security\Permission\PermissionConfigInterface;
use Fxp\Component\Security\Permission\PermissionFactory;
use Fxp\Component\Security\Tests\Fixtures\Model\MockObject as FixtureMockObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Loader\LoaderInterface;

/**
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class PermissionFactoryTest extends TestCase
{
    /**
     * @var LoaderInterface|MockObject
     */
    private $loader;

    /**
     * @var PermissionFactory
     */
    private $factory;

    protected function setUp(): void
    {
        $this->loader = $this->getMockBuilder(LoaderInterface::class)->getMock();
        $this->factory = new PermissionFactory($this->loader, 'resource');
    }

    protected function tearDown(): void
    {
        $this->loader = null;
        $this->factory = null;
    }

    public function testCreateConfigurations(): void
    {
        $expected = new PermissionConfigCollection();

        $this->loader->expects(static::once())
            ->method('load')
            ->with('resource')
            ->willReturn($expected)
        ;

        static::assertSame($expected, $this->factory->createConfigurations());
    }

    public function testCreateConfigurationsWithDefaultFields(): void
    {
        $this->factory = new PermissionFactory($this->loader, 'resource', [
            'fields' => [
                'id' => [
                    'operations' => ['read'],
                ],
            ],
        ]);

        /** @var MockObject|PermissionConfigInterface $config */
        $config = $this->getMockBuilder(PermissionConfigInterface::class)->getMock();
        $config->expects(static::atLeast(1))
            ->method('getType')
            ->willReturn(FixtureMockObject::class)
        ;
        $config->expects(static::atLeast(1))
            ->method('buildFields')
            ->willReturn(true)
        ;
        $config->expects(static::atLeast(1))
            ->method('buildDefaultFields')
            ->willReturn(true)
        ;

        $expected = new PermissionConfigCollection();
        $expected->add($config);

        $this->loader->expects(static::once())
            ->method('load')
            ->with('resource')
            ->willReturn($expected)
        ;

        $config->expects(static::once())
            ->method('merge')
        ;

        static::assertSame($expected, $this->factory->createConfigurations());
    }

    public function testCreateConfigurationsWithDefaultMasterFieldMapping(): void
    {
        $this->factory = new PermissionFactory($this->loader, 'resource', [
            'master_mapping_permissions' => [
                'view' => 'read',
                'update' => 'edit',
                'create' => 'edit',
                'delete' => 'edit',
            ],
        ]);

        /** @var MockObject|PermissionConfigInterface $config */
        $config = $this->getMockBuilder(PermissionConfigInterface::class)->getMock();
        $config->expects(static::atLeast(1))
            ->method('getType')
            ->willReturn(FixtureMockObject::class)
        ;
        $config->expects(static::atLeast(1))
            ->method('getMaster')
            ->willReturn('foo')
        ;
        $config->expects(static::atLeast(1))
            ->method('getMasterFieldMappingPermissions')
            ->willReturn([])
        ;
        $config->expects(static::atLeast(1))
            ->method('buildFields')
            ->willReturn(true)
        ;
        $config->expects(static::atLeast(1))
            ->method('buildDefaultFields')
            ->willReturn(true)
        ;

        $expected = new PermissionConfigCollection();
        $expected->add($config);

        $this->loader->expects(static::once())
            ->method('load')
            ->with('resource')
            ->willReturn($expected)
        ;

        $config->expects(static::atLeast(2))
            ->method('merge')
        ;

        static::assertSame($expected, $this->factory->createConfigurations());
    }
}
