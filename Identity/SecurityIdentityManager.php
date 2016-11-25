<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Security\Identity;

use Sonatra\Component\Security\Event\AddSecurityIdentityEvent;
use Sonatra\Component\Security\Event\PostSecurityIdentityEvent;
use Sonatra\Component\Security\Event\PreSecurityIdentityEvent;
use Sonatra\Component\Security\SecurityIdentityEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;

/**
 * Manager to retrieving security identities.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class SecurityIdentityManager implements SecurityIdentityManagerInterface
{
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var RoleHierarchyInterface
     */
    protected $roleHierarchy;

    /**
     * @var AuthenticationTrustResolverInterface
     */
    protected $authenticationTrustResolver;

    /**
     * Constructor.
     *
     * @param EventDispatcherInterface             $dispatcher                  The event dispatcher
     * @param RoleHierarchyInterface               $roleHierarchy               The role hierarchy
     * @param AuthenticationTrustResolverInterface $authenticationTrustResolver The authentication trust resolver
     */
    public function __construct(EventDispatcherInterface $dispatcher,
                                RoleHierarchyInterface $roleHierarchy,
                                AuthenticationTrustResolverInterface $authenticationTrustResolver)
    {
        $this->dispatcher = $dispatcher;
        $this->roleHierarchy = $roleHierarchy;
        $this->authenticationTrustResolver = $authenticationTrustResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityIdentities(TokenInterface $token = null)
    {
        $sids = array();

        if (null === $token) {
            return $sids;
        }

        // dispatch pre event
        $eventPre = new PreSecurityIdentityEvent($token, $sids);
        $this->dispatcher->dispatch(SecurityIdentityEvents::RETRIEVAL_PRE, $eventPre);

        // add current user and reachable roles
        $sids = $this->addCurrentUser($token, $sids);
        $sids = $this->addReachableRoles($token, $sids);

        // dispatch add event to add custom security identities
        $eventAdd = new AddSecurityIdentityEvent($token, $sids);
        $this->dispatcher->dispatch(SecurityIdentityEvents::RETRIEVAL_ADD, $eventAdd);
        $sids = $eventAdd->getSecurityIdentities();

        // add special roles
        $sids = $this->addSpecialRoles($token, $sids);

        // dispatch post event
        $eventPost = new PostSecurityIdentityEvent($token, $sids, $eventPre->isPermissionEnabled());
        $this->dispatcher->dispatch(SecurityIdentityEvents::RETRIEVAL_POST, $eventPost);

        return $sids;
    }

    /**
     * Add the security identity of current user.
     *
     * @param TokenInterface              $token The token
     * @param SecurityIdentityInterface[] $sids  The security identities
     *
     * @return SecurityIdentityInterface[]
     */
    protected function addCurrentUser(TokenInterface $token, array $sids)
    {
        if (!$token instanceof AnonymousToken) {
            try {
                $sids[] = UserSecurityIdentity::fromToken($token);
            } catch (\InvalidArgumentException $e) {
                // ignore, user has no user security identity
            }
        }

        return $sids;
    }

    /**
     * Add the security identities of reachable roles.
     *
     * @param TokenInterface              $token The token
     * @param SecurityIdentityInterface[] $sids  The security identities
     *
     * @return SecurityIdentityInterface[]
     */
    protected function addReachableRoles(TokenInterface $token, array $sids)
    {
        foreach ($this->roleHierarchy->getReachableRoles($token->getRoles()) as $role) {
            $sids[] = RoleSecurityIdentity::fromAccount($role);
        }

        return $sids;
    }

    /**
     * Add the security identities of special roles.
     *
     * @param TokenInterface              $token The token
     * @param SecurityIdentityInterface[] $sids  The security identities
     *
     * @return SecurityIdentityInterface[]
     */
    protected function addSpecialRoles(TokenInterface $token, array $sids)
    {
        if ($this->authenticationTrustResolver->isFullFledged($token)) {
            $sids[] = new RoleSecurityIdentity(AuthenticatedVoter::IS_AUTHENTICATED_FULLY);
            $sids[] = new RoleSecurityIdentity(AuthenticatedVoter::IS_AUTHENTICATED_REMEMBERED);
            $sids[] = new RoleSecurityIdentity(AuthenticatedVoter::IS_AUTHENTICATED_ANONYMOUSLY);
        } elseif ($this->authenticationTrustResolver->isRememberMe($token)) {
            $sids[] = new RoleSecurityIdentity(AuthenticatedVoter::IS_AUTHENTICATED_REMEMBERED);
            $sids[] = new RoleSecurityIdentity(AuthenticatedVoter::IS_AUTHENTICATED_ANONYMOUSLY);
        } elseif ($this->authenticationTrustResolver->isAnonymous($token)) {
            $sids[] = new RoleSecurityIdentity(AuthenticatedVoter::IS_AUTHENTICATED_ANONYMOUSLY);
        }

        return $sids;
    }
}