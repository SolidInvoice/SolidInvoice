<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Entity;

use CSBill\CoreBundle\Traits\Entity;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serialize;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="recurring_invoices")
 * @ORM\Entity()
 * @Gedmo\Loggable()
 * @Gedmo\SoftDeleteable()
 * @Serialize\ExclusionPolicy("all")
 */
class RecurringInvoice
{
    use Entity\TimeStampable,
	Entity\SoftDeleteable,
	Entity\Archivable;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serialize\Expose()
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="frequency", type="string", nullable=true)
     */
    private $frequency;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_start", type="date")
     * @Assert\NotBlank(groups={"Recurring"})
     * @Assert\Date(groups={"Recurring"})
     */
    private $dateStart;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_end", type="date", nullable=true)
     */
    private $dateEnd;

    /**
     * @var Invoice
     *
     * @ORM\OneToOne(targetEntity="CSBill\InvoiceBundle\Entity\Invoice", inversedBy="recurringInfo")
     */
    private $invoice;

    /**
     * @return int
     */
    public function getId()
    {
	return $this->id;
    }

    /**
     * @return string
     */
    public function getFrequency()
    {
	return $this->frequency;
    }

    /**
     * @param string $frequency
     *
     * @return Invoice
     */
    public function setFrequency($frequency)
    {
	$this->frequency = $frequency;

	return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateStart()
    {
	return $this->dateStart;
    }

    /**
     * @param \DateTime $dateStart
     *
     * @return Invoice
     */
    public function setDateStart(\DateTime $dateStart = null)
    {
	$this->dateStart = $dateStart;

	return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateEnd()
    {
	return $this->dateEnd;
    }

    /**
     * @param \DateTime $dateEnd
     *
     * @return Invoice
     */
    public function setDateEnd(\DateTime $dateEnd = null)
    {
	$this->dateEnd = $dateEnd;

	return $this;
    }

    /**
     * @return Invoice
     */
    public function getInvoice()
    {
	return $this->invoice;
    }

    /**
     * @param Invoice $invoice
     *
     * @return Invoice
     */
    public function setInvoice(Invoice $invoice)
    {
	$this->invoice = $invoice;

	return $this;
    }
}
