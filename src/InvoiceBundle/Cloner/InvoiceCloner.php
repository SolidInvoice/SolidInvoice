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

namespace SolidInvoice\InvoiceBundle\Cloner;

use Carbon\Carbon;
use SolidInvoice\InvoiceBundle\Entity\BaseInvoice;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Item;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Manager\InvoiceManager;
use Traversable;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Cloner\InvoiceClonerTest
 */
final class InvoiceCloner
{
    private InvoiceManager $invoiceManager;

    public function __construct(InvoiceManager $invoiceManager)
    {
        $this->invoiceManager = $invoiceManager;
    }

    public function clone(BaseInvoice $invoice): BaseInvoice
    {
        // We don't use 'clone', since cloning an invoice will clone all the item id's and nested values.
        // Rather set it manually
        $class = \get_class($invoice);
        /** @var RecurringInvoice|Invoice $newInvoice */
        $newInvoice = new $class();

        $now = Carbon::now();

        $newInvoice->setCreated($now);
        $newInvoice->setClient($invoice->getClient());
        $newInvoice->setBaseTotal($invoice->getBaseTotal());
        $newInvoice->setDiscount($invoice->getDiscount());
        $newInvoice->setNotes($invoice->getNotes());
        $newInvoice->setTotal($invoice->getTotal());
        $newInvoice->setTerms($invoice->getTerms());
        $newInvoice->setUsers($invoice->getUsers()->toArray());

        if (\method_exists($newInvoice, 'setBalance')) {
            $newInvoice->setBalance($newInvoice->getTotal());
        }

        if ($invoice instanceof RecurringInvoice) {
            $newInvoice->setDateStart($invoice->getDateStart());
            $newInvoice->setDateEnd($invoice->getDateEnd());
            $newInvoice->setFrequency($invoice->getFrequency());
        }

        if (null !== $tax = $invoice->getTax()) {
            $newInvoice->setTax($tax);
        }

        array_map(static fn (Item $item): BaseInvoice => $newInvoice->addItem($item), iterator_to_array($this->addItems($invoice, $now)));

        $this->invoiceManager->create($newInvoice);

        return $newInvoice;
    }

    private function addItems(BaseInvoice $invoice, Carbon $now): Traversable
    {
        foreach ($invoice->getItems() as $item) {
            $invoiceItem = new Item();
            $invoiceItem->setCreated($now);
            $invoiceItem->setTotal($item->getTotal());
            $invoiceItem->setDescription($item->getDescription());
            $invoiceItem->setPrice($item->getPrice());
            $invoiceItem->setQty($item->getQty());

            if (null !== $item->getTax()) {
                $invoiceItem->setTax($item->getTax());
            }

            yield $invoiceItem;
        }
    }
}
