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

use Doctrine\ORM\Mapping as ORM;

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
     */
    private $value;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_primary", type="boolean", nullable=true)
     */
    private $primary;

    /**
     * @var Contact $contact
     *
     * @ORM\ManyToOne(targetEntity="Contact", inversedBy="details")
     * @ORM\JoinColumn(name="contact_id", referencedColumnName="id")
     */
    private $contact;

    /**
     * @var ContactType $type
     *
     * @ORM\ManyToOne(targetEntity="ContactType", inversedBy="details")
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

    /**
     * Set the contact detail as primary
     *
     * @param  boolean       $primary
     * @return ContactDetail
     */
    public function setPrimary($primary)
    {
        $this->primary = (bool) $primary;

        return $this;
    }

    /**
     * Is the contact detail primary
     *
     * @return bool
     */
    public function isPrimary()
    {
        return (bool) $this->primary;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->value;
    }
}
