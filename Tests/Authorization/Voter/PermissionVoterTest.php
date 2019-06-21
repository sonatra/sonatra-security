<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Security\Tests\Authorization\Voter;

use Fxp\Component\Security\Authorization\Voter\PermissionVoter;
use Fxp\Component\Security\Identity\RoleSecurityIdentity;
use Fxp\Component\Security\Identity\SecurityIdentityManagerInterface;
use Fxp\Component\Security\Permission\FieldVote;
use Fxp\Component\Security\Permission\PermissionManagerInterface;
use Fxp\Component\Security\Tests\Fixtures\Model\MockObject;
use Fxp\Component\Security\Tests\Fixtures\Model\MockRole;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class PermissionVoterTest extends TestCase
{
    /**
     * @var PermissionManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $permManager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|SecurityIdentityManagerInterface
     */
    protected $sidManager;

    /**
     * @var TokenInterface
     */
    protected $token;

    /**
     * @var PermissionVoter
     */
    protected $voter;

    protected function setUp(): void
    {
        $this->permManager = $this->getMockBuilder(PermissionManagerInterface::class)->getMock();
        $this->sidManager = $this->getMockBuilder(SecurityIdentityManagerInterface::class)->getMock();
        $this->token = $this->getMockBuilder(TokenInterface::class)->getMock();

        $this->permManager->expects(static::any())
            ->method('isEnabled')
            ->willReturn(true)
        ;

        $this->voter = new PermissionVoter(
            $this->permManager,
            $this->sidManager
        );
    }

    public function getVoteAttributes(): array
    {
        $class = MockObject::class;
        $object = new MockObject('foo');
        $fieldVote = new FieldVote($object, 'name');
        $arrayValid = [$object, 'name'];
        $arrayInvalid = [$object];

        return [
            [[42], $class, VoterInterface::ACCESS_ABSTAIN],
            [[42], $object, VoterInterface::ACCESS_ABSTAIN],
            [[42], $fieldVote, VoterInterface::ACCESS_ABSTAIN],
            [[42], $arrayValid, VoterInterface::ACCESS_ABSTAIN],
            [[42], $arrayInvalid, VoterInterface::ACCESS_ABSTAIN],
            [['view'], $class, VoterInterface::ACCESS_ABSTAIN],
            [['view'], $object, VoterInterface::ACCESS_ABSTAIN],
            [['view'], $fieldVote, VoterInterface::ACCESS_ABSTAIN],
            [['view'], $arrayValid, VoterInterface::ACCESS_ABSTAIN],
            [['view'], $arrayInvalid, VoterInterface::ACCESS_ABSTAIN],
            [['perm_view'], $class, VoterInterface::ACCESS_GRANTED, true],
            [['perm_view'], $object, VoterInterface::ACCESS_GRANTED, true],
            [['perm_view'], $object, VoterInterface::ACCESS_DENIED, false],
            [['perm_view'], $fieldVote, VoterInterface::ACCESS_GRANTED, true],
            [['perm_view'], $fieldVote, VoterInterface::ACCESS_DENIED, false],
            [['perm_view'], $arrayValid, VoterInterface::ACCESS_GRANTED, true],
            [['perm_view'], $arrayValid, VoterInterface::ACCESS_DENIED, false],
            [['perm_view'], $arrayInvalid, VoterInterface::ACCESS_ABSTAIN],
            [['foo'], null, VoterInterface::ACCESS_ABSTAIN],
            [['perm_foo'], null, VoterInterface::ACCESS_GRANTED, true],
            [['perm_foo'], null, VoterInterface::ACCESS_DENIED, false],
        ];
    }

    /**
     * @dataProvider getVoteAttributes
     *
     * @param array     $attributes        The attributes
     * @param mixed     $subject           The subject
     * @param int       $result            The expected result
     * @param null|bool $permManagerResult The result of permission manager
     * @param bool      $isManaged         Check if the subject is managed
     */
    public function testVote(array $attributes, $subject, $result, $permManagerResult = null, $isManaged = true): void
    {
        $sids = [
            new RoleSecurityIdentity(MockRole::class, 'ROLE_USER'),
        ];

        if (null !== $permManagerResult) {
            $this->sidManager->expects(static::once())
                ->method('getSecurityIdentities')
                ->with($this->token)
                ->willReturn($sids)
            ;

            if (\is_array($subject) && isset($subject[0], $subject[1])) {
                $expectedSubject = new FieldVote($subject[0], $subject[1]);

                if (null !== $subject) {
                    $this->permManager->expects(static::once())
                        ->method('isManaged')
                        ->with($expectedSubject)
                        ->willReturn($isManaged)
                    ;
                }

                $this->permManager->expects(static::once())
                    ->method('isGranted')
                    ->with($sids, substr($attributes[0], 5), $expectedSubject)
                    ->willReturn($permManagerResult)
                ;
            } else {
                if (null !== $subject) {
                    $this->permManager->expects(static::once())
                        ->method('isManaged')
                        ->with($subject)
                        ->willReturn($isManaged)
                    ;
                }

                $this->permManager->expects(static::once())
                    ->method('isGranted')
                    ->with($sids, substr($attributes[0], 5), $subject)
                    ->willReturn($permManagerResult)
                ;
            }
        }

        static::assertSame($result, $this->voter->vote($this->token, $subject, $attributes));
    }
}
