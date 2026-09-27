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

namespace SolidInvoice\InvoiceBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Listener\Doctrine\InvoiceCurrencyGuardListener;
use SolidInvoice\InvoiceBundle\Listener\FreezeInvoiceCurrencyListener;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Reproduces SOL-298: an issued invoice must keep the currency it was issued in, even
 * after the client's currency (default or explicit) changes underneath it.
 */
#[CoversClass(FreezeInvoiceCurrencyListener::class)]
#[CoversClass(InvoiceCurrencyGuardListener::class)]
final class InvoiceCurrencyFreezeTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    /**
     * Fails before the fix: without freezing, `getCurrency()` falls through to the
     * client's live currency, which now reflects the changed system default.
     */
    public function testAcceptedInvoiceKeepsItsCurrencyAfterTheDefaultCurrencyChanges(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => null, 'company' => $this->company]);
        $invoiceId = InvoiceFactory::createOne([
            'status' => InvoiceStatus::Draft,
            'client' => $client,
            'company' => $this->company,
        ])->getId();

        $container = self::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        // Reload rather than reuse the in-memory objects Foundry just built, so the
        // client's `postLoad` listener has actually run and resolved its live currency —
        // exactly as it would for a request that loads an existing invoice to accept it.
        $entityManager->clear();

        $repository = $container->get(InvoiceRepository::class);
        $invoice = $repository->find($invoiceId);
        self::assertInstanceOf(Invoice::class, $invoice);

        $workflow = $container->get('state_machine.invoice');
        self::assertInstanceOf(WorkflowInterface::class, $workflow);
        $workflow->apply($invoice, Graph::TRANSITION_ACCEPT);
        $entityManager->flush();

        self::assertSame('USD', $invoice->getCurrency()->getCode());

        $systemConfig = $container->get(SystemConfig::class);
        $systemConfig->set(SystemConfig::CURRENCY_CONFIG_PATH, 'EUR');

        $entityManager->clear();

        $repository = $container->get(InvoiceRepository::class);
        $reloaded = $repository->find($invoice->getId());
        self::assertInstanceOf(Invoice::class, $reloaded);

        // The client itself now reports the new default ...
        self::assertSame('EUR', $reloaded->getClient()?->getCurrency()->getCode());
        // ... but the invoice it was issued to still states the currency it was issued in.
        self::assertSame('USD', $reloaded->getCurrency()->getCode());
        self::assertSame('USD', $reloaded->getCurrencyCode());
    }

    /**
     * The `ClientType` SUBMIT listener stamps the company default onto every client while
     * `Feature::MultiCurrency` is off (see `ClientType::buildForm()`). This proves that
     * overwrite never reaches an already-accepted invoice.
     */
    public function testAcceptedInvoiceCurrencyIsUnchangedAfterClientIsResaved(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD', 'company' => $this->company]);
        $invoice = InvoiceFactory::createOne([
            'status' => InvoiceStatus::Draft,
            'client' => $client,
            'company' => $this->company,
        ]);

        $container = self::getContainer();
        $workflow = $container->get('state_machine.invoice');
        self::assertInstanceOf(WorkflowInterface::class, $workflow);
        $workflow->apply($invoice, Graph::TRANSITION_ACCEPT);

        $entityManager = $container->get('doctrine')->getManager();
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $entityManager->flush();

        self::assertSame('USD', $invoice->getCurrencyCode());

        // Simulates ClientType's FormEvents::SUBMIT listener when multi-currency is off.
        $client->setCurrencyCode('EUR');
        $entityManager->flush();

        $entityManager->clear();

        $repository = $container->get(InvoiceRepository::class);
        $reloaded = $repository->find($invoice->getId());
        self::assertInstanceOf(Invoice::class, $reloaded);

        self::assertSame('USD', $reloaded->getCurrency()->getCode());
    }

    /**
     * A row written before this column existed has `currency_code IS NULL`. It must keep
     * rendering via the client fallback, exactly as it did on 3.0.x/3.1.x.
     */
    public function testPreExistingRowWithoutAFrozenCurrencyFallsBackToTheClient(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'GBP', 'company' => $this->company]);
        $invoice = InvoiceFactory::createOne([
            'status' => InvoiceStatus::Pending,
            'client' => $client,
            'company' => $this->company,
        ]);

        self::assertNull($invoice->getCurrencyCode());

        $container = self::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $entityManager->clear();

        $repository = $container->get(InvoiceRepository::class);
        $reloaded = $repository->find($invoice->getId());
        self::assertInstanceOf(Invoice::class, $reloaded);

        self::assertNull($reloaded->getCurrencyCode());
        self::assertSame('GBP', $reloaded->getCurrency()->getCode());
    }

    public function testGuardRejectsAWriteToCurrencyCodeOnAnAcceptedInvoice(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => null, 'company' => $this->company]);
        $invoiceId = InvoiceFactory::createOne([
            'status' => InvoiceStatus::Draft,
            'client' => $client,
            'company' => $this->company,
        ])->getId();

        $container = self::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $entityManager->clear();

        $repository = $container->get(InvoiceRepository::class);
        $invoice = $repository->find($invoiceId);
        self::assertInstanceOf(Invoice::class, $invoice);

        $workflow = $container->get('state_machine.invoice');
        self::assertInstanceOf(WorkflowInterface::class, $workflow);
        $workflow->apply($invoice, Graph::TRANSITION_ACCEPT);
        $entityManager->flush();

        self::assertNotNull($invoice->getCurrencyCode());

        $invoice->setCurrencyCode('EUR');

        $this->expectException(LogicException::class);

        $entityManager->flush();
    }
}
