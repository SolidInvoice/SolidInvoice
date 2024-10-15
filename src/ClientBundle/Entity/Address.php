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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\Doctrine\UuidOrderedTimeGenerator;
use Ramsey\Uuid\UuidInterface;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use Stringable;
use Symfony\Component\Intl\Countries;

#[ORM\Table(name: Address::TABLE_NAME)]
#[ORM\Entity]
class Address implements Stringable
{
    final public const TABLE_NAME = 'addresses';

    use TimeStampable;
    use CompanyAware;

    #[ORM\Column(name: 'id', type: UuidBinaryOrderedTimeType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidOrderedTimeGenerator::class)]
    private ?UuidInterface $id = null;

    #[ORM\Column(name: 'street1', type: Types::STRING, nullable: true)]
    private ?string $street1 = null;

    #[ORM\Column(name: 'street2', type: Types::STRING, nullable: true)]
    private ?string $street2 = null;

    #[ORM\Column(name: 'city', type: Types::STRING, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(name: 'state', type: Types::STRING, nullable: true)]
    private ?string $state = null;

    #[ORM\Column(name: 'zip', type: Types::STRING, nullable: true)]
    private ?string $zip = null;

    #[ORM\Column(name: 'country', type: Types::STRING, nullable: true)]
    private ?string $country = null;

    private ?string $countryName = null;

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'addresses')]
    private ?Client $client = null;

    public function getId(): ?UuidInterface
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
            $this->countryName = $this->getCountry() ? Countries::getName($this->getCountry()) : null;
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
