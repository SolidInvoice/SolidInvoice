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

namespace SolidInvoice\CoreBundle\Tests\Functional;

use Brick\Math\BigDecimal;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Doctrine\Type\QuantityType;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\CoreBundle\Tests\Fixtures\LineQuantityCorpus;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\QuoteBundle\Entity\Line as QuoteLine;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function sprintf;

/**
 * Fractional quantities have to survive a round trip through the database unchanged, and
 * the line total recomputed from a reloaded quantity has to match the one that was stored.
 *
 * @see QuantityType
 */
#[CoversClass(QuantityType::class)]
#[Group('functional')]
final class LineQuantityPersistenceTest extends KernelTestCase
{
    use DoctrineTestTrait;

    /**
     * @return iterable<string, array{string}>
     */
    public static function fullScaleQuantities(): iterable
    {
        foreach (LineQuantityCorpus::fullScaleQuantities() as $qty) {
            yield $qty => [$qty];
        }
    }

    #[DataProvider('fullScaleQuantities')]
    public function testAnInvoiceLineQuantityRoundTripsExactly(string $qty): void
    {
        $invoice = new Invoice();
        $invoice->setClient(ClientFactory::createOne(['currencyCode' => 'USD']));
        $invoice->setStatus(InvoiceStatus::Draft);

        $line = new Line();
        $line->setDescription('Metered usage')
            ->setPrice(100000)
            ->setQty($qty)
            ->updateTotal();

        $invoice->addLine($line);

        $storedTotal = (string) $line->getTotal();

        $this->em->persist($invoice);
        $this->em->flush();

        $id = $line->getId();
        $this->em->clear();

        $reloaded = $this->em->find(Line::class, $id);

        self::assertInstanceOf(Line::class, $reloaded);
        self::assertTrue(
            BigDecimal::of($qty)->isEqualTo($reloaded->getQty()),
            sprintf('Expected %s, got %s', $qty, (string) $reloaded->getQty())
        );
        self::assertTrue(
            $reloaded->updateTotal()->getTotal()->isEqualTo(BigDecimal::of($storedTotal)),
            'The total recomputed from the reloaded quantity does not match the stored one'
        );
    }

    #[DataProvider('fullScaleQuantities')]
    public function testAQuoteLineQuantityRoundTripsExactly(string $qty): void
    {
        $quote = new Quote();
        $quote->setClient(ClientFactory::createOne(['currencyCode' => 'USD']));
        $quote->setStatus(QuoteStatus::Draft);

        $line = new QuoteLine();
        $line->setDescription('Metered usage')
            ->setPrice(100000)
            ->setQty($qty)
            ->updateTotal();

        $quote->addLine($line);

        $this->em->persist($quote);
        $this->em->flush();

        $id = $line->getId();
        $this->em->clear();

        $reloaded = $this->em->find(QuoteLine::class, $id);

        self::assertInstanceOf(QuoteLine::class, $reloaded);
        self::assertTrue(
            BigDecimal::of($qty)->isEqualTo($reloaded->getQty()),
            sprintf('Expected %s, got %s', $qty, (string) $reloaded->getQty())
        );
    }

    /**
     * The line total is computed in `PrePersist`, before DBAL converts the quantity. If the
     * quantity were not already at the column scale by then, the stored total would be
     * computed from digits the column drops, and recomputing after a reload would move the
     * invoice. The price here is large enough that a difference lands in the minor unit.
     */
    public function testTheStoredTotalMatchesTheStoredQuantity(): void
    {
        $invoice = new Invoice();
        $invoice->setClient(ClientFactory::createOne(['currencyCode' => 'USD']));
        $invoice->setStatus(InvoiceStatus::Draft);

        $line = new Line();
        $line->setDescription('Sub-scale usage')
            ->setPrice(100_000_000)
            ->setQty('1.23456749')
            ->updateTotal();

        $invoice->addLine($line);

        $storedTotal = (string) $line->getTotal();

        $this->em->persist($invoice);
        $this->em->flush();

        $id = $line->getId();
        $this->em->clear();

        $reloaded = $this->em->find(Line::class, $id);

        self::assertInstanceOf(Line::class, $reloaded);
        self::assertTrue(
            $reloaded->updateTotal()->getTotal()->isEqualTo(BigDecimal::of($storedTotal)),
            sprintf(
                'Recomputing from the stored quantity gave %s, but %s was stored',
                (string) $reloaded->getTotal(),
                $storedTotal
            )
        );
    }

    /**
     * A quantity beyond the column scale is rounded once, on the way in, rather than
     * drifting differently on every read.
     */
    public function testAQuantityBeyondTheScaleIsRoundedOnce(): void
    {
        $invoice = new Invoice();
        $invoice->setClient(ClientFactory::createOne(['currencyCode' => 'USD']));
        $invoice->setStatus(InvoiceStatus::Draft);

        $line = new Line();
        $line->setDescription('Sub-scale usage')
            ->setPrice(100)
            ->setQty('1.23456749')
            ->updateTotal();

        $invoice->addLine($line);

        $this->em->persist($invoice);
        $this->em->flush();

        $id = $line->getId();
        $this->em->clear();

        $reloaded = $this->em->find(Line::class, $id);

        self::assertInstanceOf(Line::class, $reloaded);
        self::assertTrue(BigDecimal::of('1.234567')->isEqualTo($reloaded->getQty()));

        $this->em->flush();
        $this->em->clear();

        $reread = $this->em->find(Line::class, $id);

        self::assertInstanceOf(Line::class, $reread);
        self::assertTrue(BigDecimal::of('1.234567')->isEqualTo($reread->getQty()));
    }
}
