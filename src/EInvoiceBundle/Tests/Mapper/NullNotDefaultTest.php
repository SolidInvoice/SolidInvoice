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

namespace SolidInvoice\EInvoiceBundle\Tests\Mapper;

use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\EInvoiceBundle\Mapper\BuyerMapper;
use SolidInvoice\EInvoiceBundle\Mapper\InvoiceLineMapper;
use SolidInvoice\EInvoiceBundle\Mapper\InvoiceMapper;
use SolidInvoice\EInvoiceBundle\Mapper\MinorUnitConverter;
use SolidInvoice\EInvoiceBundle\Mapper\SellerMapper;
use SolidInvoice\EInvoiceBundle\Mapper\VatBreakdownMapper;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\MoneyBundle\Calculator;
use SolidInvoice\MoneyBundle\Currency\CurrencyScale;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\TaxBundle\Calculator\TaxCalculatorInterface;
use SolidInvoice\TaxBundle\Entity\LineTax;
use SolidInvoice\TaxBundle\Enum\TaxCategory;
use SolidInvoice\TaxBundle\Enum\TaxType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The acceptance criterion in its own test (design §6, §9). A minimal invoice — one taxed line,
 * no discount, no header references, no delivery/payment/payee data — maps to an {@see EInvoice}
 * where every term design §6 lists as "deliberately not sourced" is exactly `null` (or `[]` for a
 * repeatable group), never a fabricated default.
 */
final class NullNotDefaultTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testEveryDeliberatelyOmittedTermIsNullNotDefaulted(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'EUR',
        ]);

        /** @var Invoice $invoice */
        $invoice = InvoiceFactory::new()
            ->withoutPersisting()
            ->create([
                'company' => $this->company,
                'client' => $client,
                'due' => null,
                'notes' => null,
                'total' => 1200,
                'baseTotal' => 1000,
                'tax' => 200,
                'balance' => 1200,
                'discount' => new Discount(),
            ]);

        $line = new Line();
        $line->setName('Consulting');
        $line->setPrice(1000);
        $line->setQty(1);
        $line->updateTotal();

        $lineTax = new LineTax();
        $lineTax->setNameSnapshot('VAT');
        $lineTax->setRateSnapshot('20.0000');
        $lineTax->setTypeSnapshot(TaxType::Exclusive);
        $lineTax->setCategorySnapshot(TaxCategory::Standard);
        $lineTax->setCompound(false);
        $lineTax->setSequence(0);

        $line->addTax($lineTax);

        $invoice->addLine($line);

        $eInvoice = $this->mapper()->map($invoice);

        // EInvoice root.
        self::assertNull($eInvoice->vatAccountingCurrencyCode);
        self::assertNull($eInvoice->taxPointDate);
        self::assertNull($eInvoice->vatPointDateCode);
        self::assertNull($eInvoice->buyerReference);
        self::assertNull($eInvoice->projectReference);
        self::assertNull($eInvoice->contractReference);
        self::assertNull($eInvoice->purchaseOrderReference);
        self::assertNull($eInvoice->salesOrderReference);
        self::assertNull($eInvoice->receivingAdviceReference);
        self::assertNull($eInvoice->despatchAdviceReference);
        self::assertNull($eInvoice->tenderOrLotReference);
        self::assertNull($eInvoice->invoicedObjectIdentifier);
        self::assertNull($eInvoice->buyerAccountingReference);
        self::assertNull($eInvoice->businessProcessType);
        self::assertNull($eInvoice->specificationIdentifier);
        self::assertNull($eInvoice->payee);
        self::assertNull($eInvoice->sellerTaxRepresentative);
        self::assertNull($eInvoice->deliveryInformation);
        self::assertNull($eInvoice->paymentInstructions);
        self::assertSame([], $eInvoice->precedingInvoiceReferences);
        self::assertSame([], $eInvoice->charges);
        self::assertSame([], $eInvoice->additionalSupportingDocuments);

        // Seller / Buyer.
        self::assertNull($eInvoice->seller->tradingName);
        self::assertSame([], $eInvoice->seller->identifiers);
        self::assertNull($eInvoice->seller->legalRegistrationIdentifier);
        self::assertNull($eInvoice->seller->taxRegistrationIdentifier);
        self::assertNull($eInvoice->seller->additionalLegalInformation);
        self::assertNull($eInvoice->seller->electronicAddress);
        self::assertNull($eInvoice->buyer->tradingName);
        self::assertNull($eInvoice->buyer->identifier);
        self::assertNull($eInvoice->buyer->legalRegistrationIdentifier);
        self::assertNull($eInvoice->buyer->electronicAddress);

        // DocumentTotals.
        self::assertNull($eInvoice->documentTotals->sumOfAllowances);
        self::assertNull($eInvoice->documentTotals->sumOfCharges);
        self::assertNull($eInvoice->documentTotals->vatAmountInAccountingCurrency);
        self::assertNull($eInvoice->documentTotals->roundingAmount);

        // VatBreakdown (BG-23) — see VatBreakdownMapperTest for the dedicated coverage.
        self::assertCount(1, $eInvoice->vatBreakdowns);
        self::assertNull($eInvoice->vatBreakdowns[0]->taxableAmount);
        self::assertNull($eInvoice->vatBreakdowns[0]->exemptionReasonText);
        self::assertNull($eInvoice->vatBreakdowns[0]->exemptionReasonCode);

        // InvoiceLine (BG-25/BG-29/BG-31).
        self::assertCount(1, $eInvoice->invoiceLines);
        $invoiceLine = $eInvoice->invoiceLines[0];
        self::assertNull($invoiceLine->note);
        self::assertNull($invoiceLine->objectIdentifier);
        self::assertNull($invoiceLine->unitOfMeasureCode);
        self::assertNull($invoiceLine->orderLineReference);
        self::assertNull($invoiceLine->buyerAccountingReference);
        self::assertNull($invoiceLine->invoicingPeriod);
        self::assertSame([], $invoiceLine->allowances);
        self::assertSame([], $invoiceLine->charges);
        self::assertNull($invoiceLine->price->discount);
        self::assertNull($invoiceLine->price->grossPrice);
        self::assertNull($invoiceLine->price->baseQuantity);
        self::assertNull($invoiceLine->price->baseQuantityUnitCode);
        self::assertNull($invoiceLine->item->description);
        self::assertNull($invoiceLine->item->sellerIdentifier);
        self::assertNull($invoiceLine->item->buyerIdentifier);
        self::assertNull($invoiceLine->item->standardIdentifier);
        self::assertNull($invoiceLine->item->classificationIdentifier);
        self::assertNull($invoiceLine->item->originCountryCode);
        self::assertSame([], $invoiceLine->item->attributes);

        // BG-20 — no discount was set.
        self::assertSame([], $eInvoice->allowances);
    }

    private function mapper(): InvoiceMapper
    {
        $systemConfig = self::getContainer()->get(SystemConfig::class);
        $converter = new MinorUnitConverter(new CurrencyScale());

        return new InvoiceMapper(
            new SellerMapper($systemConfig),
            new BuyerMapper(),
            new InvoiceLineMapper($converter),
            new VatBreakdownMapper($converter),
            $converter,
            self::getContainer()->get(TaxCalculatorInterface::class),
            self::getContainer()->get(Calculator::class),
            $systemConfig,
        );
    }
}
