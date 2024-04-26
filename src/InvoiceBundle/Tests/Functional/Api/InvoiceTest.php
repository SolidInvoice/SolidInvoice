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

use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Ramsey\Uuid\Uuid;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\DataFixtures\ORM\LoadData as LoadClientData;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\InvoiceBundle\DataFixtures\ORM\LoadData as LoadInvoiceData;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use function assert;
use function date;

/**
 * @group functional
 */
final class InvoiceTest extends ApiTestCase
{
    private AbstractExecutor $executor;

    protected function setUp(): void
    {
        parent::setUp();

        $databaseTool = self::getContainer()->get(DatabaseToolCollection::class)->get();

        $this->executor = $databaseTool->loadFixtures([
            LoadClientData::class,
            LoadInvoiceData::class,
        ], true);
    }

    public function testCreate(): void
    {
        $contact = $this->executor->getReferenceRepository()->getReference('contact');
        assert($contact instanceof Contact);

        $data = [
            'users' => [
                '/api/contacts/' . $contact->getId(),
            ],
            'client' => '/api/clients/' . $contact->getClient()->getId(),
            'discount' => [
                'type' => 'percentage',
                'value' => 10,
            ],
            'items' => [
                [
                    'price' => 100,
                    'qty' => 1,
                    'description' => 'Foo Item',
                ],
            ],
        ];

        $result = $this->requestPost('/api/invoices', $data);

        self::assertTrue(Uuid::isValid($result['id']));
        self::assertTrue(Uuid::isValid($result['uuid']));
        self::assertTrue(Uuid::isValid($result['items'][0]['id']));

        unset($result['id'], $result['uuid'], $result['items'][0]['id']);

        self::assertSame([
            'client' => '/api/clients/' . $contact->getClient()->getId(),
            'balance' => 90,
            'due' => null,
            'invoiceDate' => date('Y-m-d\T00:00:00+02:00'),
            'paidDate' => null,
            'items' => [
                [
                    'description' => 'Foo Item',
                    'price' => 100,
                    'qty' => 1,
                    'tax' => null,
                    'total' => 100,
                ],
            ],
            'users' => [
                '/api/contacts/' . $contact->getId(),
            ],
            'status' => 'draft',
            'total' => 90,
            'baseTotal' => 100,
            'tax' => 0,
            'discount' => [
                'type' => 'percentage',
                'value' => 10,
            ],
            'terms' => null,
            'notes' => null,
        ], $result);
    }

    public function testDelete(): void
    {
        $invoice = $this->executor->getReferenceRepository()->getReference('invoice');
        assert($invoice instanceof Invoice);

        $this->requestDelete('/api/invoices/' . $invoice->getId());
    }

    public function testGet(): void
    {
        $invoice = $this->executor->getReferenceRepository()->getReference('invoice');
        assert($invoice instanceof Invoice);

        $data = $this->requestGet('/api/invoices/' . $invoice->getId());

        self::assertSame([
            'id' => $invoice->getId()->toString(),
            'uuid' => $invoice->getUuid()->toString(),
            'client' => '/api/clients/' . $invoice->getClient()->getId(),
            'balance' => 100,
            'due' => null,
            'invoiceDate' => date('Y-m-d\T00:00:00+02:00'),
            'paidDate' => null,
            'items' => [
                [
                    'id' => $invoice->getItems()->first()->getId()->toString(),
                    'description' => 'Test Item',
                    'price' => 100,
                    'qty' => 1,
                    'tax' => null,
                    'total' => 100,
                ],
            ],
            'users' => [
                '/api/contacts/' . $invoice->getUsers()->first()->getId(),
            ],
            'status' => 'draft',
            'total' => 100,
            'baseTotal' => 100,
            'tax' => 0,
            'discount' => [
                'type' => null,
                'value' => 0,
            ],
            'terms' => null,
            'notes' => null,
        ], $data);
    }

    public function testEdit(): void
    {
        $invoice = $this->executor->getReferenceRepository()->getReference('invoice');
        assert($invoice instanceof Invoice);

        $data = $this->requestPut(
            '/api/invoices/' . $invoice->getId(),
            [
                'discount' => [
                    'type' => 'percentage',
                    'value' => 10,
                ],
                'items' => [
                    [
                        'price' => 100,
                        'qty' => 1,
                        'description' => 'Foo Item',
                    ],
                ],
            ]
        );

        self::assertSame([
            'id' => $invoice->getId()->toString(),
            'uuid' => $invoice->getUuid()->toString(),
            'client' => '/api/clients/' . $invoice->getClient()->getId(),
            'balance' => 90,
            'due' => null,
            'invoiceDate' => date('Y-m-d\T00:00:00+02:00'),
            'paidDate' => null,
            'items' => [
                [
                    'id' => $invoice->getItems()->first()->getId()->toString(),
                    'description' => 'Foo Item',
                    'price' => 100,
                    'qty' => 1,
                    'tax' => null,
                    'total' => 100,
                ],
            ],
            'users' => [
                '/api/contacts/' . $invoice->getUsers()->first()->getId(),
            ],
            'status' => 'draft',
            'total' => 90,
            'baseTotal' => 100,
            'tax' => 0,
            'discount' => [
                'type' => 'percentage',
                'value' => 10,
            ],
            'terms' => null,
            'notes' => null,
        ], $data);
    }
}
