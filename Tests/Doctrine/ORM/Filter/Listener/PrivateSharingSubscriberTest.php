<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Security\Tests\Doctrine\ORM\Filter\Listener;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\ORM\Query\FilterCollection;
use Sonatra\Component\Security\Doctrine\ORM\Event\GetFilterEvent;
use Sonatra\Component\Security\Doctrine\ORM\Filter\Listener\PrivateSharingSubscriber;
use Sonatra\Component\Security\Model\Sharing;
use Sonatra\Component\Security\Tests\Fixtures\Model\MockObject;
use Sonatra\Component\Security\Tests\Fixtures\Model\MockObjectOwnerable;
use Sonatra\Component\Security\Tests\Fixtures\Model\MockObjectOwnerableOptional;
use Sonatra\Component\Security\Tests\Fixtures\Model\MockRole;
use Sonatra\Component\Security\Tests\Fixtures\Model\MockUserRoleable;

/**
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class PrivateSharingSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    /**
     * @var Connection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $connection;

    /**
     * @var ClassMetadata|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $targetEntity;

    /**
     * @var ClassMetadata|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $sharingMeta;

    /**
     * @var SQLFilter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $filter;

    /**
     * @var GetFilterEvent
     */
    protected $event;

    /**
     * @var PrivateSharingSubscriber
     */
    protected $listener;

    protected function setUp()
    {
        $this->entityManager = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
        $this->connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
        $this->targetEntity = $this->getMockBuilder(ClassMetadata::class)->disableOriginalConstructor()->getMock();
        $this->sharingMeta = $this->getMockBuilder(ClassMetadata::class)->disableOriginalConstructor()->getMock();
        $this->filter = $this->getMockForAbstractClass(SQLFilter::class, array($this->entityManager));
        $this->event = new GetFilterEvent(
            $this->filter,
            $this->entityManager,
            $this->targetEntity,
            't0',
            Sharing::class
        );
        $this->listener = new PrivateSharingSubscriber();

        $this->entityManager->expects($this->any())
            ->method('getFilters')
            ->willReturn(new FilterCollection($this->entityManager));

        $this->entityManager->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connection);

        $this->connection->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn($this->getMockForAbstractClass(AbstractPlatform::class));

        $this->entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with(Sharing::class)
            ->willReturn($this->sharingMeta);

        $this->connection->expects($this->any())
            ->method('quote')
            ->willReturnCallback(function ($v) {
                if (is_array($v)) {
                    return implode(', ', $v);
                }

                return '\''.$v.'\'';
            });

        $this->assertCount(1, $this->listener->getSubscribedEvents());
    }

    /**
     * {@inheritdoc}
     */
    protected function injectParameters($sharingEnabled = true, $userId = 42, array $mapSids = array())
    {
        $this->filter->setParameter('has_security_identities', !empty($mapSids), 'boolean');
        $this->filter->setParameter('map_security_identities', $mapSids, 'array');
        $this->filter->setParameter('user_id', $userId, 'integer');
        $this->filter->setParameter('sharing_manager_enabled', $sharingEnabled, 'boolean');
    }

    public function testGetFilter()
    {
        $this->injectParameters(true, 42, array(
            MockRole::class => '\'ROLE_USER\'',
            MockUserRoleable::class => '\'user.test\'',
        ));

        $this->targetEntity->expects($this->any())
            ->method('getName')
            ->willReturn(MockObject::class);

        $this->sharingMeta->expects($this->once())
            ->method('getTableName')
            ->willReturn('test_sharing');

        $this->sharingMeta->expects($this->atLeastOnce())
            ->method('getColumnName')
            ->willReturnCallback(function ($value) {
                $map = array(
                    'subjectClass' => 'subject_class',
                    'subjectId' => 'subject_id',
                    'identityClass' => 'identity_class',
                    'identityName' => 'identity_name',
                    'enabled' => 'enabled',
                    'startedAt' => 'started_at',
                    'endedAt' => 'ended_at',
                    'id' => 'id',
                );

                return isset($map[$value]) ? $map[$value] : null;
            });

        $validFilter = <<<SELECTCLAUSE
t0.id IN (SELECT
    s.subject_id
FROM
    test_sharing s
WHERE
    s.subject_class = 'Sonatra\Component\Security\Tests\Fixtures\Model\MockObject'
    AND s.enabled IS TRUE
    AND (s.started_at IS NULL OR s.started_at <= CURRENT_TIMESTAMP)
    AND (s.ended_at IS NULL OR s.ended_at >= CURRENT_TIMESTAMP)
    AND ((s.identity_class = 'Sonatra\Component\Security\Tests\Fixtures\Model\MockRole' AND s.identity_name IN ('ROLE_USER')) OR (s.identity_class = 'Sonatra\Component\Security\Tests\Fixtures\Model\MockUserRoleable' AND s.identity_name IN ('user.test')))
GROUP BY
    s.subject_id)
SELECTCLAUSE;

        $this->listener->getFilter($this->event);
        $this->assertSame($validFilter, $this->event->getFilterConstraint());
    }

    public function getCurrentUserValues()
    {
        return array(
            array(MockObjectOwnerable::class, false),
            array(MockObjectOwnerable::class, true),
            array(MockObjectOwnerableOptional::class, false),
            array(MockObjectOwnerableOptional::class, true),
        );
    }

    /**
     * @dataProvider getCurrentUserValues
     *
     * @param string $objectClass
     * @param bool   $withCurrentUser
     */
    public function testGetFilterWithOwnerableObject($objectClass, $withCurrentUser)
    {
        $this->injectParameters(
            true,
            $withCurrentUser ? 50 : null,
            array(
                MockRole::class => '\'ROLE_USER\'',
                MockUserRoleable::class => '\'user.test\'',
            )
        );

        $this->targetEntity->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn($objectClass);

        $this->targetEntity->expects($this->atLeastOnce())
            ->method('getAssociationMapping')
            ->willReturnCallback(function ($value) {
                $map = array(
                    'owner' => array(
                        'joinColumnFieldNames' => array(
                            'owner' => 'owner_id',
                        ),
                    ),
                );

                return isset($map[$value]) ? $map[$value] : null;
            });

        $this->sharingMeta->expects($this->once())
            ->method('getTableName')
            ->willReturn('test_sharing');

        $this->sharingMeta->expects($this->atLeastOnce())
            ->method('getColumnName')
            ->willReturnCallback(function ($value) {
                $map = array(
                    'subjectClass' => 'subject_class',
                    'subjectId' => 'subject_id',
                    'identityClass' => 'identity_class',
                    'identityName' => 'identity_name',
                    'enabled' => 'enabled',
                    'startedAt' => 'started_at',
                    'endedAt' => 'ended_at',
                    'id' => 'id',
                );

                return isset($map[$value]) ? $map[$value] : null;
            });

        $ownerFilter = $withCurrentUser
            ? 't0.owner_id = \'50\''
            : 't0.owner_id IS NULL';

        if ($withCurrentUser && $objectClass === MockObjectOwnerableOptional::class) {
            $ownerFilter .= ' OR t0.owner_id IS NULL';
        }

        $validFilter = <<<SELECTCLAUSE
{$ownerFilter}
    OR
(t0.id IN (SELECT
    s.subject_id
FROM
    test_sharing s
WHERE
    s.subject_class = '{$objectClass}'
    AND s.enabled IS TRUE
    AND (s.started_at IS NULL OR s.started_at <= CURRENT_TIMESTAMP)
    AND (s.ended_at IS NULL OR s.ended_at >= CURRENT_TIMESTAMP)
    AND ((s.identity_class = 'Sonatra\Component\Security\Tests\Fixtures\Model\MockRole' AND s.identity_name IN ('ROLE_USER')) OR (s.identity_class = 'Sonatra\Component\Security\Tests\Fixtures\Model\MockUserRoleable' AND s.identity_name IN ('user.test')))
GROUP BY
    s.subject_id))
SELECTCLAUSE;

        $this->listener->getFilter($this->event);
        $this->assertSame($validFilter, $this->event->getFilterConstraint());
    }
}
