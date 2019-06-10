<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Security\Tests\Doctrine\ORM\Event;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\ORM\Query\FilterCollection;
use Fxp\Component\Security\Doctrine\ORM\Event\GetNoneFilterEvent;
use Fxp\Component\Security\Tests\Fixtures\Model\MockSharing;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class GetNoneFilterEventTest extends TestCase
{
    /**
     * @var EntityManagerInterface|MockObject
     */
    protected $entityManager;

    /**
     * @var Connection|MockObject
     */
    protected $connection;

    /**
     * @var ClassMetadata|MockObject
     */
    protected $targetEntity;

    /**
     * @var MockObject|SQLFilter
     */
    protected $filter;

    /**
     * @var GetNoneFilterEvent
     */
    protected $event;

    /**
     * @throws
     */
    protected function setUp(): void
    {
        $this->entityManager = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
        $this->connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
        $this->targetEntity = $this->getMockBuilder(ClassMetadata::class)->disableOriginalConstructor()->getMock();
        $this->filter = $this->getMockForAbstractClass(SQLFilter::class, [$this->entityManager]);

        $this->entityManager->expects($this->any())
            ->method('getFilters')
            ->willReturn(new FilterCollection($this->entityManager))
        ;

        $this->entityManager->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connection)
        ;

        $this->entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($this->targetEntity)
        ;

        $this->connection->expects($this->any())
            ->method('quote')
            ->willReturnCallback(static function ($v) {
                return '\''.$v.'\'';
            })
        ;

        $this->event = new GetNoneFilterEvent(
            $this->filter,
            $this->entityManager,
            $this->targetEntity,
            't0',
            MockSharing::class
        );
    }

    public function testGetters(): void
    {
        $this->assertSame($this->entityManager, $this->event->getEntityManager());
        $this->assertSame($this->entityManager->getConnection(), $this->event->getConnection());
        $this->assertSame($this->entityManager->getClassMetadata(MockSharing::class), $this->event->getClassMetadata(MockSharing::class));
        $this->assertSame($this->entityManager->getClassMetadata(MockSharing::class), $this->event->getSharingClassMetadata());
        $this->assertSame($this->targetEntity, $this->event->getTargetEntity());
        $this->assertSame('t0', $this->event->getTargetTableAlias());
    }

    /**
     * @throws
     */
    public function testSetParameter(): void
    {
        $this->assertFalse($this->event->hasParameter('foo'));
        $this->event->setParameter('foo', true, 'boolean');
        $this->assertSame('\'1\'', $this->event->getParameter('foo'));
        $this->assertTrue($this->event->getRealParameter('foo'));
    }

    public function testSetFilterConstraint(): void
    {
        $this->assertSame('', $this->event->getFilterConstraint());

        $this->event->setFilterConstraint('TEST_FILTER');

        $this->assertSame('TEST_FILTER', $this->event->getFilterConstraint());
    }
}