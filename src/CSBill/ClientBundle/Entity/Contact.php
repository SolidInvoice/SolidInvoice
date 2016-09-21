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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serialize;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="contacts", indexes={@ORM\Index(name="email", columns={"email"})})
 * @ORM\Entity(repositoryClass="CSBill\ClientBundle\Repository\ContactRepository")
 * @Gedmo\Loggable()
 * @Gedmo\SoftDeleteable()
 */
class Contact implements \Serializable
{
    use Entity\TimeStampable,
        Entity\SoftDeleteable;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serialize\Groups({"api", "js"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="firstName", type="string", length=125, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(max=125)
     * @Serialize\Groups({"api", "js"})
     */
    private $firstName;

    /**
     * @var string
     *
     * @ORM\Column(name="lastName", type="string", length=125, nullable=true)
     * @Assert\Length(max=125)
     * @Serialize\Groups({"api", "js"})
     */
    private $lastName;

    /**
     * @var Client
     *
     * @ORM\ManyToOne(targetEntity="Client", inversedBy="contacts")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     * @Serialize\Groups({"js"})
     */
    private $client;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Email(strict=true)
     * @Serialize\Groups({"api", "js"})
     */
    private $email;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="AdditionalContactDetail", mappedBy="contact", cascade={"persist"})
     * @Assert\Valid()
     * @Serialize\Groups({"api", "js"})
     */
    private $additionalDetails;

    public function __construct()
    {
        $this->additionalDetails = new ArrayCollection();
    }

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
     * Get firstName.
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set firstName.
     *
     * @param string $firstName
     *
     * @return Contact
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get lastname.
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set lastname.
     *
     * @param string $lastName
     *
     * @return Contact
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get client.
     *
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Set client.
     *
     * @param Client $client
     *
     * @return Contact
     */
    public function setClient(Client $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Add additional detail.
     *
     * @param AdditionalContactDetail $detail
     *
     * @return Contact
     */
    public function addAdditionalDetail(AdditionalContactDetail $detail)
    {
        $this->additionalDetails->add($detail);
        $detail->setContact($this);

        return $this;
    }

    /**
     * Removes additional detail from the current contact.
     *
     * @param AdditionalContactDetail $detail
     *
     * @return Contact
     */
    public function removeAdditionalDetail(AdditionalContactDetail $detail)
    {
        $this->additionalDetails->removeElement($detail);

        return $this;
    }

    /**
     * Get additional details.
     *
     * @return Collection|AdditionalContactDetail[]
     */
    public function getAdditionalDetails()
    {
        return $this->additionalDetails;
    }

    /**
     * @param string $type
     *
     * @return null|AdditionalContactDetail
     */
    public function getAdditionalDetail($type)
    {
        if (count($this->additionalDetails) > 0) {
            foreach ($this->additionalDetails as $detail) {
                if (strtolower((string) $detail->getType()) === strtolower($type)) {
                    return $detail;
                }
            }
        }

        return null;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize([$this->id, $this->firstName, $this->lastName, $this->created, $this->updated]);
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        list($this->id, $this->firstName, $this->lastName, $this->created, $this->updated) = unserialize($serialized);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->firstName.' '.$this->lastName;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return Contact
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }
}
