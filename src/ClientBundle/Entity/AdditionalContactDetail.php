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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use Stringable;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Serializer\Annotation as Serialize;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Uid\Ulid;

#[ORM\Table(name: AdditionalContactDetail::TABLE_NAME)]
#[ORM\Entity]
/*
AdditionalContactDetail has a deeply nested URL which requires a client id and contact id.
This does not work well with API Platform, so this process needs to be revisited.
#[ApiResource(
    uriTemplate: '/clients/{clientId}/contacts/{contactId}/additional_details',
    operations: [ new GetCollection(), new Post() ],
    uriVariables: [
        'clientId' => new Link(
            fromProperty: 'contact.client',
            toProperty: 'contact.client',
            fromClass: Contact::class,
        ),
        'contactId' => new Link(
            fromProperty: 'additionalContactDetails',
            fromClass: Contact::class,
        ),
    ],
    normalizationContext: [
        // 'groups' => ['contact_type_id'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
    denormalizationContext: [
        // 'groups' => ['contact_type_id'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ]
)]
#[ApiResource(
    uriTemplate: '/clients/{clientId}/contacts/{contactId}/additional_details/{id}',
    operations: [ new Get(), new Patch(), new Put() ],
    uriVariables: [
        'clientId' => new Link(
            fromProperty: 'contact.client',
            toProperty: 'contact.client',
            fromClass: Contact::class,
        ),
        'contactId' => new Link(
            fromProperty: 'additionalContactDetails',
            fromClass: Contact::class,
        ),
        'id' => new Link(
            fromClass: AdditionalContactDetail::class,
        ),
    ],
    normalizationContext: [
        // 'groups' => ['contact_type_id'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
    denormalizationContext: [
        // 'groups' => ['contact_type_id'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ]
)]*/
class AdditionalContactDetail implements Stringable
{
    final public const TABLE_NAME = 'contact_details';

    use TimeStampable;
    use CompanyAware;

    #[ORM\Column(name: 'id', type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    #[Serialize\Groups(['contact_type_id'])]
    private ?Ulid $id = null;

    #[ORM\Column(name: 'value', type: Types::TEXT)]
    #[Serialize\Groups(['contact_type_id'])]
    private ?string $value = null;

    #[ORM\ManyToOne(targetEntity: ContactType::class, inversedBy: 'details')]
    #[ORM\JoinColumn(name: 'contact_type_id')]
    #[Serialize\Groups(['contact_type_id'])]
    private ?ContactType $type = null;

    #[ORM\ManyToOne(targetEntity: Contact::class, inversedBy: 'additionalContactDetails')]
    #[ORM\JoinColumn(name: 'contact_id')]
    #[Serialize\Groups(['js'])]
    private ?Contact $contact = null;

    public function getId(): Ulid
    {
        return $this->id;
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

    public function getType(): ?ContactType
    {
        return $this->type;
    }

    public function setType(ContactType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function setContact(Contact $contact): self
    {
        $this->contact = $contact;

        return $this;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
