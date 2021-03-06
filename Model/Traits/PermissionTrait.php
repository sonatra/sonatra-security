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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Fxp\Component\Security\Model\RoleInterface;

/**
 * Trait of permission model.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
trait PermissionTrait
{
    /**
     * @var string[]
     *
     * @ORM\Column(type="json", nullable=true)
     */
    protected $contexts = [];

    /**
     * @var null|string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $class;

    /**
     * @var null|string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $field;

    /**
     * @var null|string
     *
     * @ORM\Column(type="string", length=255)
     */
    protected $operation;

    /**
     * @var null|Collection|RoleInterface[]
     *
     * @ORM\ManyToMany(targetEntity="Fxp\Component\Security\Model\RoleInterface", mappedBy="permissions")
     */
    protected $roles;

    /**
     * {@inheritdoc}
     */
    public function setOperation(?string $operation): self
    {
        $this->operation = $operation;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getOperation(): ?string
    {
        return $this->operation;
    }

    /**
     * {@inheritdoc}
     */
    public function setContexts(array $contexts): self
    {
        $this->contexts = $contexts;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getContexts(): array
    {
        return $this->contexts;
    }

    /**
     * {@inheritdoc}
     */
    public function setClass(?string $class): self
    {
        $this->class = $class;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getClass(): ?string
    {
        return $this->class;
    }

    /**
     * {@inheritdoc}
     */
    public function setField(?string $field): self
    {
        $this->field = $field;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getField(): ?string
    {
        return $this->field;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles(): Collection
    {
        return $this->roles ?: $this->roles = new ArrayCollection();
    }
}
