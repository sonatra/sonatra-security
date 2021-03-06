<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Security\Sharing;

use Fxp\Component\Config\Cache\AbstractCache;
use Fxp\Component\Config\ConfigCollectionInterface;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;

/**
 * Cache sharing factory.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class CacheSharingFactory extends AbstractCache implements SharingFactoryInterface, WarmableInterface
{
    /**
     * @var SharingFactoryInterface
     */
    protected $factory;

    /**
     * Constructor.
     *
     * @param SharingFactoryInterface $factory The sharing factory
     * @param array                   $options An array of options
     */
    public function __construct(SharingFactoryInterface $factory, array $options = [])
    {
        parent::__construct($options);

        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     *
     * @return ConfigCollectionInterface|SharingSubjectConfigCollection
     */
    public function createSubjectConfigurations(): SharingSubjectConfigCollection
    {
        if (null === $this->options['cache_dir'] || $this->options['debug']) {
            return $this->factory->createSubjectConfigurations();
        }

        return $this->loadConfigurationFromCache('sharing_subject', function () {
            return $this->factory->createSubjectConfigurations();
        });
    }

    /**
     * {@inheritdoc}
     *
     * @return ConfigCollectionInterface|SharingIdentityConfigCollection
     */
    public function createIdentityConfigurations(): SharingIdentityConfigCollection
    {
        if (null === $this->options['cache_dir'] || $this->options['debug']) {
            return $this->factory->createIdentityConfigurations();
        }

        return $this->loadConfigurationFromCache('sharing_identity', function () {
            return $this->factory->createIdentityConfigurations();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir): void
    {
        // skip warmUp when the config doesn't use cache
        if (null === $this->options['cache_dir']) {
            return;
        }

        $this->createSubjectConfigurations();
        $this->createIdentityConfigurations();
    }
}
