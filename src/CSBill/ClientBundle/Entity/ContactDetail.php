<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Entity;

use CSBill\CoreBundle\Traits\Entity;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serialize;

/**
 * CSBill\ClientBundle\Entity\ContactDetail.
 *
 * @ORM\Table(name="contact_details")
 * @ORM\Entity()
 * @Gedmo\Loggable()
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="detail_type", type="string")
 * @ORM\DiscriminatorMap({
 *      "primary"    = "CSBill\ClientBundle\Entity\PrimaryContactDetail",
 *      "additional" = "CSBill\ClientBundle\Entity\AdditionalContactDetail",
 * })
 * @Serialize\Discriminator(field = "detail_type", map = {
 *    "primary": "CSBill\ClientBundle\Entity\PrimaryContactDetail",
 *    "additional": "CSBill\ClientBundle\Entity\AdditionalContactDetail",
 * })
 * @Serialize\ExclusionPolicy("all")
 */
abstract class ContactDetail implements \JsonSerializable
{
    use Entity\TimeStampable,
        Entity\JsonSerialize {
        Entity\JsonSerialize::jsonSerialize as serializeJson;
    }

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text", nullable=false)
     * @Serialize\Expose()
     */
    protected $value;

    /**
     * @var ContactType
     *
     * @ORM\ManyToOne(targetEntity="ContactType", inversedBy="details")
     * @ORM\JoinColumn(name="contact_type_id", referencedColumnName="id")
     * @Serialize\Expose()
     * @Serialize\Inline()
     */
    protected $type;

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
     * @return ContactDetail
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
     * @return ContactDetail
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
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $this->__serializeExclude[] = 'contact';

        return $this->serializeJson();
    }
}
