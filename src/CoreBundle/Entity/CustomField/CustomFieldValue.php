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
use SolidInvoice\CoreBundle\Repository\CustomFieldValueRepository;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Table(name: CustomFieldValue::TABLE_NAME)]
#[ORM\Index(columns: ['company_id', 'target', 'target_id'], name: 'idx_cfv_company_target_record')]
#[ORM\Index(columns: ['field_id'], name: 'idx_cfv_field')]
#[ORM\UniqueConstraint(name: 'uq_cfv_field_record', columns: ['field_id', 'target_id'])]
#[ORM\Entity(repositoryClass: CustomFieldValueRepository::class)]
class CustomFieldValue
{
    final public const string TABLE_NAME = 'custom_field_value';

    use TimeStampable;
    use CompanyAware;

    #[ORM\Column(type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    private ?Ulid $id = null;

    #[ORM\ManyToOne(targetEntity: CustomField::class)]
    #[ORM\JoinColumn(name: 'field_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?CustomField $field = null;

    #[ORM\Column(name: 'target', type: Types::STRING, length: 32, enumType: CustomFieldTarget::class)]
    private ?CustomFieldTarget $target = null;

    #[ORM\Column(name: 'target_id', type: UlidType::NAME)]
    private ?Ulid $targetId = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $value = null;

    public function __construct()
    {
        $this->id = new Ulid();
    }

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getField(): ?CustomField
    {
        return $this->field;
    }

    public function setField(CustomField $field): self
    {
        $this->field = $field;

        return $this;
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

    public function getTargetId(): ?Ulid
    {
        return $this->targetId;
    }

    public function setTargetId(Ulid $targetId): self
    {
        $this->targetId = $targetId;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): self
    {
        $this->value = $value;

        return $this;
    }
}
