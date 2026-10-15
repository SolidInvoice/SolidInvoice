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

namespace SolidInvoice\InvoiceBundle\Tests\Twig\Components;

use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Test\LiveComponentTest;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Enum\PaymentTerms;
use SolidInvoice\InvoiceBundle\Twig\Components\CreateRecurringInvoice;

final class CreateRecurringInvoiceTest extends LiveComponentTest
{
    public function testChoosingAClientAppliesItsPaymentTerms(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD', 'paymentTerms' => PaymentTerms::Net60]);

        $component = $this->createLiveComponent(
            name: CreateRecurringInvoice::class,
            data: ['invoice' => new RecurringInvoice()->setPaymentTerms(PaymentTerms::Net30)]
        )->actingAs($this->getUser());

        $component->set('recurring_invoice.client', (string) $client->getId());

        self::assertSame(PaymentTerms::Net60->value, $component->component()->formValues['paymentTerms']);

        // Picking terms by hand afterwards sticks: only a client change resets them.
        $component->set('recurring_invoice.paymentTerms', PaymentTerms::Net7->value);

        self::assertSame(PaymentTerms::Net7->value, $component->component()->formValues['paymentTerms']);
    }
}
