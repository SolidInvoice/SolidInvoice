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
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CSBill\ClientBundle\Entity\ContactDetail
 *
 * @ORM\Table(name="contact_details")
 * @ORM\Entity()
 */
class ContactDetail
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
     * @var string $value
     *
     * @ORM\Column(name="value", type="text", nullable=false)
     * @Assert\NotBlank()
     */
    private $value;

    /**
     * @var Contact $contact
     *
     * @ORM\ManyToOne(targetEntity="Contact", inversedBy="details", cascade="ALL")
     * @ORM\JoinColumn(name="contact_id", referencedColumnName="id")
     */
    private $contact;

    /**
     * @var ContactType $type
     *
     * @ORM\ManyToOne(targetEntity="ContactType", inversedBy="details", cascade="ALL")
     * @ORM\JoinColumn(name="contact_type_id", referencedColumnName="id")
     */
    private $type;

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
     * Set value
     *
     * @param  string        $value
     * @return ContactDetail
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set contact
     *
     * @param  Contact       $contact
     * @return ContactDetail
     */
    public function setContact(Contact $contact)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * Get contact
     *
     * @return Contact
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * Set type
     *
     * @param  ContactType   $type
     * @return ContactDetail
     */
    public function setType(ContactType $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return ContactType
     */
    public function getType()
    {
        return $this->type;
    }
}
