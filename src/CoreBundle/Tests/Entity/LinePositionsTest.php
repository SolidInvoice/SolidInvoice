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

namespace SolidInvoice\CoreBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Entity\LineInterface;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line as InvoiceLine;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoiceLine;
use SolidInvoice\QuoteBundle\Entity\Line as QuoteLine;
use SolidInvoice\QuoteBundle\Entity\Quote;
use function array_map;
use function array_values;
use function assert;

/**
 * The three line owners share {@see \SolidInvoice\CoreBundle\Traits\Entity\LinePositions}, so
 * every case runs against all three rather than against the trait in isolation — what matters
 * is that an invoice, a recurring invoice and a quote all behave the same.
 */
final class LinePositionsTest extends TestCase
{
    /**
     * @return iterable<string, array{callable(): object, callable(): LineInterface}>
     */
    public static function owners(): iterable
    {
        yield 'invoice' => [
            static fn (): Invoice => new Invoice(),
            static fn (): InvoiceLine => new InvoiceLine(),
        ];

        yield 'recurring invoice' => [
            static fn (): RecurringInvoice => new RecurringInvoice(),
            static fn (): RecurringInvoiceLine => new RecurringInvoiceLine(),
        ];

        yield 'quote' => [
            static fn (): Quote => new Quote(),
            static fn (): QuoteLine => new QuoteLine(),
        ];
    }

    #[DataProvider('owners')]
    public function testAddingLinesAppendsThemInOrder(callable $newOwner, callable $newLine): void
    {
        $owner = $newOwner();

        $owner->addLine($first = $newLine());
        $owner->addLine($second = $newLine());
        $owner->addLine($third = $newLine());

        self::assertSame(0, $first->getPosition());
        self::assertSame(1, $second->getPosition());
        self::assertSame(2, $third->getPosition());
    }

    #[DataProvider('owners')]
    public function testRemovingALineFromTheMiddleClosesTheGap(callable $newOwner, callable $newLine): void
    {
        $owner = $newOwner();

        $owner->addLine($first = $newLine());
        $owner->addLine($middle = $newLine());
        $owner->addLine($last = $newLine());

        $owner->removeLine($middle);

        self::assertSame([0, 1], $this->positions($owner));
        self::assertSame(0, $first->getPosition());
        self::assertSame(1, $last->getPosition());
    }

    #[DataProvider('owners')]
    public function testInsertingAfterARemovalStillAppends(callable $newOwner, callable $newLine): void
    {
        $owner = $newOwner();

        $owner->addLine($newLine());
        $owner->addLine($middle = $newLine());
        $owner->addLine($newLine());
        $owner->removeLine($middle);

        $owner->addLine($added = $newLine());

        // 2, not 3: the removal renumbered, so there is no hole for the new line to fall into.
        self::assertSame(2, $added->getPosition());
        self::assertSame([0, 1, 2], $this->positions($owner));
    }

    #[DataProvider('owners')]
    public function testAnExplicitPositionDecidesWhereTheLineLands(callable $newOwner, callable $newLine): void
    {
        $owner = $newOwner();

        $owner->addLine($first = $newLine());
        $owner->addLine($second = $newLine());

        $jumpsTheQueue = $newLine();
        $jumpsTheQueue->setPosition(-1);

        $owner->addLine($jumpsTheQueue);

        self::assertSame(0, $jumpsTheQueue->getPosition());
        self::assertSame(1, $first->getPosition());
        self::assertSame(2, $second->getPosition());
    }

    /**
     * A drag-to-reorder submits new positions for lines that are already placed. A later add
     * has to honour those rather than fall back to the collection's own order.
     */
    #[DataProvider('owners')]
    public function testAddingAfterAReorderKeepsTheReorderedOrder(callable $newOwner, callable $newLine): void
    {
        $owner = $newOwner();

        $owner->addLine($first = $newLine());
        $owner->addLine($second = $newLine());
        $owner->addLine($third = $newLine());

        // The order a reordered submission would bind: the last line moved to the front.
        $third->setPosition(0);
        $first->setPosition(1);
        $second->setPosition(2);

        $owner->addLine($added = $newLine());

        self::assertSame(0, $third->getPosition());
        self::assertSame(1, $first->getPosition());
        self::assertSame(2, $second->getPosition());
        self::assertSame(3, $added->getPosition());
    }

    /**
     * Two lines claiming the same slot must not both keep it, and must not swap: the
     * collection's own order is the tie-break.
     */
    #[DataProvider('owners')]
    public function testDuplicatePositionsAreBrokenByCollectionOrder(callable $newOwner, callable $newLine): void
    {
        $owner = $newOwner();

        $owner->addLine($first = $newLine());

        $second = $newLine();
        $second->setPosition(0);

        $owner->addLine($second);

        self::assertSame(0, $first->getPosition());
        self::assertSame(1, $second->getPosition());
    }

    /**
     * A line that reached the collection without `addLine()` is placed by the owner's own
     * insert, not left on the sentinel — and several of them get a slot each rather than all
     * landing on the first.
     */
    #[DataProvider('owners')]
    public function testTheOwnerPlacesLinesThatSkippedAddLine(callable $newOwner, callable $newLine): void
    {
        $owner = $newOwner();

        $owner->addLine($placed = $newLine());
        $owner->getLines()->add($skipped = $newLine());
        $owner->getLines()->add($alsoSkipped = $newLine());

        $owner->updateLines();

        self::assertSame(0, $placed->getPosition());
        self::assertSame(1, $skipped->getPosition());
        self::assertSame(2, $alsoSkipped->getPosition());
    }

    /**
     * The line's own fallback, for the case the owner's insert cannot cover: a line persisted
     * against an owner that is already in the database.
     */
    #[DataProvider('attachedLines')]
    public function testALineAttachedWithoutAddLineAppends(callable $newOwner, callable $newLine, callable $attach): void
    {
        $owner = $newOwner();

        $owner->addLine($newLine());
        $owner->addLine($newLine());

        $attach($owner, $attached = $newLine());
        $attached->placeUnplacedLine();

        // 2, not 0 — the slot the collection's first line is holding.
        self::assertSame(2, $attached->getPosition());
    }

    #[DataProvider('owners')]
    public function testALineWithNoOwnerTakesTheFirstSlot(callable $newOwner, callable $newLine): void
    {
        $line = $newLine();

        $line->placeUnplacedLine();

        self::assertSame(0, $line->getPosition());
    }

    /**
     * @return iterable<string, array{callable(): object, callable(): LineInterface, callable(object, LineInterface): void}>
     */
    public static function attachedLines(): iterable
    {
        yield 'invoice' => [
            static fn (): Invoice => new Invoice(),
            static fn (): InvoiceLine => new InvoiceLine(),
            static function (object $owner, LineInterface $line): void {
                assert($owner instanceof Invoice && $line instanceof InvoiceLine);

                $line->setInvoice($owner);
            },
        ];

        yield 'recurring invoice' => [
            static fn (): RecurringInvoice => new RecurringInvoice(),
            static fn (): RecurringInvoiceLine => new RecurringInvoiceLine(),
            static function (object $owner, LineInterface $line): void {
                assert($owner instanceof RecurringInvoice && $line instanceof RecurringInvoiceLine);

                $line->setRecurringInvoice($owner);
            },
        ];

        yield 'quote' => [
            static fn (): Quote => new Quote(),
            static fn (): QuoteLine => new QuoteLine(),
            static function (object $owner, LineInterface $line): void {
                assert($owner instanceof Quote && $line instanceof QuoteLine);

                $line->setQuote($owner);
            },
        ];
    }

    /**
     * @return list<int>
     */
    private function positions(object $owner): array
    {
        // array_values, because removeElement() leaves the collection's keys sparse and only
        // the positions are under test here.
        return array_values(
            array_map(
                static fn (LineInterface $line): int => $line->getPosition(),
                $owner->getLines()->toArray()
            )
        );
    }
}
