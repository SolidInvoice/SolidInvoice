<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Entity;

use CSBill\CoreBundle\Traits\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="contacts")
 * @ORM\Entity(repositoryClass="CSBill\ClientBundle\Repository\ContactRepository")
 * @Gedmo\Loggable()
 * @Gedmo\SoftDeleteable()
 */
class Contact implements \serializable
{
    use Entity\TimeStampable,
        Entity\SoftDeleteable;

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $firstname
     *
     * @ORM\Column(name="firstname", type="string", length=125, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(max=125)
     */
    private $firstname;

    /**
     * @var string $lastname
     *
     * @ORM\Column(name="lastname", type="string", length=125, nullable=true)
     * @Assert\Length(max=125)
     */
    private $lastname;

    /**
     * @var Client $client
     *
     * @ORM\ManyToOne(targetEntity="Client", inversedBy="contacts")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     */
    private $client;

    /**
     * @var ArrayCollection $primaryDetails
     *
     * @ORM\OneToMany(indexBy="contact_type_id", targetEntity="PrimaryContactDetail", mappedBy="contact",
     *                                           cascade={"persist"})
     * @Assert\Valid()
     */
    private $primaryDetails;

    /**
     * @var ArrayCollection $additionalDetails
     *
     * @ORM\OneToMany(targetEntity="AdditionalContactDetail", mappedBy="contact", cascade={"persist"})
     * @Assert\Valid()
     */
    private $additionalDetails;

    /**
     * Constructer
     */
    public function __construct()
    {
        $this->primaryDetails = new ArrayCollection();
        $this->additionalDetails = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get firstname
     *
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set firstname
     *
     * @param string $firstname
     *
     * @return Contact
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get lastname
     *
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set lastname
     *
     * @param string $lastname
     *
     * @return Contact
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * Get client
     *
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Set client
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
     * Add additional detail
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
     * Removes primary detail from the current contact
     *
     * @param PrimaryContactDetail $detail
     *
     * @return Contact
     */
    public function removePrimaryDetail(PrimaryContactDetail $detail)
    {
        $this->primaryDetails->removeElement($detail);

        return $this;
    }

    /**
     * Removes additional detail from the current contact
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
     * Get primary details
     *
     * @return ArrayCollection
     */
    public function getPrimaryDetails()
    {
        return $this->primaryDetails;
    }

    /**
     * Add primary detail
     *
     * @param PrimaryContactDetail $detail
     *
     * @return Contact
     */
    public function setPrimaryDetails(PrimaryContactDetail $detail)
    {
        if (!$this->primaryDetails->containsKey($detail->getType()->getId())) {
            $this->primaryDetails->add($detail);
        }

        $detail->setContact($this);

        return $this;
    }

    /**
     * Get additional details
     *
     * @return ArrayCollection
     */
    public function getAdditionalDetails()
    {
        return $this->additionalDetails;
    }

    /**
     * @param string $type
     *
     * @return null|PrimaryContactDetail
     */
    public function getPrimaryDetail($type)
    {
        if (count($this->primaryDetails) > 0) {
            foreach ($this->primaryDetails as $detail) {
                if (strtolower((string) $detail->getType()) === strtolower($type)) {
                    return $detail;
                }
            }
        }

        return;
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

        return;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize(array($this->id, $this->firstname, $this->lastname));
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        list($this->id, $this->firstname, $this->lastname) = unserialize($serialized);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->firstname.' '.$this->lastname;
    }
}
