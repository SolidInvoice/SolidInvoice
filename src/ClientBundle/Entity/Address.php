<?php

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
    public function getId()
    {
	return $this->id;
    }

    /**
     * @return string
     */
    public function getStreet1()
    {
	return $this->street1;
    }

    /**
     * @param string $street1
     *
     * @return $this
     */
    public function setStreet1($street1)
    {
	$this->street1 = $street1;

	return $this;
    }

    /**
     * @return string
     */
    public function getStreet2()
    {
	return $this->street2;
    }

    /**
     * @param string $street2
     *
     * @return $this
     */
    public function setStreet2($street2)
    {
	$this->street2 = $street2;

	return $this;
    }

    /**
     * @return string
     */
    public function getCity()
    {
	return $this->city;
    }

    /**
     * @param string $city
     *
     * @return $this
     */
    public function setCity($city)
    {
	$this->city = $city;

	return $this;
    }

    /**
     * @return string
     */
    public function getState()
    {
	return $this->state;
    }

    /**
     * @param string $state
     *
     * @return $this
     */
    public function setState($state)
    {
	$this->state = $state;

	return $this;
    }

    /**
     * @return string
     */
    public function getZip()
    {
	return $this->zip;
    }

    /**
     * @param string $zip
     *
     * @return $this
     */
    public function setZip($zip)
    {
	$this->zip = $zip;

	return $this;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
	return $this->country;
    }

    /**
     * @param string $country
     *
     * @return $this
     */
    public function setCountry($country)
    {
	$this->country = $country;

	return $this;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
	return $this->client;
    }

    /**
     * @param Client $client
     */
    public function setClient(Client $client)
    {
	$this->client = $client;
    }

    /**
     * @return string
     */
    public function __toString()
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
		isset($countries[$this->country]) ? $countries[$this->country] : null,
	    ]
	);

	return trim(implode("\n", $info), ', \t\n\r\0\x0B');
    }

    /**
     * @param array $data
     *
     * @return static
     */
    public static function fromArray(array $data)
    {
	$address = new static();
	$address->setStreet1($data['street1']);
	$address->setStreet2($data['street2']);
	$address->setCity($data['city']);
	$address->setState($data['state']);
	$address->setZip($data['zip']);
	$address->setCountry($data['country']);

	return $address;
    }
}
