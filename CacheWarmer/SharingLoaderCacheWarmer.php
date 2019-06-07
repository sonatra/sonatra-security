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

use Fxp\Component\Security\Sharing\Loader\CacheLoader;

/**
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class SharingLoaderCacheWarmer extends AbstractLoaderCacheWarmer
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return [
            'fxp_security.sharing_loader.cache' => CacheLoader::class,
        ];
    }
}
