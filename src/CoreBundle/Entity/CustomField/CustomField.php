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

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\CoreBundle\Action\Api\CustomFieldReorderAction;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Enum\CustomFieldType;
use SolidInvoice\CoreBundle\Enum\CustomFieldVisibility;
use SolidInvoice\CoreBundle\Repository\CustomFieldRepository;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation as Serialize;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use function in_array;

#[ApiResource(
    shortName: 'CustomField',
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Patch(),
        new Delete(),
        new Post(
            uriTemplate: '/custom-fields/reorder',
            controller: CustomFieldReorderAction::class,
            read: false,
            deserialize: false,
            validate: false,
            write: false,
            name: 'custom_field_reorder',
        ),
    ],
    normalizationContext: [
        'groups' => ['custom_field:read'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
    denormalizationContext: [
        'groups' => ['custom_field:write'],
    ],
)]
#[ApiFilter(SearchFilter::class, properties: ['target' => 'exact'])]
#[ORM\Table(name: CustomField::TABLE_NAME)]
#[ORM\Index(columns: ['company_id', 'target', 'position'], name: 'idx_cf_company_target_pos')]
#[ORM\UniqueConstraint(name: 'uq_cf_company_target_key', columns: ['company_id', 'target', 'field_key'])]
#[ORM\Entity(repositoryClass: CustomFieldRepository::class)]
#[UniqueEntity(fields: ['company', 'target', 'label'], message: 'A custom field with this label already exists for the selected target.', errorPath: 'label')]
#[UniqueEntity(fields: ['company', 'target', 'fieldKey'], message: 'A custom field with a similar label already exists for the selected target.', errorPath: 'label')]
class CustomField
{
    final public const string TABLE_NAME = 'custom_field';

    use TimeStampable;
    use CompanyAware;

    #[ORM\Column(type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    #[Serialize\Groups(['custom_field:read'])]
    private ?Ulid $id = null;

    #[ORM\Column(name: 'target', type: Types::STRING, length: 32, enumType: CustomFieldTarget::class)]
    #[Assert\NotNull]
    #[Serialize\Groups(['custom_field:read', 'custom_field:write'])]
    private ?CustomFieldTarget $target = null;

    #[ORM\Column(type: Types::STRING, length: 125)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 125)]
    #[Serialize\Groups(['custom_field:read', 'custom_field:write'])]
    private ?string $label = null;

    #[ORM\Column(name: 'field_key', type: Types::STRING, length: 64)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    #[Assert\Regex(pattern: '/^[a-z][a-z0-9_]*$/', message: 'Field key must start with a lowercase letter and contain only lowercase letters, digits, and underscores.')]
    #[Serialize\Groups(['custom_field:read', 'custom_field:write'])]
    private ?string $fieldKey = null;

    #[ORM\Column(name: 'type', type: Types::STRING, length: 32, enumType: CustomFieldType::class)]
    #[Assert\NotNull]
    #[Serialize\Groups(['custom_field:read', 'custom_field:write'])]
    private ?CustomFieldType $type = null;

    /**
     * @var list<array{value: string, label: string}>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Serialize\Groups(['custom_field:read', 'custom_field:write'])]
    private ?array $options = null;

    #[ORM\Column(name: 'default_value', type: Types::TEXT, nullable: true)]
    #[Serialize\Groups(['custom_field:read', 'custom_field:write'])]
    private ?string $defaultValue = null;

    #[ORM\Column(name: 'visibility', type: Types::STRING, length: 32, nullable: true, enumType: CustomFieldVisibility::class)]
    #[Serialize\Groups(['custom_field:read', 'custom_field:write'])]
    private ?CustomFieldVisibility $visibility = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Serialize\Groups(['custom_field:read', 'custom_field:write'])]
    private bool $required = false;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    #[Serialize\Groups(['custom_field:read'])]
    private int $position = 0;

    public function __construct()
    {
        $this->id = new Ulid();
    }

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
        $this->fieldKey = new AsciiSlugger()->slug($label, '_')->lower()->toString();

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

    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    public function setDefaultValue(?string $defaultValue): self
    {
        $this->defaultValue = ($defaultValue === null || $defaultValue === '') ? null : $defaultValue;

        return $this;
    }

    public function getVisibility(): ?CustomFieldVisibility
    {
        return $this->visibility;
    }

    public function setVisibility(?CustomFieldVisibility $visibility): self
    {
        $this->visibility = $visibility;

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

    #[Assert\Callback]
    public function validateOptions(ExecutionContextInterface $context): void
    {
        if (! in_array($this->type, [CustomFieldType::SELECT, CustomFieldType::MULTI_SELECT], true)) {
            return;
        }

        if ($this->options === null || $this->options === []) {
            $context->buildViolation('At least one option is required for select fields.')
                ->atPath('options')
                ->addViolation();
        }
    }

    #[Assert\Callback]
    public function validateVisibility(ExecutionContextInterface $context): void
    {
        if (! $this->target instanceof CustomFieldTarget) {
            return;
        }

        if ($this->target->supportsVisibility()) {
            if (! $this->visibility instanceof CustomFieldVisibility) {
                $context->buildViolation('Visibility is required for invoice and quote custom fields.')
                    ->atPath('visibility')
                    ->addViolation();
            }

            return;
        }

        if ($this->visibility instanceof CustomFieldVisibility) {
            $context->buildViolation('Visibility only applies to invoice and quote custom fields.')
                ->atPath('visibility')
                ->addViolation();
        }
    }
}
