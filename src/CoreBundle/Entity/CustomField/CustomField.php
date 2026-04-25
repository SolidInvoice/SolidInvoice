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

namespace SolidInvoice\CoreBundle\Entity\CustomField;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Enum\CustomFieldType;
use SolidInvoice\CoreBundle\Repository\CustomFieldRepository;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: CustomField::TABLE_NAME)]
#[ORM\Index(name: 'idx_cf_company_target_pos', columns: ['company_id', 'target', 'position'])]
#[ORM\UniqueConstraint(name: 'uq_cf_company_target_key', columns: ['company_id', 'target', 'field_key'])]
#[ORM\Entity(repositoryClass: CustomFieldRepository::class)]
class CustomField
{
    final public const TABLE_NAME = 'custom_field';

    use TimeStampable;
    use CompanyAware;

    #[ORM\Column(type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    private ?Ulid $id = null;

    #[ORM\Column(name: 'target', type: Types::STRING, length: 32, enumType: CustomFieldTarget::class)]
    #[Assert\NotNull]
    private ?CustomFieldTarget $target = null;

    #[ORM\Column(type: Types::STRING, length: 125)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 125)]
    private ?string $label = null;

    #[ORM\Column(name: 'field_key', type: Types::STRING, length: 64)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    #[Assert\Regex(pattern: '/^[a-z][a-z0-9_]*$/', message: 'Field key must start with a lowercase letter and contain only lowercase letters, digits, and underscores.')]
    private ?string $fieldKey = null;

    #[ORM\Column(name: 'type', type: Types::STRING, length: 32, enumType: CustomFieldType::class)]
    #[Assert\NotNull]
    private ?CustomFieldType $type = null;

    /**
     * @var list<array{value: string, label: string}>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $options = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $required = false;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $position = 0;

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getTarget(): ?CustomFieldTarget
    {
        return $this->target;
    }

    public function setTarget(CustomFieldTarget $target): self
    {
        $this->target = $target;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getFieldKey(): ?string
    {
        return $this->fieldKey;
    }

    public function setFieldKey(string $key): self
    {
        $this->fieldKey = $key;

        return $this;
    }

    public function getType(): ?CustomFieldType
    {
        return $this->type;
    }

    public function setType(CustomFieldType $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return list<array{value: string, label: string}>|null
     */
    public function getOptions(): ?array
    {
        return $this->options;
    }

    /**
     * @param list<array{value: string, label: string}>|null $options
     */
    public function setOptions(?array $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): self
    {
        $this->required = $required;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }
}
