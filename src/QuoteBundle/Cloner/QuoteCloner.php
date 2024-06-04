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

namespace SolidInvoice\QuoteBundle\Cloner;

use Brick\Math\Exception\MathException;
use Carbon\Carbon;
use SolidInvoice\QuoteBundle\Entity\Item;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Model\Graph;
use SolidInvoice\TaxBundle\Entity\Tax;
use Symfony\Component\Workflow\WorkflowInterface;
use Traversable;

/**
 * @see \SolidInvoice\QuoteBundle\Tests\Cloner\QuoteClonerTest
 */
final class QuoteCloner
{
    public function __construct(
        private readonly WorkflowInterface $quoteStateMachine
    ) {
    }

    /**
     * @throws MathException
     */
    public function clone(Quote $quote): Quote
    {
        // We don't use 'clone', since cloning a quote will clone all the item id's and nested values.
        // We rather set it manually
        $newQuote = new Quote();

        $now = Carbon::now();

        $newQuote->setCreated($now);
        $newQuote->setClient($quote->getClient());
        $newQuote->setBaseTotal($quote->getBaseTotal());
        $newQuote->setDiscount($quote->getDiscount());
        $newQuote->setNotes($quote->getNotes());
        $newQuote->setTotal($quote->getTotal());
        $newQuote->setTerms($quote->getTerms());
        $newQuote->setUsers($quote->getUsers());

        if (null !== $quote->getTax()) {
            $newQuote->setTax($quote->getTax());
        }

        array_map(static fn (Item $item): Quote => $newQuote->addItem($item), iterator_to_array($this->addItems($quote, $now)));

        $this->quoteStateMachine->apply($newQuote, Graph::TRANSITION_NEW);

        return $newQuote;
    }

    /**
     * @throws MathException
     */
    private function addItems(Quote $quote, Carbon $now): Traversable
    {
        foreach ($quote->getItems() as $item) {
            $invoiceItem = new Item();
            $invoiceItem->setCreated($now);
            $invoiceItem->setTotal($item->getTotal());
            $invoiceItem->setDescription($item->getDescription());
            $invoiceItem->setPrice($item->getPrice());
            $invoiceItem->setQty($item->getQty());

            if ($item->getTax() instanceof Tax) {
                $invoiceItem->setTax($item->getTax());
            }

            yield $invoiceItem;
        }
    }
}
