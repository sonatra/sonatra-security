<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Security\Permission;

use Fxp\Component\Security\CacheWarmer\AbstractCacheWarmer;

/**
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class PermissionFactoryCacheWarmer extends AbstractCacheWarmer
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return [
            'fxp_security.permission_factory' => PermissionFactoryInterface::class,
        ];
    }
}
