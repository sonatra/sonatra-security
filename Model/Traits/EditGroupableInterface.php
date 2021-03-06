<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Security\Model\Traits;

use Fxp\Component\Security\Model\GroupInterface;

/**
 * Edit Groupable interface.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
interface EditGroupableInterface extends GroupableInterface
{
    /**
     * Add a group to the user groups.
     *
     * @param GroupInterface $group
     *
     * @return static
     */
    public function addGroup(GroupInterface $group);

    /**
     * Remove a group from the user groups.
     *
     * @param GroupInterface $group
     *
     * @return static
     */
    public function removeGroup(GroupInterface $group);
}
