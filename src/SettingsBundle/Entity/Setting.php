<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\SettingsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\CoreBundle\Doctrine\Id\IdGenerator;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Table(
 *     name="app_config",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(columns={"setting_key", "company_id"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="SolidInvoice\SettingsBundle\Repository\SettingsRepository")
 * @UniqueEntity(fields={"company_id", "key"})
 */
class Setting
{
    use CompanyAware;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=IdGenerator::class)
     *
     * @var int|null
     */
    private $id;

    /**
     * @ORM\Column(name="setting_key", type="string", length=125)
     *
     * @var string|null
     */
    protected $key;

    /**
     * @ORM\Column(name="setting_value", type="text", nullable=true)
     *
     * @var string|null
     */
    protected $value;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     *
     * @var string|null
     */
    protected $description;

    /**
     * @ORM\Column(name="field_type", type="string")
     *
     * @var string|null
     */
    protected $type;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): self
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Get value.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set value.
     *
     * @param mixed $value
     */
    public function setValue($value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set description.
     *
     * @param string $description
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
