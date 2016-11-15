<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Security\Tests\Identity;

use Sonatra\Component\Security\Identity\GroupSecurityIdentity;
use Sonatra\Component\Security\Identity\OrganizationSecurityIdentity;
use Sonatra\Component\Security\Identity\RoleSecurityIdentity;
use Sonatra\Component\Security\Identity\SecurityIdentityInterface;
use Sonatra\Component\Security\Model\GroupInterface;
use Sonatra\Component\Security\Model\OrganizationInterface;
use Sonatra\Component\Security\Organizational\OrganizationalContextInterface;
use Sonatra\Component\Security\Tests\Fixtures\Model\MockOrganization;
use Sonatra\Component\Security\Tests\Fixtures\Model\MockOrganizationUserRoleableGroupable;
use Sonatra\Component\Security\Tests\Fixtures\Model\MockUserOrganizationUsersGroupable;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;

/**
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class OrganizationSecurityIdentityTest extends \PHPUnit_Framework_TestCase
{
    public function testDebugInfo()
    {
        $sid = new OrganizationSecurityIdentity('foo');

        $this->assertSame('OrganizationSecurityIdentity(foo)', (string) $sid);
    }

    public function testTypeAndIdentifier()
    {
        $identity = new OrganizationSecurityIdentity('identifier');

        $this->assertSame(OrganizationSecurityIdentity::TYPE, $identity->getType());
        $this->assertSame('identifier', $identity->getIdentifier());
    }

    public function getIdentities()
    {
        $id3 = $this->getMockBuilder(SecurityIdentityInterface::class)->getMock();
        $id3->expects($this->any())->method('getType')->willReturn(OrganizationSecurityIdentity::TYPE);
        $id3->expects($this->any())->method('getIdentifier')->willReturn('identifier');

        return array(
            array(new OrganizationSecurityIdentity('identifier'), true),
            array(new OrganizationSecurityIdentity('other'), false),
            array($id3, false),
        );
    }

    /**
     * @dataProvider getIdentities
     *
     * @param mixed $value  The value
     * @param bool  $result The expected result
     */
    public function testEquals($value, $result)
    {
        $identity = new OrganizationSecurityIdentity('identifier');

        $this->assertSame($result, $identity->equals($value));
    }

    public function testFromAccount()
    {
        /* @var OrganizationInterface|\PHPUnit_Framework_MockObject_MockObject $org */
        $org = $this->getMockBuilder(OrganizationInterface::class)->getMock();
        $org->expects($this->once())
            ->method('getName')
            ->willReturn('foo');

        $sid = OrganizationSecurityIdentity::fromAccount($org);

        $this->assertInstanceOf(OrganizationSecurityIdentity::class, $sid);
        $this->assertSame(OrganizationSecurityIdentity::TYPE, $sid->getType());
        $this->assertSame('foo', $sid->getIdentifier());
    }

    public function testFormTokenWithoutOrganizationalContext()
    {
        $user = new MockUserOrganizationUsersGroupable();
        $org = new MockOrganization('foo');
        $orgUser = new MockOrganizationUserRoleableGroupable($org, $user);

        $org->addRole('ROLE_ORG_TEST');

        /* @var GroupInterface|\PHPUnit_Framework_MockObject_MockObject $group */
        $group = $this->getMockBuilder(GroupInterface::class)->getMock();
        $group->expects($this->once())
            ->method('getName')
            ->willReturn('GROUP_TEST');
        $group->expects($this->once())
            ->method('getGroup')
            ->willReturn('GROUP_ORG_USER_TEST');

        $orgUser->addGroup($group);
        $orgUser->addRole('ROLE_ORG_USER_TEST');

        $user->addUserOrganization($orgUser);

        /* @var TokenInterface|\PHPUnit_Framework_MockObject_MockObject $token */
        $token = $this->getMockBuilder(TokenInterface::class)->getMock();
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        /* @var RoleHierarchyInterface|\PHPUnit_Framework_MockObject_MockObject $roleHierarchy */
        $roleHierarchy = $this->getMockBuilder(RoleHierarchyInterface::class)->getMock();
        $roleHierarchy->expects($this->once())
            ->method('getReachableRoles')
            ->willReturnCallback(function ($value) {
                return $value;
            });

        $sids = OrganizationSecurityIdentity::fromToken($token, null, $roleHierarchy);

        $this->assertCount(4, $sids);
        $this->assertInstanceOf(OrganizationSecurityIdentity::class, $sids[0]);
        $this->assertSame('foo', $sids[0]->getIdentifier());
        $this->assertInstanceOf(GroupSecurityIdentity::class, $sids[1]);
        $this->assertSame('GROUP_ORG_USER_TEST', $sids[1]->getIdentifier());
        $this->assertInstanceOf(RoleSecurityIdentity::class, $sids[2]);
        $this->assertSame('ROLE_ORG_USER_TEST__FOO', $sids[2]->getIdentifier());
        $this->assertInstanceOf(RoleSecurityIdentity::class, $sids[3]);
        $this->assertSame('ROLE_ORG_TEST', $sids[3]->getIdentifier());
    }

    public function testFormTokenWithOrganizationalContext()
    {
        $user = new MockUserOrganizationUsersGroupable();
        $org = new MockOrganization('foo');
        $orgUser = new MockOrganizationUserRoleableGroupable($org, $user);

        $org->addRole('ROLE_ORG_TEST');

        /* @var GroupInterface|\PHPUnit_Framework_MockObject_MockObject $group */
        $group = $this->getMockBuilder(GroupInterface::class)->getMock();
        $group->expects($this->once())
            ->method('getName')
            ->willReturn('GROUP_TEST');
        $group->expects($this->once())
            ->method('getGroup')
            ->willReturn('GROUP_ORG_USER_TEST');

        $orgUser->addGroup($group);
        $orgUser->addRole('ROLE_ORG_USER_TEST');

        $user->addUserOrganization($orgUser);

        /* @var TokenInterface|\PHPUnit_Framework_MockObject_MockObject $token */
        $token = $this->getMockBuilder(TokenInterface::class)->getMock();
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        /* @var OrganizationalContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMockBuilder(OrganizationalContextInterface::class)->getMock();
        $context->expects($this->once())
            ->method('getCurrentOrganization')
            ->willReturn($org);
        $context->expects($this->once())
            ->method('getCurrentOrganizationUser')
            ->willReturn($orgUser);

        /* @var RoleHierarchyInterface|\PHPUnit_Framework_MockObject_MockObject $roleHierarchy */
        $roleHierarchy = $this->getMockBuilder(RoleHierarchyInterface::class)->getMock();
        $roleHierarchy->expects($this->once())
            ->method('getReachableRoles')
            ->willReturnCallback(function ($value) {
                return $value;
            });

        $sids = OrganizationSecurityIdentity::fromToken($token, $context, $roleHierarchy);

        $this->assertCount(4, $sids);
        $this->assertInstanceOf(OrganizationSecurityIdentity::class, $sids[0]);
        $this->assertSame('foo', $sids[0]->getIdentifier());
        $this->assertInstanceOf(GroupSecurityIdentity::class, $sids[1]);
        $this->assertSame('GROUP_ORG_USER_TEST', $sids[1]->getIdentifier());
        $this->assertInstanceOf(RoleSecurityIdentity::class, $sids[2]);
        $this->assertSame('ROLE_ORG_USER_TEST__FOO', $sids[2]->getIdentifier());
        $this->assertInstanceOf(RoleSecurityIdentity::class, $sids[3]);
        $this->assertSame('ROLE_ORG_TEST', $sids[3]->getIdentifier());
    }

    public function testFormTokenWithInvalidInterface()
    {
        /* @var AdvancedUserInterface|\PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->getMockBuilder(AdvancedUserInterface::class)->getMock();

        /* @var TokenInterface|\PHPUnit_Framework_MockObject_MockObject $token */
        $token = $this->getMockBuilder(TokenInterface::class)->getMock();
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $sids = OrganizationSecurityIdentity::fromToken($token);

        $this->assertCount(0, $sids);
    }
}