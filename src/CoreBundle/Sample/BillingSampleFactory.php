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

namespace SolidInvoice\CoreBundle\Sample;

use Brick\Math\BigInteger;
use DateTimeImmutable;
use Money\Currency;
use SolidInvoice\ClientBundle\Entity\Address;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Enum\ClientStatus;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line as InvoiceLine;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\QuoteBundle\Entity\Line as QuoteLine;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

/**
 * Builds in-memory sample Invoice / Quote graphs for the billing template
 * preview action. Nothing is persisted; entities only need to satisfy
 * the read accesses performed by the default templates.
 *
 * @see \SolidInvoice\CoreBundle\Tests\Sample\BillingSampleFactoryTest
 */
final class BillingSampleFactory
{
    private const SAMPLE_ULID = '01JBYEQCR7DJ2YW4EXP6FYJZCR';

    private const SAMPLE_UUID = '91656880-2d93-11ef-933f-5a2cf21a5680';

    public function createInvoice(): Invoice
    {
        $invoice = new Invoice();
        $invoice->setId(Ulid::fromString(self::SAMPLE_ULID));
        $invoice->setUuid(Uuid::fromString(self::SAMPLE_UUID));
        $invoice->setInvoiceId('INV-PREVIEW-0001');
        $invoice->setInvoiceDate(new DateTimeImmutable('2026-01-15'));
        $invoice->setDue(new DateTimeImmutable('2026-02-15'));
        $invoice->setBalance(BigInteger::of(12000));
        $invoice->setClient($this->buildSampleClient());
        $invoice->setStatus(InvoiceStatus::Pending);
        $invoice->setCreated(new DateTimeImmutable('2026-01-15'));
        $invoice->setTotal(BigInteger::of(12000));
        $invoice->setBaseTotal(BigInteger::of(12000));
        $invoice->setTax(BigInteger::zero());
        $invoice->setDiscount(new Discount());
        $invoice->setTerms('Payment due within 30 days.');
        $invoice->setNotes('Thank you for your business.');

        $line = (new InvoiceLine())
            ->setDescription('Consulting services')
            ->setPrice(BigInteger::of(10000))
            ->setQty(1.0)
            ->setTotal(BigInteger::of(10000));

        $invoice->addLine($line);

        $line2 = (new InvoiceLine())
            ->setDescription('Hosting (monthly)')
            ->setPrice(BigInteger::of(2000))
            ->setQty(1.0)
            ->setTotal(BigInteger::of(2000));

        $invoice->addLine($line2);

        return $invoice;
    }

    public function createQuote(): Quote
    {
        $quote = new Quote();
        $quote->setQuoteId('QUOT-PREVIEW-0001');
        $quote->setId(Ulid::fromString(self::SAMPLE_ULID));
        $quote->setUuid(Uuid::fromString(self::SAMPLE_UUID));
        $quote->setStatus(QuoteStatus::Pending);
        $quote->setCreated(new DateTimeImmutable('2026-01-15'));
        $quote->setTotal(BigInteger::of(12000));
        $quote->setBaseTotal(BigInteger::of(12000));
        $quote->setTax(BigInteger::zero());
        $quote->setDiscount(new Discount());
        $quote->setTerms('Quote valid for 14 days.');
        $quote->setNotes('Thank you for the opportunity.');
        $quote->setClient($this->buildSampleClient());

        $line = (new QuoteLine())
            ->setDescription('Initial design')
            ->setPrice(BigInteger::of(8000))
            ->setQty(1.0)
            ->setTotal(BigInteger::of(8000));

        $quote->addLine($line);

        $line2 = (new QuoteLine())
            ->setDescription('Implementation')
            ->setPrice(BigInteger::of(4000))
            ->setQty(1.0)
            ->setTotal(BigInteger::of(4000));

        $quote->addLine($line2);

        return $quote;
    }

    private function buildSampleClient(): Client
    {
        $client = new Client();
        $client
            ->setName('Sample Client Inc')
            ->setWebsite('https://example.com')
            ->setStatus(ClientStatus::Active)
            ->setCurrency(new Currency('USD'))
            ->setCurrencyCode('USD');

        $contact = (new Contact())
            ->setFirstName('Sam')
            ->setLastName('Sampleton')
            ->setEmail('sam@example.com');

        $client->addContact($contact);

        $address = (new Address())
            ->setStreet1('123 Sample Street')
            ->setStreet2('Suite 100')
            ->setCity('Sampletown')
            ->setState('CA')
            ->setZip('90001')
            ->setCountry('US');

        $client->addAddress($address);

        return $client;
    }
}
