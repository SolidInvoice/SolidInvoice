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

use Brick\Math\BigDecimal;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentMethodFactory;
use Symfony\Component\HttpFoundation\Response;

#[Group('functional')]
final class RecordPaymentTest extends ApiTestCase
{
    protected function getResourceClass(): string
    {
        return Payment::class;
    }

    public function testRecordPayment(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD']);
        $invoice = InvoiceFactory::createOne(['status' => InvoiceStatus::Pending, 'client' => $client]);

        PaymentMethodFactory::createOne([
            'factoryName' => 'offline',
            'enabled' => true,
            'internal' => false,
        ]);

        $response = self::$client->request('POST', $this->getIriFromResource($invoice) . '/payments', [
            'json' => ['amount' => 1000, 'currency' => 'USD'],
            'headers' => ['content-type' => 'application/ld+json', 'accept' => 'application/ld+json'],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $result = $response->toArray(false);

        self::assertArrayHasKey('id', $result);
        self::assertSame('captured', $result['status']);
        self::assertSame(1000, $result['totalAmount']);
        self::assertSame('USD', $result['currencyCode']);
        self::assertArrayNotHasKey('config', $result['method']);
    }

    /**
     * The invoice was issued while the client was in EUR and keeps that frozen currency even
     * though the client has since moved to USD. The payment must be validated against what the
     * invoice itself states, not the client's current currency.
     */
    public function testRecordPaymentAcceptsTheInvoicesFrozenCurrencyEvenAfterTheClientMoves(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD']);
        $invoice = InvoiceFactory::createOne([
            'status' => InvoiceStatus::Pending,
            'client' => $client,
            'currencyCode' => 'EUR',
        ]);

        PaymentMethodFactory::createOne([
            'factoryName' => 'offline',
            'enabled' => true,
            'internal' => false,
        ]);

        $response = self::$client->request('POST', $this->getIriFromResource($invoice) . '/payments', [
            'json' => ['amount' => 1000, 'currency' => 'EUR'],
            'headers' => ['content-type' => 'application/ld+json', 'accept' => 'application/ld+json'],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $result = $response->toArray(false);

        self::assertSame('EUR', $result['currencyCode']);
    }

    /**
     * The client's current USD is not what the invoice states, so a USD payment must be
     * rejected even though it matches the client's live currency.
     */
    public function testRecordPaymentRejectsTheClientsCurrentCurrencyWhenItDiffersFromTheFrozenInvoiceCurrency(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD']);
        $invoice = InvoiceFactory::createOne([
            'status' => InvoiceStatus::Pending,
            'client' => $client,
            'currencyCode' => 'EUR',
        ]);

        PaymentMethodFactory::createOne([
            'factoryName' => 'offline',
            'enabled' => true,
            'internal' => false,
        ]);

        self::$client->request('POST', $this->getIriFromResource($invoice) . '/payments', [
            'json' => ['amount' => 1000, 'currency' => 'USD'],
            'headers' => ['content-type' => 'application/ld+json', 'accept' => 'application/ld+json'],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * A client that never set a currency explicitly has a null `currencyCode` column, and an
     * unfrozen invoice falls through to the client, which in turn falls through to the
     * company's default currency (USD). This must resolve to a currency, not 422.
     *
     * {@see \SolidInvoice\ClientBundle\Listener\ClientListener::postLoad()} is what hydrates
     * `Client::$currency` from the default in this case, so the client must actually be
     * reloaded through it, not reused as the same in-memory object Foundry just built.
     */
    public function testRecordPaymentResolvesTheCompanyDefaultCurrencyWhenTheClientNeverSetOne(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => null]);
        $invoice = InvoiceFactory::createOne(['status' => InvoiceStatus::Pending, 'client' => $client]);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $entityManager->clear();

        PaymentMethodFactory::createOne([
            'factoryName' => 'offline',
            'enabled' => true,
            'internal' => false,
        ]);

        $response = self::$client->request('POST', $this->getIriFromResource($invoice) . '/payments', [
            'json' => ['amount' => 1000, 'currency' => 'USD'],
            'headers' => ['content-type' => 'application/ld+json', 'accept' => 'application/ld+json'],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $result = $response->toArray(false);

        self::assertSame('USD', $result['currencyCode']);
    }

    /**
     * A partial payment must not mark the invoice paid; it only reduces the balance.
     * Previously any positive amount applied the "pay" transition, and the next payment
     * was then refused with a 422 because the invoice was already paid.
     */
    public function testPartialPaymentsKeepTheInvoicePendingUntilItIsSettled(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD']);
        $invoice = InvoiceFactory::new([
            'status' => InvoiceStatus::Pending,
            'client' => $client,
            'paidDate' => null,
            'discount' => new Discount(),
        ])
            ->afterInstantiate(static function (Invoice $invoice): void {
                $invoice->addLine(new Line()->setQty(1)->setPrice(100000));
            })
            ->create();

        PaymentMethodFactory::createOne([
            'factoryName' => 'offline',
            'enabled' => true,
            'internal' => false,
        ]);

        $this->postPayment($invoice, 1);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $invoice = $this->reloadInvoice($invoice);
        self::assertSame(InvoiceStatus::Pending, $invoice->getStatus());
        self::assertTrue(BigDecimal::of(99999)->isEqualTo($invoice->getBalance()));
        self::assertNull($invoice->getPaidDate());

        $this->postPayment($invoice, 99999);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $invoice = $this->reloadInvoice($invoice);
        self::assertSame(InvoiceStatus::Paid, $invoice->getStatus());
        self::assertTrue($invoice->getBalance()->isZero());
        self::assertNotNull($invoice->getPaidDate());
    }

    private function reloadInvoice(Invoice $invoice): Invoice
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $entityManager->clear();

        $reloaded = $entityManager->find(Invoice::class, $invoice->getId());
        self::assertInstanceOf(Invoice::class, $reloaded);

        return $reloaded;
    }

    private function postPayment(Invoice $invoice, int $amount): void
    {
        self::$client->request('POST', $this->getIriFromResource($invoice) . '/payments', [
            'json' => ['amount' => $amount, 'currency' => 'USD'],
            'headers' => ['content-type' => 'application/ld+json', 'accept' => 'application/ld+json'],
        ]);
    }

    public function testCannotRecordPaymentForDraftInvoice(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD']);
        $invoice = InvoiceFactory::createOne(['status' => InvoiceStatus::Draft, 'client' => $client]);

        PaymentMethodFactory::createOne([
            'factoryName' => 'offline',
            'enabled' => true,
            'internal' => false,
        ]);

        self::$client->request(
            'POST',
            $this->getIriFromResource($invoice) . '/payments',
            [
                'json' => ['amount' => 1000, 'currency' => 'USD'],
                'headers' => [
                    'content-type' => 'application/ld+json',
                    'accept' => 'application/ld+json',
                ],
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testRecordPaymentForForeignCompanyInvoice(): void
    {
        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        $foreignClient = ClientFactory::createOne(['company' => $otherCompany]);
        $foreignInvoice = InvoiceFactory::createOne(['client' => $foreignClient, 'status' => InvoiceStatus::Pending]);
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        PaymentMethodFactory::createOne([
            'factoryName' => 'offline',
            'enabled' => true,
            'internal' => false,
        ]);

        self::$client->request(
            'POST',
            $this->getIriFromResource($foreignInvoice) . '/payments',
            [
                'json' => ['amount' => 1000, 'currency' => 'USD'],
                'headers' => [
                    'content-type' => 'application/ld+json',
                    'accept' => 'application/ld+json',
                ],
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
