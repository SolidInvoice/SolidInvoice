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

use Doctrine\DBAL\Types\Types;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use SolidInvoice\SettingsBundle\Repository\SettingsRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidOrderedTimeGenerator;
use Ramsey\Uuid\UuidInterface;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use Stringable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Table(name: Setting::TABLE_NAME)]
#[ORM\UniqueConstraint(columns: ['setting_key', 'company_id'])]
#[ORM\Entity(repositoryClass: SettingsRepository::class)]
#[UniqueEntity(fields: ['company_id', 'key'])]
class Setting implements Stringable
{
    final public const TABLE_NAME = 'app_config';

    use CompanyAware;

    #[ORM\Column(name: 'id', type: UuidBinaryOrderedTimeType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidOrderedTimeGenerator::class)]
    private ?UuidInterface $id = null;

    #[ORM\Column(name: 'setting_key', type: Types::STRING, length: 125)]
    protected ?string $key = null;

    #[ORM\Column(name: 'setting_value', type: Types::TEXT, nullable: true)]
    protected ?string $value = null;

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(name: 'field_type', type: Types::STRING)]
    protected ?string $type = null;

    public function getId(): ?UuidInterface
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
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    public function setValue(mixed $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

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
        return (string) $this->value;
    }
}
