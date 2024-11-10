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
use Psr\Container\ContainerExceptionInterface;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator;
use SolidInvoice\QuoteBundle\Entity\Line;
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
        private readonly WorkflowInterface $quoteStateMachine,
        private readonly BillingIdGenerator $billingIdGenerator,
    ) {
    }

    /**
     * @throws MathException
     * @throws ContainerExceptionInterface
     */
    public function clone(Quote $quote): Quote
    {
        // We don't use 'clone', since cloning a quote will clone all the line id's and nested values.
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
        $newQuote->setQuoteId($this->billingIdGenerator->generate($newQuote, ['field' => 'quoteId']));

        if (null !== $quote->getTax()) {
            $newQuote->setTax($quote->getTax());
        }

        array_map(static fn (Line $line): Quote => $newQuote->addLine($line), iterator_to_array($this->addLines($quote, $now)));

        $this->quoteStateMachine->apply($newQuote, Graph::TRANSITION_NEW);

        return $newQuote;
    }

    /**
     * @throws MathException
     * @return Traversable<Line>
     */
    private function addLines(Quote $quote, Carbon $now): Traversable
    {
        foreach ($quote->getLines() as $line) {
            $quoteLine = new Line();
            $quoteLine->setCreated($now);
            $quoteLine->setTotal($line->getTotal());
            $quoteLine->setDescription($line->getDescription());
            $quoteLine->setPrice($line->getPrice());
            $quoteLine->setQty($line->getQty());

            if ($line->getTax() instanceof Tax) {
                $quoteLine->setTax($line->getTax());
            }

            yield $quoteLine;
        }
    }
}
