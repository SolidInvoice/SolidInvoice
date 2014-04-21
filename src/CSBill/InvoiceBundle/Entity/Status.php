<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
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
