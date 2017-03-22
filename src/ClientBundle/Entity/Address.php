<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Entity;

use CSBill\CoreBundle\Traits\Entity;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serialize;
use Symfony\Component\Intl\Intl;

/**
 * @ORM\Table(name="addresses")
 * @ORM\Entity()
 * @Gedmo\Loggable()
 * @Gedmo\SoftDeleteable()
 */
class Address
{
    use Entity\TimeStampable,
        Entity\SoftDeleteable;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serialize\Groups({"js"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="street1", type="string", nullable=true)
     * @Serialize\Groups({"api", "js"})
     */
    private $street1;

    /**
     * @var string
     *
     * @ORM\Column(name="street2", type="string", nullable=true)
     * @Serialize\Groups({"api", "js"})
     */
    private $street2;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", nullable=true)
     * @Serialize\Groups({"api", "js"})
     */
    private $city;

    /**
     * @var string
     *
     * @ORM\Column(name="state", type="string", nullable=true)
     * @Serialize\Groups({"api", "js"})
     */
    private $state;

    /**
     * @var string
     *
     * @ORM\Column(name="zip", type="string", nullable=true)
     * @Serialize\Groups({"api", "js"})
     */
    private $zip;

    /**
     * @var string
     *
     * @ORM\Column(name="country", type="string", nullable=true)
     * @Serialize\Groups({"api", "js"})
     */
    private $country;

    /**
     * @var Client
     *
     * @ORM\ManyToOne(targetEntity="CSBill\ClientBundle\Entity\Client", inversedBy="addresses")
     * @Serialize\Groups({"js"})
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
     *
     * @return Address
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
     *
     * @return Address
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
     *
     * @return Address
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
     *
     * @return Address
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
     *
     * @return Address
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
     * @param string $country
     *
     * @return Address
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

    /**
     * @param Client $client
     *
     * @return Address
     */
    public function setClient(Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        static $countries;

        if (empty($countries)) {
            $countries = Intl::getRegionBundle()->getCountryNames();
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

    /**
     * @param array $data
     *
     * @return Address
     */
    public static function fromArray(array $data): Address
    {
        $address = new static();
        $address->setStreet1($data['street1'] ?? null);
        $address->setStreet2($data['street2'] ?? null);
        $address->setCity($data['city'] ?? null);
        $address->setState($data['state'] ?? null);
        $address->setZip($data['zip'] ?? null);
        $address->setCountry($data['country'] ?? null);

        return $address;
    }
}
