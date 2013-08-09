<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\SecurityBundle\Acl\Domain;

use Sonatra\Bundle\SecurityBundle\Event\SecurityIdentityEvent;
use Sonatra\Bundle\SecurityBundle\Events;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Acl\Domain\SecurityIdentityRetrievalStrategy as BaseSecurityIdentityRetrievalStrategy;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Strategy for retrieving security identities.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class SecurityIdentityRetrievalStrategy extends BaseSecurityIdentityRetrievalStrategy
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * Set event dispatcher.
     *
     * @param EventDispatcherInterface $dispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->eventDispatcher = $dispatcher;
    }

    /**
     * @var RegistryInterface
     */
    private $registry;

    /**
     * Constructor
     *
     * @param RoleHierarchyInterface      $roleHierarchy
     * @param AuthenticationTrustResolver $authenticationTrustResolver
     * @param RegistryInterface           $registry
    */
    public function __construct(RoleHierarchyInterface $roleHierarchy,
            AuthenticationTrustResolver $authenticationTrustResolver,
            RegistryInterface $registry)
    {
        parent::__construct($roleHierarchy, $authenticationTrustResolver);

        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityIdentities(TokenInterface $token)
    {
        $sids = parent::getSecurityIdentities($token);
        $em = $this->registry->getManager();
        $filterIsEnabled = $em->getFilters()->isEnabled('sonatra_acl');

        if ($filterIsEnabled) {
            $em->getFilters()->disable('sonatra_acl');
        }

        // dispatch pre event
        if (null !== $this->eventDispatcher) {
            $event = new SecurityIdentityEvent();
            $event->setSecurityIdentities($sids);
            $event = $this->eventDispatcher->dispatch(Events::PRE_SECURITY_IDENTITY_RETRIEVAL, $event);
            $sids = $event->getSecurityIdentities();
        }

        // add group security identity
        if (!$token instanceof AnonymousToken) {
            try {
                $sids = array_merge($sids, GroupSecurityIdentity::fromToken($token));

            } catch (\InvalidArgumentException $invalid) {
                // ignore, group has no group security identity
            }
        }

        // dispatch post event
        if (null !== $this->eventDispatcher) {
            $event->setSecurityIdentities($sids);
            $event = $this->eventDispatcher->dispatch(Events::POST_SECURITY_IDENTITY_RETRIEVAL, $event);
            $sids = $event->getSecurityIdentities();
        }

        if ($filterIsEnabled) {
            $em->getFilters()->enable('sonatra_acl');
        }

        return $sids;
    }
}
