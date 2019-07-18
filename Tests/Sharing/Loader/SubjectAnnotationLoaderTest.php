<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Security\Tests\Sharing\Loader;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Fxp\Component\Config\Loader\ClassFinder;
use Fxp\Component\Security\Sharing\Loader\SubjectAnnotationLoader;
use Fxp\Component\Security\SharingVisibilities;
use Fxp\Component\Security\Tests\Fixtures\Model\MockObjectWithAnnotation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class SubjectAnnotationLoaderTest extends TestCase
{
    protected function setUp(): void
    {
        AnnotationRegistry::registerLoader('class_exists');
    }

    public function testSupports(): void
    {
        $reader = new AnnotationReader();
        $loader = new SubjectAnnotationLoader($reader);

        static::assertTrue($loader->supports(__DIR__, 'annotation'));
        static::assertFalse($loader->supports(__DIR__, 'config'));
        static::assertFalse($loader->supports(new \stdClass(), 'annotation'));
    }

    /**
     * @throws
     */
    public function testLoad(): void
    {
        /** @var ClassFinder|MockObject $finder */
        $finder = $this->getMockBuilder(ClassFinder::class)
            ->setMethods(['findClasses'])
            ->getMock()
        ;

        $finder->expects(static::once())
            ->method('findClasses')
            ->willReturn([
                MockObjectWithAnnotation::class,
                'InvalidClass',
            ])
        ;

        $reader = new AnnotationReader();
        $loader = new SubjectAnnotationLoader($reader, $finder);

        $configs = $loader->load(__DIR__, 'annotation');

        static::assertCount(1, $configs);

        $config = current($configs->all());
        static::assertSame(MockObjectWithAnnotation::class, $config->getType());
        static::assertSame(SharingVisibilities::TYPE_PRIVATE, $config->getVisibility());
    }
}
