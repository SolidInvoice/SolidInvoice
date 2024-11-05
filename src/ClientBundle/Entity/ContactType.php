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

namespace SolidInvoice\ClientBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\ClientBundle\Repository\ContactTypeRepository;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use Stringable;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation as Serialize;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: ContactType::TABLE_NAME)]
#[ORM\Entity(repositoryClass: ContactTypeRepository::class)]
#[ORM\UniqueConstraint(columns: ['name', 'company_id'])]
#[UniqueEntity(fields: ['name', 'company'])]
class ContactType implements Stringable
{
    final public const TABLE_NAME = 'contact_types';

    use CompanyAware;

    #[ORM\Column(name: 'id', type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    #[Serialize\Groups(['contact_api'])]
    private ?Ulid $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 45)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 45)]
    #[Serialize\Groups(['contact_api'])]
    private ?string $name = null;

    #[ORM\Column(name: 'type', type: Types::STRING, length: 45)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 45)]
    #[Serialize\Groups(['none'])]
    private string $type = 'text';

    /**
     * @var array<int|string, int|string|list<string>>|null
     */
    #[ORM\Column(name: 'field_options', type: 'array', nullable: true)]
    #[Serialize\Groups(['none'])]
    private ?array $options = [];

    #[ORM\Column(name: 'required', type: Types::BOOLEAN)]
    #[Serialize\Groups(['none'])]
    private bool $required = false;

    /**
     * @var Collection<int, AdditionalContactDetail>
     */
    #[ORM\OneToMany(mappedBy: 'type', targetEntity: AdditionalContactDetail::class, orphanRemoval: true)]
    #[Serialize\Groups(['none'])]
    private Collection $details;

    public function __construct()
    {
        $this->details = new ArrayCollection();
    }

    public function getId(): Ulid
    {
        return $this->id;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setRequired(bool $required): self
    {
        $this->required = $required;

        return $this;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function addDetail(AdditionalContactDetail $detail): self
    {
        $this->details[] = $detail;
        $detail->setType($this);

        return $this;
    }

    /**
     * @return Collection<int, AdditionalContactDetail>
     */
    public function getDetails(): Collection
    {
        return $this->details;
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

    /**
     * @return array<int|string, int|string|list<string>>
     */
    public function getOptions(): array
    {
        return $this->options ?? [];
    }

    /**
     * @param array<int|string, int|string|list<string>> $options
     */
    public function setOptions(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }
}
