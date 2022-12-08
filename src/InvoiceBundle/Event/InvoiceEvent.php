<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Event;

use SolidInvoice\InvoiceBundle\Entity\BaseInvoice;
use Symfony\Contracts\EventDispatcher\Event;

class InvoiceEvent extends Event
{
    /**
     * @var BaseInvoice
     */
    protected $invoice;

    public function __construct(BaseInvoice $invoice = null)
    {
        $this->invoice = $invoice;
    }

    public function setInvoice(BaseInvoice $invoice): void
    {
        $this->invoice = $invoice;
    }

    public function getInvoice(): BaseInvoice
    {
        return $this->invoice;
    }
}
