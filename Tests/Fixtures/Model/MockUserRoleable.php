<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Security\Tests\Fixtures\Model;

use Fxp\Component\Security\Model\Traits\RoleableTrait;
use Fxp\Component\Security\Model\UserInterface;

/**
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class MockUserRoleable implements UserInterface
{
    use RoleableTrait;

    /**
     * {@inheritdoc}
     */
    public function getId(): int
    {
        return 50;
    }

    /**
     * {@inheritdoc}
     */
    public function isAccountNonExpired(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isAccountNonLocked(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isCredentialsNonExpired(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword(): string
    {
        return 'password';
    }

    /**
     * {@inheritdoc}
     */
    public function getSalt(): string
    {
        return 'salt';
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername(): string
    {
        return 'user.test';
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials(): void
    {
        // do nothing
    }
}
