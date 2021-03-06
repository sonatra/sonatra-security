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

use Doctrine\ORM\Mapping as ORM;
use Fxp\Component\Security\Model\OrganizationInterface;
use Fxp\Component\Security\Model\UserInterface;

/**
 * Trait for organization user model.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
trait OrganizationUserTrait
{
    /**
     * @var null|OrganizationInterface
     *
     * @ORM\ManyToOne(
     *     targetEntity="Fxp\Component\Security\Model\OrganizationInterface",
     *     fetch="EXTRA_LAZY",
     *     inversedBy="organizationUsers"
     * )
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     */
    protected $organization;

    /**
     * @var null|UserInterface
     *
     * @ORM\ManyToOne(
     *     targetEntity="Fxp\Component\Security\Model\UserInterface",
     *     fetch="EXTRA_LAZY",
     *     inversedBy="userOrganizations",
     *     cascade={"persist"}
     * )
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $user;

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->organization->getName().':'.$this->user->getUsername();
    }

    /**
     * {@inheritdoc}
     */
    public function setOrganization(OrganizationInterface $organization): self
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrganization(): ?OrganizationInterface
    {
        return $this->organization;
    }

    /**
     * {@inheritdoc}
     */
    public function setUser(UserInterface $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getUser(): ?UserInterface
    {
        return $this->user;
    }
}
