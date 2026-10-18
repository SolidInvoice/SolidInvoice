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

namespace SolidInvoice\InvoiceBundle\Tests\Functional\Api;

use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use function array_map;

/**
 * Settles design §9 of SOL-77: proves API Platform round-trips
 * {@see \SolidInvoice\CoreBundle\Entity\BillingPeriod}'s dates without a custom normalizer.
 */
#[Group('functional')]
final class BillingPeriodTest extends ApiTestCase
{
    protected function getResourceClass(): string
    {
        return Invoice::class;
    }

    public function testCreateWithPeriodRoundTrips(): void
    {
        $client = ClientFactory::createOne();
        $contacts = array_map(
            $this->getIriFromResource(...),
            ContactFactory::createMany(1, ['client' => $client])
        );

        $result = $this->requestPost('/api/invoices', [
            'users' => $contacts,
            'client' => $this->getIriFromResource($client),
            'period' => [
                'start' => '2026-02-01',
                'end' => '2026-02-28',
            ],
            'lines' => [
                [
                    'price' => 100,
                    'qty' => 1,
                    'name' => 'Foo Item',
                ],
            ],
        ]);

        self::assertSame([
            'start' => '2026-02-01T00:00:00+02:00',
            'end' => '2026-02-28T00:00:00+02:00',
        ], $result['period']);

        $data = $this->requestGet($result['@id']);

        self::assertSame([
            'start' => '2026-02-01T00:00:00+02:00',
            'end' => '2026-02-28T00:00:00+02:00',
        ], $data['period']);
    }

    public function testGetWithoutPeriodSerialisesExplicitNulls(): void
    {
        $client = ClientFactory::createOne();
        $contacts = ContactFactory::createMany(1, ['client' => $client]);
        $invoice = InvoiceFactory::createOne([
            'client' => $client,
            'users' => $contacts,
            'lines' => [new Line()->setName('Test Item')->setQty(1)->setPrice(10000)],
        ]);

        $data = $this->requestGet($this->getIriFromResource($invoice));

        self::assertArrayHasKey('period', $data);
        self::assertSame(['start' => null, 'end' => null], $data['period']);
    }

    public function testPatchPeriod(): void
    {
        $client = ClientFactory::createOne();
        $contacts = ContactFactory::createMany(1, ['client' => $client]);
        $invoice = InvoiceFactory::createOne([
            'client' => $client,
            'users' => $contacts,
            'lines' => [new Line()->setName('Test Item')->setQty(1)->setPrice(10000)],
        ]);

        $data = $this->requestPatch($this->getIriFromResource($invoice), [
            'period' => [
                'start' => '2026-03-01',
                'end' => '2026-03-31',
            ],
        ]);

        self::assertSame([
            'start' => '2026-03-01T00:00:00+02:00',
            'end' => '2026-03-31T00:00:00+02:00',
        ], $data['period']);
    }

    public function testPatchPeriodBackToEmptySerialisesExplicitNulls(): void
    {
        $client = ClientFactory::createOne();
        $contacts = ContactFactory::createMany(1, ['client' => $client]);
        $invoice = InvoiceFactory::createOne([
            'client' => $client,
            'users' => $contacts,
            'lines' => [new Line()->setName('Test Item')->setQty(1)->setPrice(10000)],
        ]);

        $this->requestPatch($this->getIriFromResource($invoice), [
            'period' => [
                'start' => '2026-03-01',
                'end' => '2026-03-31',
            ],
        ]);

        $data = $this->requestPatch($this->getIriFromResource($invoice), [
            'period' => [
                'start' => null,
                'end' => null,
            ],
        ]);

        self::assertSame(['start' => null, 'end' => null], $data['period']);
    }
}
