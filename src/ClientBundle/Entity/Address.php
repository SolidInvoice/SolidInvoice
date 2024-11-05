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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use Stringable;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Uid\Ulid;

#[ORM\Table(name: Address::TABLE_NAME)]
#[ORM\Entity]
#[ApiResource(
    uriTemplate: '/clients/{clientId}/address/{id}',
    operations: [new Get(), new Delete(), new Patch()],
    uriVariables: [
        'clientId' => new Link(
            fromProperty: 'addresses',
            fromClass: Client::class,
            description: 'Client identifier',
        ),
        'id' => new Link(
            fromClass: Address::class,
        ),
    ],
    normalizationContext: [
        'groups' => ['address_api:read'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
    denormalizationContext: [
        'groups' => ['address_api:write'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ]
)]
#[ApiResource(
    uriTemplate: '/clients/{clientId}/addresses',
    operations: [ new GetCollection(), new Post() ],
    uriVariables: [
        'clientId' => new Link(
            fromProperty: 'addresses',
            fromClass: Client::class,
            description: 'Client identifier',
        ),
    ],
    normalizationContext: [
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
    denormalizationContext: [
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ]
)]
class Address implements Stringable
{
    final public const TABLE_NAME = 'addresses';

    use TimeStampable;
    use CompanyAware;

    #[ORM\Column(name: 'id', type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    #[Groups(['address_api:read'])]
    private ?Ulid $id = null;

    #[ORM\Column(name: 'street1', type: Types::STRING, nullable: true)]
    #[Groups(['address_api:read', 'address_api:write'])]
    private ?string $street1 = null;

    #[ORM\Column(name: 'street2', type: Types::STRING, nullable: true)]
    #[Groups(['address_api:read', 'address_api:write'])]
    private ?string $street2 = null;

    #[ORM\Column(name: 'city', type: Types::STRING, nullable: true)]
    #[Groups(['address_api:read', 'address_api:write'])]
    private ?string $city = null;

    #[ORM\Column(name: 'state', type: Types::STRING, nullable: true)]
    #[Groups(['address_api:read', 'address_api:write'])]
    private ?string $state = null;

    #[ORM\Column(name: 'zip', type: Types::STRING, nullable: true)]
    #[Groups(['address_api:read', 'address_api:write'])]
    private ?string $zip = null;

    #[ORM\Column(name: 'country', type: Types::STRING, nullable: true)]
    #[Groups(['address_api:read', 'address_api:write'])]
    #[ApiProperty(iris: ['https://schema.org/Country'])]
    private ?string $country = null;

    #[Groups(['address_api:read'])]
    private ?string $countryName = null;

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'addresses')]
    #[Groups(['address_api:read'])]
    #[ApiProperty(writable: false, writableLink: false, example: '/api/clients/3fa85f64-5717-4562-b3fc-2c963f66afa6')]
    private ?Client $client = null;

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getStreet1(): ?string
    {
        return $this->street1;
    }

    public function setStreet1(?string $street1): self
    {
        $this->street1 = $street1;

        return $this;
    }

    public function getStreet2(): ?string
    {
        return $this->street2;
    }

    public function setStreet2(?string $street2): self
    {
        $this->street2 = $street2;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(?string $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function setZip(?string $zip): self
    {
        $this->zip = $zip;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function getCountryName(): ?string
    {
        if (null === $this->countryName) {
            $this->countryName = $this->getCountry() && Countries::exists($this->getCountry()) ? Countries::getName($this->getCountry()) : null;
        }

        return $this->countryName;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @param array{street1: ?string, street2: ?string, city: ?string, state: ?string, zip: ?string, country: ?string} $data
     */
    public static function fromArray(array $data): self
    {
        $address = new self();
        $address->setStreet1($data['street1'] ?? null);
        $address->setStreet2($data['street2'] ?? null);
        $address->setCity($data['city'] ?? null);
        $address->setState($data['state'] ?? null);
        $address->setZip($data['zip'] ?? null);
        $address->setCountry($data['country'] ?? null);

        return $address;
    }

    public function __toString(): string
    {
        static $countries = [];

        if ([] === $countries) {
            $countries = Countries::getNames();
        }

        $info = array_filter(
            [
                $this->street1,
                $this->street2,
                $this->city,
                $this->state,
                $this->zip,
                $countries[$this->country] ?? null,
            ]
        );

        return trim(implode("\n", $info), ", \t\n\r\0\x0B");
    }
}
