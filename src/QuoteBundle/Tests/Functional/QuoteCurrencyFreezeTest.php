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

namespace SolidInvoice\QuoteBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\QuoteBundle\Listener\FreezeQuoteCurrencyListener;
use SolidInvoice\QuoteBundle\Model\Graph;
use SolidInvoice\QuoteBundle\Repository\QuoteRepository;
use SolidInvoice\QuoteBundle\Test\Factory\QuoteFactory;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Quote equivalents of {@see \SolidInvoice\InvoiceBundle\Tests\Functional\InvoiceCurrencyFreezeTest}.
 */
#[CoversClass(FreezeQuoteCurrencyListener::class)]
final class QuoteCurrencyFreezeTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    /**
     * Fails before the fix: without freezing, `getCurrency()` falls through to the
     * client's live currency, which now reflects the changed system default.
     */
    public function testPublishedQuoteKeepsItsCurrencyAfterTheDefaultCurrencyChanges(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => null, 'company' => $this->company]);
        $quoteId = QuoteFactory::createOne([
            'status' => QuoteStatus::Draft,
            'client' => $client,
            'company' => $this->company,
        ])->getId();

        $container = self::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $entityManager->clear();

        $repository = $container->get(QuoteRepository::class);
        $quote = $repository->find($quoteId);
        self::assertInstanceOf(Quote::class, $quote);

        $workflow = $container->get('state_machine.quote');
        self::assertInstanceOf(WorkflowInterface::class, $workflow);
        $workflow->apply($quote, Graph::TRANSITION_PUBLISH);
        $entityManager->flush();

        self::assertSame('USD', $quote->getCurrency()->getCode());

        $systemConfig = $container->get(SystemConfig::class);
        $systemConfig->set(SystemConfig::CURRENCY_CONFIG_PATH, 'EUR');

        $entityManager->clear();

        $reloaded = $repository->find($quoteId);
        self::assertInstanceOf(Quote::class, $reloaded);

        self::assertSame('EUR', $reloaded->getClient()?->getCurrency()->getCode());
        self::assertSame('USD', $reloaded->getCurrency()->getCode());
        self::assertSame('USD', $reloaded->getCurrencyCode());
    }

    /**
     * The `ClientType` SUBMIT listener stamps the company default onto every client while
     * `Feature::MultiCurrency` is off. This proves that overwrite never reaches an
     * already-published quote.
     */
    public function testPublishedQuoteCurrencyIsUnchangedAfterClientIsResaved(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD', 'company' => $this->company]);
        $quote = QuoteFactory::createOne([
            'status' => QuoteStatus::Draft,
            'client' => $client,
            'company' => $this->company,
        ]);

        $container = self::getContainer();
        $workflow = $container->get('state_machine.quote');
        self::assertInstanceOf(WorkflowInterface::class, $workflow);
        $workflow->apply($quote, Graph::TRANSITION_PUBLISH);

        $entityManager = $container->get('doctrine')->getManager();
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $entityManager->flush();

        self::assertSame('USD', $quote->getCurrencyCode());

        // Simulates ClientType's FormEvents::SUBMIT listener when multi-currency is off.
        $client->setCurrencyCode('EUR');
        $entityManager->flush();

        $entityManager->clear();

        $repository = $container->get(QuoteRepository::class);
        $reloaded = $repository->find($quote->getId());
        self::assertInstanceOf(Quote::class, $reloaded);

        self::assertSame('USD', $reloaded->getCurrency()->getCode());
    }
}
