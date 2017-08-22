<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Cloner;

use Carbon\Carbon;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Item;
use SolidInvoice\InvoiceBundle\Manager\InvoiceManager;

final class InvoiceCloner
{
    /**
     * @var InvoiceManager
     */
    private $invoiceManager;

    public function __construct(InvoiceManager $invoiceManager)
    {
        $this->invoiceManager = $invoiceManager;
    }

    /**
     * @param Invoice $invoice
     *
     * @return Invoice
     */
    public function clone(Invoice $invoice): Invoice
    {
        // We don't use 'clone', since cloning an invoice will clone all the item id's and nested values.
        // Rather set it manually
        $newInvoice = new Invoice();

        $now = Carbon::now();

        $newInvoice->setCreated($now);
        $newInvoice->setClient($invoice->getClient());
        $newInvoice->setBaseTotal($invoice->getBaseTotal());
        $newInvoice->setDiscount($invoice->getDiscount());
        $newInvoice->setNotes($invoice->getNotes());
        $newInvoice->setTotal($invoice->getTotal());
        $newInvoice->setTerms($invoice->getTerms());
        $newInvoice->setUsers($invoice->getUsers()->toArray());
        $newInvoice->setBalance($newInvoice->getTotal());

        if (null !== $tax = $invoice->getTax()) {
            $newInvoice->setTax($tax);
        }

        array_map([$newInvoice, 'addItem'], iterator_to_array($this->addItems($invoice, $now)));

        $this->invoiceManager->create($newInvoice);

        return $newInvoice;
    }

    /**
     * @param Invoice $invoice
     * @param Carbon  $now
     *
     * @return \Traversable
     */
    private function addItems(Invoice $invoice, Carbon $now): \Traversable
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
