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

use Brick\Math\BigInteger;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Currency\FrozenCurrencyThaw;
use SolidInvoice\CoreBundle\Listener\Doctrine\FrozenCurrencyGuardListener;
use SolidInvoice\CoreBundle\Listener\FreezeCurrencyListener;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentFactory;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * an issued invoice must keep the currency it was issued in, even
 * after the client's currency (default or explicit) changes underneath it.
 */
#[CoversClass(FreezeCurrencyListener::class)]
#[CoversClass(FrozenCurrencyGuardListener::class)]
#[CoversClass(FrozenCurrencyThaw::class)]
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

    /**
     * `cancel` and `reopen` both keep the invoice in the mutable window: a draft that was
     * cancelled and reopened is still a draft, so it must follow its client until it is
     * actually issued. Before the fix, cancelling from Draft froze the currency.
     */
    public function testCancelledThenReopenedDraftStillFollowsItsClient(): void
    {
        $usdClient = ClientFactory::createOne(['currencyCode' => 'USD', 'company' => $this->company]);
        $eurClient = ClientFactory::createOne(['currencyCode' => 'EUR', 'company' => $this->company]);
        $invoiceId = InvoiceFactory::createOne([
            'status' => InvoiceStatus::Draft,
            'client' => $usdClient,
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

        $workflow->apply($invoice, Graph::TRANSITION_CANCEL);
        $entityManager->flush();
        self::assertNull($invoice->getCurrencyCode());

        $workflow->apply($invoice, Graph::TRANSITION_REOPEN);
        $entityManager->flush();
        self::assertNull($invoice->getCurrencyCode());

        $reloadedEurClient = $entityManager->find(Client::class, $eurClient->getId());
        self::assertInstanceOf(Client::class, $reloadedEurClient);
        $invoice->setClient($reloadedEurClient);
        $entityManager->flush();

        self::assertSame('EUR', $invoice->getCurrency()->getCode());

        $workflow->apply($invoice, Graph::TRANSITION_ACCEPT);
        $entityManager->flush();

        self::assertSame('EUR', $invoice->getCurrencyCode());
    }

    /**
     * Once frozen, the document no longer reaches through to its client. Archiving the
     * client afterwards must not change what the issued document states.
     */
    public function testFrozenInvoiceKeepsItsCurrencyAfterItsClientIsArchived(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD', 'company' => $this->company]);
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

        self::assertSame('USD', $invoice->getCurrencyCode());

        $container->get(ClientRepository::class)->archiveClients([$client->getId()->toBase32()]);
        $entityManager->clear();

        $reloaded = $repository->find($invoiceId);
        self::assertInstanceOf(Invoice::class, $reloaded);

        self::assertSame('USD', $reloaded->getCurrency()->getCode());
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

    /**
     * The sibling of {@see self::testCancelledThenReopenedDraftStillFollowsItsClient()}
     * that starts from an issued invoice. That test starts from a draft, which was never
     * frozen, so it passes vacuously; this one proves `reopen` (`[Cancelled] → [Draft]`)
     * actually thaws a frozen `currency_code`, not just one that was already null.
     */
    public function testCancelledThenReopenedIssuedInvoiceThawsAndFollowsItsClientAgain(): void
    {
        $usdClient = ClientFactory::createOne(['currencyCode' => 'USD', 'company' => $this->company]);
        $eurClient = ClientFactory::createOne(['currencyCode' => 'EUR', 'company' => $this->company]);
        $invoiceId = InvoiceFactory::createOne([
            'status' => InvoiceStatus::Draft,
            'client' => $usdClient,
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
        self::assertSame('USD', $invoice->getCurrencyCode());

        $workflow->apply($invoice, Graph::TRANSITION_CANCEL);
        $entityManager->flush();
        self::assertSame('USD', $invoice->getCurrencyCode());

        $workflow->apply($invoice, Graph::TRANSITION_REOPEN);
        $entityManager->flush();
        self::assertNull($invoice->getCurrencyCode());

        $reloadedEurClient = $entityManager->find(Client::class, $eurClient->getId());
        self::assertInstanceOf(Client::class, $reloadedEurClient);
        $invoice->setClient($reloadedEurClient);
        $entityManager->flush();

        self::assertSame('EUR', $invoice->getCurrency()->getCode());

        $workflow->apply($invoice, Graph::TRANSITION_ACCEPT);
        $entityManager->flush();

        self::assertSame('EUR', $invoice->getCurrencyCode());
    }

    /**
     * An invoice with a payment row retains its frozen currency even when it returns to
     * Draft, per {@see Invoice::retainsFrozenCurrency()}. The edit form has no currency
     * field, so the only way to reach the frozen column is a direct write, which the
     * guard still rejects.
     */
    public function testPartiallyPaidInvoiceRetainsItsFrozenCurrencyOnEdit(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD', 'company' => $this->company]);
        $invoiceId = InvoiceFactory::createOne([
            'status' => InvoiceStatus::Draft,
            'client' => $client,
            'company' => $this->company,
            'total' => BigInteger::of(10000),
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

        self::assertSame('USD', $invoice->getCurrencyCode());

        PaymentFactory::createOne([
            'invoice' => $invoice,
            'client' => $client,
            'company' => $this->company,
            'totalAmount' => 5000,
            'currencyCode' => 'USD',
        ]);
        $entityManager->flush();
        $entityManager->clear();

        $invoice = $repository->find($invoiceId);
        self::assertInstanceOf(Invoice::class, $invoice);

        $workflow->apply($invoice, 'edit');
        $entityManager->flush();

        self::assertSame('USD', $invoice->getCurrencyCode());

        $invoice->setCurrencyCode('EUR');

        $this->expectException(LogicException::class);

        $entityManager->flush();
    }

    /**
     * The same `edit`-from-Pending path as above, but with no payment rows: requirement 1
     * applies and the currency thaws, so the invoice follows its client again.
     */
    public function testEditFromPendingWithNoPaymentsThawsCurrencyCode(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD', 'company' => $this->company]);
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

        self::assertSame('USD', $invoice->getCurrencyCode());

        $workflow->apply($invoice, 'edit');
        $entityManager->flush();

        self::assertNull($invoice->getCurrencyCode());
    }
}
