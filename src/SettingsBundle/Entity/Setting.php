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
use Doctrine\ORM\Mapping as ORM;
use Serializable;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\SettingsBundle\Repository\SettingsRepository;
use Stringable;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Ulid;

#[ORM\Table(name: Setting::TABLE_NAME)]
#[ORM\UniqueConstraint(columns: ['setting_key', 'company_id'])]
#[ORM\Entity(repositoryClass: SettingsRepository::class)]
#[UniqueEntity(fields: ['company_id', 'key'])]
class Setting implements Stringable, Serializable
{
    final public const TABLE_NAME = 'app_config';

    use CompanyAware;

    #[ORM\Column(name: 'id', type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    private ?Ulid $id = null;

    #[ORM\Column(name: 'setting_key', type: Types::STRING, length: 125)]
    protected ?string $key = null;

    #[ORM\Column(name: 'setting_value', type: Types::TEXT, nullable: true)]
    protected ?string $value = null;

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(name: 'field_type', type: Types::STRING)]
    protected ?string $type = null;

    public function getId(): ?Ulid
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

    public function getValue(): ?string
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

    public function __serialize(): array
    {
        return [
            $this->id,
            $this->key,
            $this->value,
            $this->description,
            $this->type,
        ];
    }

    /**
     * @param array{0: Ulid|null, 1: string|null, 2: string|null, 3: string|null, 4: string|null} $data
     */
    public function __unserialize(array $data): void
    {
        [
            $this->id,
            $this->key,
            $this->value,
            $this->description,
            $this->type,
        ] = $data;
    }

    public function serialize(): string
    {
        return serialize($this->__serialize());
    }

    public function unserialize(string $data): void
    {
        $this->__unserialize(unserialize($data));
    }
}
