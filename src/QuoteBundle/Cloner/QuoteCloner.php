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

namespace SolidInvoice\QuoteBundle\Cloner;

use Carbon\Carbon;
use SolidInvoice\QuoteBundle\Entity\Item;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Model\Graph;
use Symfony\Component\Workflow\StateMachine;
use Traversable;

final class QuoteCloner
{
    /**
     * @var StateMachine
     */
    private $stateMachine;

    public function __construct(StateMachine $stateMachine)
    {
        $this->stateMachine = $stateMachine;
    }

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
        $newQuote->setUsers($quote->getUsers()->toArray());

        if (null !== $quote->getTax()) {
            $newQuote->setTax($quote->getTax());
        }

        array_map(static function (Item $item) use ($newQuote): Quote {
            return $newQuote->addItem($item);
        }, iterator_to_array($this->addItems($quote, $now)));

        $this->stateMachine->apply($newQuote, Graph::TRANSITION_NEW);

        return $newQuote;
    }

    private function addItems(Quote $quote, Carbon $now): Traversable
    {
        foreach ($quote->getItems() as $item) {
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
