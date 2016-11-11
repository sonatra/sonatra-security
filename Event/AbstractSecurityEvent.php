<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Component\Security\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * The abstract security event.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
abstract class AbstractSecurityEvent extends Event
{
    /**
     * @var bool
     */
    protected $aclEnabled = true;

    /**
     * Check if the acl is enabled.
     *
     * @return bool
     */
    public function isAclEnabled()
    {
        return $this->aclEnabled;
    }
}