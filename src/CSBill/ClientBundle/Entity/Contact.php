<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\ClientBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use CSBill\ClientBundle\Validator\Constraints as CSBillAssert;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * CSBill\ClientBundle\Entity\Contact
 *
 * @ORM\Table(name="contacts")
 * @ORM\Entity(repositoryClass="CSBill\ClientBundle\Repository\ContactRepository")
 * @Gedmo\Loggable()
 * @Gedmo\SoftDeleteable(fieldName="deleted")
 */
class Contact implements \serializable
{
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
     * @var ArrayCollection $details
     *
     * @ORM\OneToMany(targetEntity="ContactDetail", mappedBy="contact")
     *
     * @Assert\Count(
     *      min = "1",
     *      minMessage = "You must add al least one contact detail for each contact"
     * )
     * @Assert\Valid()
     * @CSBillAssert\ContactDetailPrimary
     */
    private $details;

    /**
     * @var DateTIme $created
     *
     * @ORM\Column(name="created", type="datetime")
     * @Gedmo\Timestampable(on="create")
     * @Assert\DateTime()
     */
    private $created;

    /**
     * @var DateTime $updated
     *
     * @ORM\Column(name="updated", type="datetime")
     * @Gedmo\Timestampable(on="update")
     * @Assert\DateTime()
     */
    private $updated;

    /**
     * @var DateTime $deleted
     *
     * @ORM\Column(name="deleted", type="datetime", nullable=true)
     * @Assert\DateTime()
     */
    private $deleted;

    /**
     * Constructer
     */
    public function __construct()
    {
        $this->details = new ArrayCollection;
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
     * Set firstname
     *
     * @param  string  $firstname
     * @return Contact
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
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
     * Set lastname
     *
     * @param  string  $lastname
     * @return Contact
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

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
     * Set client
     *
     * @param  Client  $client
     * @return Contact
     */
    public function setClient(Client $client)
    {
        $this->client = $client;

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
     * Add detail
     *
     * @param  ContactDetail $detail
     * @return Contact
     */
    public function addDetail(ContactDetail $detail)
    {
        $this->details[] = $detail;
        $detail->setContact($this);

        return $this;
    }

    /**
     * Removes detail from the current contact
     *
     * @param  ContactDetail $detail
     * @return Contact
     */
    public function removeDetail(ContactDetail $detail)
    {
        $this->details->removeElement($detail);

        return $this;
    }

    /**
     * Get details
     *
     * @return ArrayCollection
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * Set created
     *
     * @param  \DateTime $created
     * @return Client
     */
    public function setCreated(\DateTime $created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param  \DateTime $updated
     * @return Client
     */
    public function setUpdated(\DateTime $updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set deleted
     *
     * @param  \DateTime $deleted
     * @return Client
     */
    public function setDeleted(\DateTime $deleted)
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function getDetail($type)
    {
        if (count($this->details) > 0) {
            foreach ($this->details as $detail) {
                if (strtolower((string) $detail->getType()) === strtolower($type)) {
                    return $detail;
                }
            }
        }

        // TODO : throw exception
        return null;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getDeleted()
    {
        return $this->created;
    }

    public function serialize()
    {
        return serialize(array($this->id, $this->firstname, $this->lastname));
    }

    public function unserialize($serialized)
    {
        list($this->id, $this->firstname, $this->lastname) = unserialize($serialized);
    }

    public function __toString()
    {
        return $this->firstname . ' ' . $this->lastname;
    }
}
