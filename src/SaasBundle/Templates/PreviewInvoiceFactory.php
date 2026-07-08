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

namespace SolidInvoice\SaasBundle\Templates;

use Carbon\CarbonImmutable;
use ReflectionProperty;
use SolidInvoice\ClientBundle\Entity\Address;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

/**
 * Builds a purely in-memory invoice with plausible sample data so template
 * previews can render every design without touching the database. The company
 * details (name, logo, address) still come from the active company via the
 * usual Twig helpers, so the preview looks like the user's own invoice.
 *
 * @see \SolidInvoice\SaasBundle\Tests\Templates\PreviewInvoiceFactoryTest
 */
final readonly class PreviewInvoiceFactory
{
    public function __construct(
        private SystemConfig $systemConfig,
    ) {
    }

    public function create(): Invoice
    {
        $client = new Client();
        $client->setName('Acme Studios');
        $client->setCurrencyCode($this->systemConfig->getCurrency()->getCode());

        $contact = new Contact();
        $contact->setFirstName('Jane');
        $contact->setLastName('Doe');
        $contact->setEmail('jane.doe@example.com');

        $client->addContact($contact);

        $address = new Address();
        $address->setStreet1('742 Evergreen Terrace');
        $address->setCity('Springfield');
        $address->setZip('49007');
        $address->setCountry('US');

        $client->addAddress($address);

        $invoice = new Invoice();
        $invoice->setInvoiceId('INV-2025-0042');
        $invoice->setUuid(Uuid::v4());
        $invoice->setStatus(InvoiceStatus::Pending);
        $invoice->setClient($client);
        $invoice->setInvoiceDate(CarbonImmutable::parse('first day of this month'));
        $invoice->setDue(CarbonImmutable::now()->addDays(14));
        $invoice->setTerms("Payment due within 14 days.\nBank transfer or card accepted.");
        $invoice->setDiscount(new Discount()->setType(null));

        foreach ([
            ['Brand identity design', 120000, 1.0],
            ['Landing page implementation', 85000, 1.0],
            ['Consulting & support', 15000, 3.0],
        ] as [$description, $price, $qty]) {
            $line = new Line();
            $line->setDescription($description);
            $line->setPrice($price);
            $line->setQty($qty);
            $line->setTotal((int) ($price * $qty));

            $invoice->addLine($line);
        }

        $invoice->setBaseTotal(250000);
        $invoice->setTax(0);
        $invoice->setTotal(250000);
        $invoice->setBalance(250000);

        // Some render paths (e.g. the custom-fields component) require a
        // non-null id; the invoice is never persisted so any Ulid will do.
        new ReflectionProperty(Invoice::class, 'id')->setValue($invoice, new Ulid());

        return $invoice;
    }
}
