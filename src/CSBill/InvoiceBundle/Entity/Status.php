<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\InvoiceBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use CSBill\CoreBundle\Entity\Status as BaseStatus;

/**
 * CSBill\InvoiceBundle\Entity\Status
 *
 * @ORM\Entity
 */
class Status extends BaseStatus
{
    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="Invoice", mappedBy="status", fetch="EXTRA_LAZY")
     */
    protected $invoices;

    public function __construct()
    {
        $this->invoices = new ArrayCollection();
    }

    /**
     * @param  Invoice $invoice
     * @return $this
     */
    public function addInvoice(Invoice $invoice)
    {
        $this->invoices[] = $invoice;
        $invoice->setStatus($this);

        return $this;
    }

    /**
     * @param  Invoice $invoice
     * @return $this
     */
    public function removeInvoice(Invoice $invoice)
    {
        $this->invoices->removeElement($invoice);

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getInvoices()
    {
        return $this->invoices;
    }
}
