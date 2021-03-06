<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Security\Tests\Firewall;

use Fxp\Component\Security\Firewall\AnonymousRoleListener;
use Fxp\Component\Security\Identity\SecurityIdentityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class AnonymousRoleListenerTest extends TestCase
{
    /**
     * @var MockObject|SecurityIdentityManagerInterface
     */
    protected $sidManager;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var AuthenticationTrustResolverInterface|MockObject
     */
    protected $trustResolver;

    /**
     * @var MockObject|TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var MockObject|Request
     */
    protected $request;

    /**
     * @var MockObject|RequestEvent
     */
    protected $event;

    /**
     * @var AnonymousRoleListener
     */
    protected $listener;

    protected function setUp(): void
    {
        $this->sidManager = $this->getMockBuilder(SecurityIdentityManagerInterface::class)->getMock();
        $this->config = [
            'role' => 'ROLE_CUSTOM_ANONYMOUS',
        ];
        $this->trustResolver = $this->getMockBuilder(AuthenticationTrustResolverInterface::class)->getMock();
        $this->tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();
        $this->request = $this->getMockBuilder(Request::class)->getMock();
        $this->event = $this->getMockBuilder(RequestEvent::class)->disableOriginalConstructor()->getMock();
        $this->event->expects(static::any())
            ->method('getRequest')
            ->willReturn($this->request)
        ;

        $this->listener = new AnonymousRoleListener(
            $this->sidManager,
            $this->config,
            $this->trustResolver,
            $this->tokenStorage
        );
    }

    public function testBasic(): void
    {
        static::assertTrue($this->listener->isEnabled());
        $this->listener->setEnabled(false);
        static::assertFalse($this->listener->isEnabled());
    }

    public function testInvokeWithDisabledListener(): void
    {
        $this->sidManager->expects(static::never())
            ->method('addSpecialRole')
        ;

        $this->tokenStorage->expects(static::never())
            ->method('getToken')
        ;

        $this->trustResolver->expects(static::never())
            ->method('isAnonymous')
        ;

        $this->listener->setEnabled(false);
        ($this->listener)($this->event);
    }

    public function testInvokeWithoutAnonymousRole(): void
    {
        $this->listener = new AnonymousRoleListener(
            $this->sidManager,
            [
                'role' => null,
            ],
            $this->trustResolver,
            $this->tokenStorage
        );

        $this->sidManager->expects(static::never())
            ->method('addSpecialRole')
        ;

        $this->tokenStorage->expects(static::never())
            ->method('getToken')
        ;

        $this->trustResolver->expects(static::never())
            ->method('isAnonymous')
        ;

        ($this->listener)($this->event);
    }

    public function testInvokeWithoutToken(): void
    {
        $this->tokenStorage->expects(static::once())
            ->method('getToken')
            ->willReturn(null)
        ;

        $this->trustResolver->expects(static::never())
            ->method('isAnonymous')
        ;

        $this->sidManager->expects(static::once())
            ->method('addSpecialRole')
            ->with('ROLE_CUSTOM_ANONYMOUS')
        ;

        ($this->listener)($this->event);
    }

    public function testInvokeWithToken(): void
    {
        $token = $this->getMockBuilder(TokenInterface::class)->getMock();

        $this->tokenStorage->expects(static::once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $this->trustResolver->expects(static::once())
            ->method('isAnonymous')
            ->with($token)
            ->willReturn(true)
        ;

        $this->sidManager->expects(static::once())
            ->method('addSpecialRole')
            ->with('ROLE_CUSTOM_ANONYMOUS')
        ;

        ($this->listener)($this->event);
    }
}
