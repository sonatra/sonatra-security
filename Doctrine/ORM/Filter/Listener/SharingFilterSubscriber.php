<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Security\Doctrine\ORM\Filter\Listener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Sonatra\Component\DoctrineExtensions\Filter\Listener\AbstractFilterSubscriber;
use Sonatra\Component\Security\Doctrine\ORM\Filter\SharingFilter;
use Sonatra\Component\Security\Identity\IdentityUtils;
use Sonatra\Component\Security\Identity\SecurityIdentityInterface;
use Sonatra\Component\Security\Identity\SecurityIdentityManagerInterface;
use Sonatra\Component\Security\Model\UserInterface;
use Sonatra\Component\Security\OrganizationalContextEvents;
use Sonatra\Component\Security\Sharing\SharingManagerInterface;
use Sonatra\Component\Security\SharingEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Sharing filter listener.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class SharingFilterSubscriber extends AbstractFilterSubscriber
{
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var SecurityIdentityManagerInterface
     */
    protected $sidManager;

    /**
     * @var SharingManagerInterface
     */
    protected $sharingManager;

    /**
     * @var string
     */
    protected $sharingClass;

    /**
     * Constructor.
     *
     * @param EntityManagerInterface           $entityManager  The entity manager
     * @param EventDispatcherInterface         $dispatcher     The event dispatcher
     * @param TokenStorageInterface            $tokenStorage   The token storage
     * @param SecurityIdentityManagerInterface $sidManager     The security identity manager
     * @param SharingManagerInterface          $sharingManager The sharing manager
     * @param string                           $sharingClass   The classname of sharing model
     */
    public function __construct(EntityManagerInterface $entityManager,
                                EventDispatcherInterface $dispatcher,
                                TokenStorageInterface $tokenStorage,
                                SecurityIdentityManagerInterface $sidManager,
                                SharingManagerInterface $sharingManager,
                                $sharingClass)
    {
        parent::__construct($entityManager);

        $this->dispatcher = $dispatcher;
        $this->tokenStorage = $tokenStorage;
        $this->sidManager = $sidManager;
        $this->sharingManager = $sharingManager;
        $this->sharingClass = $sharingClass;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array_merge(parent::getSubscribedEvents(), array(
            OrganizationalContextEvents::SET_CURRENT_ORGANIZATION => array(
                array('onEvent', 0),
            ),
            SharingEvents::ENABLED => array(
                array('onSharingManagerChange', 0),
            ),
            SharingEvents::DISABLED => array(
                array('onSharingManagerChange', 0),
            ),
        ));
    }

    /**
     * Action when the sharing manager is enabled or disabled.
     */
    public function onSharingManagerChange()
    {
        if (null !== ($filter = $this->getFilter())) {
            $filter->setParameter('sharing_manager_enabled', $this->sharingManager->isEnabled(), 'boolean');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function supports()
    {
        return SharingFilter::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function injectParameters(SQLFilter $filter)
    {
        /* @var SharingFilter $filter */
        $filter->setEventDispatcher($this->dispatcher);
        $filter->setSharingManager($this->sharingManager);
        $filter->setSharingClass($this->sharingClass);
        $sids = $this->buildSecurityIdentities();

        $filter->setParameter('has_security_identities', !empty($sids), 'boolean');
        $filter->setParameter('map_security_identities', $this->getMapSecurityIdentities($sids), 'array');
        $filter->setParameter('user_id', $this->getUserId());
        $filter->setParameter('sharing_manager_enabled', $this->sharingManager->isEnabled(), 'boolean');
    }

    /**
     * Build the security identities.
     *
     * @return SecurityIdentityInterface[]
     */
    private function buildSecurityIdentities()
    {
        $tSids = $this->sidManager->getSecurityIdentities($this->tokenStorage->getToken());
        $sids = array();

        foreach ($tSids as $sid) {
            if (IdentityUtils::isValid($sid)) {
                $sids[] = $sid;
            }
        }

        return $sids;
    }

    /**
     * Get the map of the security identities.
     *
     * @param SecurityIdentityInterface[] $sids The security identities
     *
     * @return array
     */
    private function getMapSecurityIdentities(array $sids)
    {
        $connection = $this->entityManager->getConnection();
        $mapSids = array();

        foreach ($sids as $sid) {
            $type = $this->sharingManager->getIdentityConfig($sid->getType())->getType();
            $mapSids[$type][] = $connection->quote($sid->getIdentifier());
        }

        foreach ($mapSids as $type => $ids) {
            $mapSids[$type] = implode(', ', $ids);
        }

        return $mapSids;
    }

    /**
     * Get the current user id.
     *
     * @return string|int|null
     */
    private function getUserId()
    {
        $id = null;

        if (null !== $this->tokenStorage && null !== $this->tokenStorage->getToken()) {
            $user = $this->tokenStorage->getToken()->getUser();

            if ($user instanceof UserInterface) {
                $id = $user->getId();
            }
        }

        return $id;
    }
}