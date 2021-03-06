<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Security\CacheWarmer;

use Psr\Container\ContainerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
abstract class AbstractCacheWarmer implements CacheWarmerInterface, ServiceSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var null|object
     */
    private $cacheService;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir): void
    {
        if (null === $this->cacheService) {
            $this->cacheService = $this->container->get(array_keys(static::getSubscribedServices())[0]);
        }

        if ($this->cacheService instanceof WarmableInterface) {
            $this->cacheService->warmUp($cacheDir);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional(): bool
    {
        return true;
    }
}
