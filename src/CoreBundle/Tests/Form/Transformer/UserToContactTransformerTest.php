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

namespace SolidInvoice\CoreBundle\Tests\Form\Transformer;

use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Form\Transformer\UserToContactTransformer;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\InvoiceContact;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoiceContact;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Entity\QuoteContact;

/**
 * @coversDefaultClass \SolidInvoice\CoreBundle\Form\Transformer\UserToContactTransformer
 */
final class UserToContactTransformerTest extends TestCase
{
    public function testReverseTransform(): void
    {
        $entity = new Quote();
        $transformer = new UserToContactTransformer($entity, QuoteContact::class);

        self::assertNull($transformer->transform(null));
        self::assertTrue($transformer->transform(true));
        self::assertFalse($transformer->transform(false));
        self::assertSame(1, $transformer->transform(1));
        self::assertSame(1.0, $transformer->transform(1.0));
        self::assertSame('foo', $transformer->transform('foo'));
        self::assertSame([], $transformer->transform([]));
        self::assertSame([1, 'b', true], $transformer->transform([1, 'b', true]));
        self::assertSame($entity, $transformer->transform($entity));
    }

    public function testTransform(): void
    {
        $entity = new Quote();
        $transformer = new UserToContactTransformer(new Quote(), QuoteContact::class);

        self::assertNull($transformer->reverseTransform(null));
        self::assertTrue($transformer->reverseTransform(true));
        self::assertFalse($transformer->reverseTransform(false));
        self::assertSame(1, $transformer->reverseTransform(1));
        self::assertSame(1.0, $transformer->reverseTransform(1.0));
        self::assertSame('foo', $transformer->reverseTransform('foo'));
        self::assertSame([], $transformer->reverseTransform([]));
        self::assertSame($entity, $transformer->reverseTransform($entity));

        self::assertContainsOnlyInstancesOf(
            QuoteContact::class,
            (new UserToContactTransformer(new Quote(), QuoteContact::class))->reverseTransform([new Contact()])
        );

        self::assertContainsOnlyInstancesOf(
            InvoiceContact::class,
            (new UserToContactTransformer(new Invoice(), InvoiceContact::class))->reverseTransform([new Contact()])
        );

        self::assertContainsOnlyInstancesOf(
            RecurringInvoiceContact::class,
            (new UserToContactTransformer(new RecurringInvoice(), RecurringInvoiceContact::class))->reverseTransform([new Contact()])
        );

        $entity = new Quote();
        $contact = new Contact();

        foreach ((new UserToContactTransformer($entity, QuoteContact::class))->reverseTransform([$contact]) as $quoteContact) {
            self::assertSame($entity, $quoteContact->getQuote());
            self::assertSame($contact, $quoteContact->getContact());
        }

        $entity = new Invoice();
        $contact = new Contact();

        foreach ((new UserToContactTransformer($entity, InvoiceContact::class))->reverseTransform([$contact]) as $invoiceContact) {
            self::assertSame($entity, $invoiceContact->getInvoice());
            self::assertSame($contact, $invoiceContact->getContact());
        }

        $entity = new RecurringInvoice();
        $contact = new Contact();

        foreach ((new UserToContactTransformer($entity, RecurringInvoiceContact::class))->reverseTransform([$contact]) as $recurringInvoice) {
            self::assertSame($entity, $recurringInvoice->getRecurringInvoice());
            self::assertSame($contact, $recurringInvoice->getContact());
        }
    }
}
