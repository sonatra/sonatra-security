<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Security\Tests\Doctrine\ORM\Listener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Fxp\Component\Security\Doctrine\ORM\Listener\ObjectFilterListener;
use Fxp\Component\Security\ObjectFilter\ObjectFilterInterface;
use Fxp\Component\Security\Permission\PermissionManagerInterface;
use Fxp\Component\Security\Token\ConsoleToken;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class ObjectFilterListenerTest extends TestCase
{
    /**
     * @var MockObject|TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var MockObject|PermissionManagerInterface
     */
    protected $permissionManager;

    /**
     * @var MockObject|ObjectFilterInterface
     */
    protected $objectFilter;

    /**
     * @var EntityManagerInterface|MockObject
     */
    protected $em;

    /**
     * @var MockObject|UnitOfWork
     */
    protected $uow;

    /**
     * @var ObjectFilterListener
     */
    protected $listener;

    protected function setUp(): void
    {
        $this->tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();
        $this->permissionManager = $this->getMockBuilder(PermissionManagerInterface::class)->getMock();
        $this->objectFilter = $this->getMockBuilder(ObjectFilterInterface::class)->getMock();
        $this->em = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
        $this->uow = $this->getMockBuilder(UnitOfWork::class)->disableOriginalConstructor()->getMock();
        $this->listener = new ObjectFilterListener();

        $this->em->expects(static::any())
            ->method('getUnitOfWork')
            ->willReturn($this->uow)
        ;

        $this->listener->setTokenStorage($this->tokenStorage);
        $this->listener->setPermissionManager($this->permissionManager);
        $this->listener->setObjectFilter($this->objectFilter);

        static::assertCount(3, $this->listener->getSubscribedEvents());
    }

    public function getInvalidInitMethods(): array
    {
        return [
            ['setTokenStorage', []],
            ['setPermissionManager', ['tokenStorage']],
            ['setObjectFilter', ['tokenStorage', 'permissionManager']],
        ];
    }

    /**
     * @dataProvider getInvalidInitMethods
     *
     * @param string   $method  The method
     * @param string[] $setters The setters
     */
    public function testInvalidInit($method, array $setters): void
    {
        $this->expectException(\Fxp\Component\Security\Exception\SecurityException::class);

        $msg = sprintf('The "%s()" method must be called before the init of the "Fxp\Component\Security\Doctrine\ORM\Listener\ObjectFilterListener" class', $method);
        $this->expectExceptionMessage($msg);

        $listener = new ObjectFilterListener();

        if (\in_array('tokenStorage', $setters, true)) {
            $listener->setTokenStorage($this->tokenStorage);
        }

        if (\in_array('permissionManager', $setters, true)) {
            $listener->setPermissionManager($this->permissionManager);
        }

        if (\in_array('objectFilter', $setters, true)) {
            $listener->setObjectFilter($this->objectFilter);
        }

        /** @var LifecycleEventArgs $args */
        $args = $this->getMockBuilder(LifecycleEventArgs::class)->disableOriginalConstructor()->getMock();

        $listener->postLoad($args);
    }

    public function testPostFlush(): void
    {
        $this->permissionManager->expects(static::once())
            ->method('resetPreloadPermissions')
            ->with([])
        ;

        $this->listener->postFlush();
    }

    public function testPostLoadWithDisabledPermissionManager(): void
    {
        /** @var LifecycleEventArgs $args */
        $args = $this->getMockBuilder(LifecycleEventArgs::class)->disableOriginalConstructor()->getMock();
        $token = $this->getMockBuilder(TokenInterface::class)->getMock();

        $this->tokenStorage->expects(static::once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $this->permissionManager->expects(static::once())
            ->method('isEnabled')
            ->willReturn(false)
        ;

        $this->objectFilter->expects(static::never())
            ->method('filter')
        ;

        $this->listener->postLoad($args);
    }

    public function testPostLoadWithEmptyToken(): void
    {
        /** @var LifecycleEventArgs $args */
        $args = $this->getMockBuilder(LifecycleEventArgs::class)->disableOriginalConstructor()->getMock();

        $this->tokenStorage->expects(static::once())
            ->method('getToken')
            ->willReturn(null)
        ;

        $this->objectFilter->expects(static::never())
            ->method('filter')
        ;

        $this->listener->postLoad($args);
    }

    public function testPostLoadWithConsoleToken(): void
    {
        /** @var LifecycleEventArgs $args */
        $args = $this->getMockBuilder(LifecycleEventArgs::class)->disableOriginalConstructor()->getMock();
        $token = $this->getMockBuilder(ConsoleToken::class)->disableOriginalConstructor()->getMock();

        $this->tokenStorage->expects(static::once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $this->objectFilter->expects(static::never())
            ->method('filter')
        ;

        $this->listener->postLoad($args);
    }

    public function testPostLoad(): void
    {
        /** @var LifecycleEventArgs|MockObject $args */
        $args = $this->getMockBuilder(LifecycleEventArgs::class)->disableOriginalConstructor()->getMock();
        $token = $this->getMockBuilder(TokenInterface::class)->getMock();
        $entity = new \stdClass();

        $this->tokenStorage->expects(static::once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $this->permissionManager->expects(static::once())
            ->method('isEnabled')
            ->willReturn(true)
        ;

        $args->expects(static::once())
            ->method('getEntity')
            ->willReturn($entity)
        ;

        $this->objectFilter->expects(static::once())
            ->method('filter')
            ->with($entity)
        ;

        $this->listener->postLoad($args);
    }

    public function testOnFlushWithDisabledPermissionManager(): void
    {
        /** @var OnFlushEventArgs $args */
        $args = $this->getMockBuilder(OnFlushEventArgs::class)->disableOriginalConstructor()->getMock();
        $token = $this->getMockBuilder(TokenInterface::class)->getMock();

        $this->tokenStorage->expects(static::once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $this->permissionManager->expects(static::once())
            ->method('isEnabled')
            ->willReturn(false)
        ;

        $this->objectFilter->expects(static::never())
            ->method('filter')
        ;

        $this->listener->onFlush($args);
    }

    public function testOnFlushWithEmptyToken(): void
    {
        /** @var OnFlushEventArgs $args */
        $args = $this->getMockBuilder(OnFlushEventArgs::class)->disableOriginalConstructor()->getMock();

        $this->tokenStorage->expects(static::once())
            ->method('getToken')
            ->willReturn(null)
        ;

        $this->objectFilter->expects(static::never())
            ->method('filter')
        ;

        $this->listener->onFlush($args);
    }

    public function testOnFlushWithConsoleToken(): void
    {
        /** @var OnFlushEventArgs $args */
        $args = $this->getMockBuilder(OnFlushEventArgs::class)->disableOriginalConstructor()->getMock();
        $token = $this->getMockBuilder(ConsoleToken::class)->disableOriginalConstructor()->getMock();

        $this->tokenStorage->expects(static::once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $this->objectFilter->expects(static::never())
            ->method('filter')
        ;

        $this->listener->onFlush($args);
    }

    public function testOnFlushWithCreateEntity(): void
    {
        /** @var MockObject|OnFlushEventArgs $args */
        $args = $this->getMockBuilder(OnFlushEventArgs::class)->disableOriginalConstructor()->getMock();
        $token = $this->getMockBuilder(TokenInterface::class)->getMock();
        $object = $this->getMockBuilder(\stdClass::class)->getMock();

        $this->tokenStorage->expects(static::once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $this->permissionManager->expects(static::once())
            ->method('isEnabled')
            ->willReturn(true)
        ;

        $args->expects(static::once())
            ->method('getEntityManager')
            ->willReturn($this->em)
        ;

        $this->objectFilter->expects(static::once())
            ->method('beginTransaction')
        ;

        $this->uow->expects(static::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([$object])
        ;

        $this->uow->expects(static::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([])
        ;

        $this->uow->expects(static::once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([])
        ;

        $this->objectFilter->expects(static::once())
            ->method('restore')
        ;

        $this->listener->onFlush($args);
    }

    public function testOnFlushWithUpdateEntity(): void
    {
        /** @var MockObject|OnFlushEventArgs $args */
        $args = $this->getMockBuilder(OnFlushEventArgs::class)->disableOriginalConstructor()->getMock();
        $token = $this->getMockBuilder(TokenInterface::class)->getMock();
        $object = $this->getMockBuilder(\stdClass::class)->getMock();

        $this->tokenStorage->expects(static::once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $this->permissionManager->expects(static::once())
            ->method('isEnabled')
            ->willReturn(true)
        ;

        $args->expects(static::once())
            ->method('getEntityManager')
            ->willReturn($this->em)
        ;

        $this->objectFilter->expects(static::once())
            ->method('beginTransaction')
        ;

        $this->uow->expects(static::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([])
        ;

        $this->uow->expects(static::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$object])
        ;

        $this->uow->expects(static::once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([])
        ;

        $this->objectFilter->expects(static::once())
            ->method('restore')
        ;

        $this->listener->onFlush($args);
    }

    public function testOnFlushWithDeleteEntity(): void
    {
        /** @var MockObject|OnFlushEventArgs $args */
        $args = $this->getMockBuilder(OnFlushEventArgs::class)->disableOriginalConstructor()->getMock();
        $token = $this->getMockBuilder(TokenInterface::class)->getMock();
        $object = $this->getMockBuilder(\stdClass::class)->getMock();

        $this->tokenStorage->expects(static::once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $this->permissionManager->expects(static::once())
            ->method('isEnabled')
            ->willReturn(true)
        ;

        $args->expects(static::once())
            ->method('getEntityManager')
            ->willReturn($this->em)
        ;

        $this->objectFilter->expects(static::once())
            ->method('beginTransaction')
        ;

        $this->uow->expects(static::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([])
        ;

        $this->uow->expects(static::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([])
        ;

        $this->uow->expects(static::once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([$object])
        ;

        $this->listener->onFlush($args);
    }

    public function testOnFLush(): void
    {
        /** @var MockObject|OnFlushEventArgs $args */
        $args = $this->getMockBuilder(OnFlushEventArgs::class)->disableOriginalConstructor()->getMock();
        $token = $this->getMockBuilder(TokenInterface::class)->getMock();

        $this->tokenStorage->expects(static::once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $this->permissionManager->expects(static::once())
            ->method('isEnabled')
            ->willReturn(true)
        ;

        $args->expects(static::once())
            ->method('getEntityManager')
            ->willReturn($this->em)
        ;

        $this->objectFilter->expects(static::once())
            ->method('beginTransaction')
        ;

        $this->uow->expects(static::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([])
        ;

        $this->uow->expects(static::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([])
        ;

        $this->uow->expects(static::once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([])
        ;

        $this->objectFilter->expects(static::once())
            ->method('commit')
        ;

        $this->listener->onFlush($args);
    }
}
