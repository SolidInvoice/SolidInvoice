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

namespace SolidInvoice\PaymentBundle\Tests\Functional\Api;

use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentFactory;
use Zenstruck\Foundry\Test\Factories;

/**
 * @group functional
 */
final class PaymentTest extends ApiTestCase
{
    use Factories;

    protected function getResourceClass(): string
    {
        return Payment::class;
    }

    public function testGetPaymentsForInvoice(): void
    {
        $invoice = InvoiceFactory::createOne(['archived' => null, 'company' => $this->company])->object();
        $payment = PaymentFactory::createOne([
            'invoice' => $invoice,
            'status' => 'captured',
        ]);

        $data = $this->requestGet($this->getIriFromResource($invoice) . '/payments');

        self::assertSame([
            '@context' => '/api/contexts/Payment',
            '@id' => $this->getIriFromResource($invoice) . '/payments',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 1,
            'hydra:member' => [
                [
                    '@id' => $this->getIriFromResource($invoice) . '/payment/' . $payment->getId(),
                    '@type' => 'Payment',
                    'id' => $payment->getId()->toString(),
                    'invoice' => $this->getIriFromResource($invoice),
                    'client' => null,
                    'method' => null,
                    'status' => 'captured',
                    'message' => $payment->getMessage(),
                    'completed' => $payment->getCompleted()->format('c'),
                    'number' => $payment->getNumber(),
                    'description' => $payment->getDescription(),
                    'clientEmail' => $payment->getClientEmail(),
                    'clientId' => null,
                    'totalAmount' => $payment->getTotalAmount(),
                    'currencyCode' => $payment->getCurrencyCode(),
                    'details' => [],
                    'creditCard' => null,
                    'bankAccount' => null,
                    'created' => $payment->getCreated()->format('c'),
                    'updated' => $payment->getUpdated()->format('c'),
                    'amount' => [
                        'amount' => $payment->getAmount()->getAmount(),
                        'currency' => $payment->getAmount()->getCurrency()->getCode(),
                    ],
                ],
            ],
        ], $data);
    }

    /*public function testGetPaymentsForClient(): void
    {
        $client = ClientFactory::createOne(['archived' => null, 'company' => $this->company])->object();
        $payment = PaymentFactory::createOne([
            'client' => $client,
            'status' => 'captured',
        ]);

        $data = $this->requestGet($this->getIriFromResource($client) . '/payments');

        self::assertSame([
            '@context' => '/api/contexts/Payment',
            '@id' => $this->getIriFromResource($client) . '/payments',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 1,
            'hydra:member' => [
                [
                    '@id' => $this->getIriFromResource($client) . '/payment/' . $payment->getId(),
                    '@type' => 'Payment',
                    'id' => $payment->getId()->toString(),
                    'details' => [],
                    'description' => $payment->getDescription(),
                    'number' => $payment->getNumber(),
                    'clientEmail' => $payment->getClientEmail(),
                    'clientId' => null,
                    'invoice' => $this->getIriFromResource($client),
                    'client' => null,
                    'method' => null,
                    'status' => 'captured',
                    'message' => $payment->getMessage(),
                    'completed' => $payment->getCompleted()->format('c'),
                    'totalAmount' => $payment->getTotalAmount(),
                    'currencyCode' => $payment->getCurrencyCode(),
                    'creditCard' => null,
                    'bankAccount' => null,
                    'created' => $payment->getCreated()->format('c'),
                    'updated' => $payment->getUpdated()->format('c'),
                    'amount' => [
                        'amount' => $payment->getAmount()->getAmount(),
                        'currency' => $payment->getAmount()->getCurrency()->getCode(),
                    ],
                ],
            ],
        ], $data);
    }*/

    public function testGetFromInvoice(): void
    {
        $invoice = InvoiceFactory::createOne(['archived' => null, 'company' => $this->company])->object();
        $payment = PaymentFactory::createOne([
            'invoice' => $invoice,
            'status' => 'captured',
        ]);

        $data = $this->requestGet($this->getIriFromResource($invoice) . '/payment/' . $payment->getId()->toString());

        self::assertSame([
            '@context' => '/api/contexts/Payment',
            '@id' => $this->getIriFromResource($invoice) . '/payment/' . $payment->getId(),
            '@type' => 'Payment',
            'id' => $payment->getId()->toString(),
            'invoice' => $this->getIriFromResource($invoice),
            'client' => null,
            'method' => null,
            'status' => 'captured',
            'message' => $payment->getMessage(),
            'completed' => $payment->getCompleted()->format('c'),
            'number' => $payment->getNumber(),
            'description' => $payment->getDescription(),
            'clientEmail' => $payment->getClientEmail(),
            'clientId' => null,
            'totalAmount' => $payment->getTotalAmount(),
            'currencyCode' => $payment->getCurrencyCode(),
            'details' => [],
            'creditCard' => null,
            'bankAccount' => null,
            'created' => $payment->getCreated()->format('c'),
            'updated' => $payment->getUpdated()->format('c'),
            'amount' => [
                'amount' => $payment->getAmount()->getAmount(),
                'currency' => $payment->getAmount()->getCurrency()->getCode(),
            ],
        ], $data);
    }
}
