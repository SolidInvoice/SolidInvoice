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
use JMS\Serializer\Annotation as Serialize;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * CSBill\ClientBundle\Entity\AdditionalContactDetail.
 *
 * @ORM\Entity()
 * @ORM\Table(name="contact_details")
 * @Gedmo\Loggable()
 */
class AdditionalContactDetail
{
    use Entity\TimeStampable;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serialize\Groups({"js"})
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text", nullable=false)
     * @Serialize\Groups({"api", "js"})
     */
    protected $value;

    /**
     * @var ContactType
     *
     * @ORM\ManyToOne(targetEntity="ContactType", inversedBy="details")
     * @ORM\JoinColumn(name="contact_type_id", referencedColumnName="id")
     * @Serialize\Groups({"api", "js"})
     * @Serialize\Inline()
     */
    protected $type;

    /**
     * @var Contact
     *
     * @ORM\ManyToOne(targetEntity="Contact", inversedBy="additionalDetails")
     * @ORM\JoinColumn(name="contact_id", referencedColumnName="id")
     * @Serialize\Groups({"js"})
     */
    private $contact;

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
     * Get value.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set value.
     *
     * @param string $value
     *
     * @return AdditionalContactDetail
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get type.
     *
     * @return ContactType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set type.
     *
     * @param ContactType $type
     *
     * @return AdditionalContactDetail
     */
    public function setType(ContactType $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->value;
    }

    /**
     * Get contact.
     *
     * @return Contact
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * Set contact.
     *
     * @param Contact $contact
     *
     * @return AdditionalContactDetail
     */
    public function setContact(Contact $contact)
    {
        $this->contact = $contact;

        return $this;
    }
}
