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

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serialize;

/**
 * CSBill\ClientBundle\Entity\AdditionalContactDetail.
 *
 * @ORM\Entity()
 */
class AdditionalContactDetail extends ContactDetail
{
    /**
     * @var Contact
     *
     * @ORM\ManyToOne(targetEntity="Contact", inversedBy="additionalDetails")
     * @ORM\JoinColumn(name="contact_id", referencedColumnName="id")
     * @Serialize\Groups({"js"})
     */
    private $contact;

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
