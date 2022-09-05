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

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Serializer\Annotation as Serialize;

/**
 * @ORM\Table(name="addresses")
 * @ORM\Entity()
 * @Gedmo\Loggable
 */
class Address
{
    use TimeStampable;

    /**
     * @var int|null
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     * @Serialize\Groups({"client_api"})
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="street1", type="string", nullable=true)
     * @Serialize\Groups({"client_api"})
     */
    private $street1;

    /**
     * @var string|null
     *
     * @ORM\Column(name="street2", type="string", nullable=true)
     * @Serialize\Groups({"client_api"})
     */
    private $street2;

    /**
     * @var string|null
     *
     * @ORM\Column(name="city", type="string", nullable=true)
     * @Serialize\Groups({"client_api"})
     */
    private $city;

    /**
     * @var string|null
     *
     * @ORM\Column(name="state", type="string", nullable=true)
     * @Serialize\Groups({"client_api"})
     */
    private $state;

    /**
     * @var string|null
     *
     * @ORM\Column(name="zip", type="string", nullable=true)
     * @Serialize\Groups({"client_api"})
     */
    private $zip;

    /**
     * @var string|null
     *
     * @ORM\Column(name="country", type="string", nullable=true)
     * @Serialize\Groups({"client_api"})
     */
    private $country;

    /**
     * @var string
     *
     * @Serialize\Groups({"client_api"})
     */
    private $countryName;

    /**
     * @var Client|null
     *
     * @ORM\ManyToOne(targetEntity="SolidInvoice\ClientBundle\Entity\Client", inversedBy="addresses")
     */
    private $client;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getStreet1(): ?string
    {
        return $this->street1;
    }

    /**
     * @param string $street1
     */
    public function setStreet1(?string $street1): self
    {
        $this->street1 = $street1;

        return $this;
    }

    /**
     * @return string
     */
    public function getStreet2(): ?string
    {
        return $this->street2;
    }

    /**
     * @param string $street2
     */
    public function setStreet2(?string $street2): self
    {
        $this->street2 = $street2;

        return $this;
    }

    /**
     * @return string
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @param string $city
     */
    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return string
     */
    public function getState(): ?string
    {
        return $this->state;
    }

    /**
     * @param string $state
     */
    public function setState(?string $state): self
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return string
     */
    public function getZip(): ?string
    {
        return $this->zip;
    }

    /**
     * @param string $zip
     */
    public function setZip(?string $zip): self
    {
        $this->zip = $zip;

        return $this;
    }

    /**
     * @return string
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * @return string
     */
    public function getCountryName(): ?string
    {
        return Countries::getName($this->getCountry());
    }

    /**
     * @param string $country
     */
    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return Client
     */
    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function __toString(): string
    {
        static $countries;

        if (empty($countries)) {
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

        return trim(implode("\n", $info), ', \t\n\r\0\x0B');
    }

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
}
