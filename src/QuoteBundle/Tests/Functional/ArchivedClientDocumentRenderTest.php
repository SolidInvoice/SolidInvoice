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

use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Action\ViewBilling;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Contracts\EmailVerificationGateInterface;
use SolidInvoice\CoreBundle\Pdf\Generator;
use SolidInvoice\CoreBundle\Response\PdfResponse;
use SolidInvoice\CoreBundle\Templates\BillingTemplateResolver;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\DataGridBundle\GridBuilder\Query;
use SolidInvoice\DataGridBundle\GridInterface;
use SolidInvoice\DataGridBundle\Source\ORMSource;
use SolidInvoice\QuoteBundle\Action\View;
use SolidInvoice\QuoteBundle\DataGrid\QuoteGrid;
use SolidInvoice\QuoteBundle\Email\QuoteEmail;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\QuoteBundle\Listener\Mailer\QuotePdfListener;
use SolidInvoice\QuoteBundle\Test\Factory\QuoteFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Uid\Ulid;
use Twig\Environment;

/**
 * Quote twin of InvoiceBundle's ArchivedClientDocumentRenderTest: see SOL-299.
 */
final class ArchivedClientDocumentRenderTest extends KernelTestCase
{
    use DoctrineTestTrait;

    public function testQuoteViewRendersAfterClientIsArchived(): void
    {
        $quote = $this->reloadQuote($this->createQuoteWithArchivedClient());
        $request = Request::create('/quote/' . $quote->getId());
        self::getContainer()->get('request_stack')->push($request);

        $result = self::getContainer()->get(View::class)($request, $quote);

        self::assertIsArray($result);

        // The array return is rendered by Symfony's #[Template] listener after the
        // action returns, so only actually rendering the block reaches the lazy
        // client access that used to throw.
        $html = self::getContainer()->get('twig')
            ->resolveTemplate('@SolidInvoiceQuote/Default/view.html.twig')
            ->renderBlock('content', $result);

        self::assertStringContainsString($quote->getQuoteId(), (string) $html);
    }

    public function testQuotePdfRendersAfterClientIsArchived(): void
    {
        $quote = $this->reloadQuote($this->createQuoteWithArchivedClient());

        $request = Request::create('/quote/' . $quote->getId() . '.pdf');
        $request->setRequestFormat('pdf');

        $result = self::getContainer()->get(View::class)($request, $quote);

        self::assertInstanceOf(PdfResponse::class, $result);
    }

    public function testQuoteEmailAttachmentRendersAfterClientIsArchived(): void
    {
        $quote = $this->reloadQuote($this->createQuoteWithArchivedClient());

        $listener = self::getContainer()->get(QuotePdfListener::class);
        $message = new QuoteEmail($quote);

        $listener(new MessageEvent($message, Envelope::create($message), 'smtp'));

        self::assertCount(1, $message->getAttachments());
    }

    public function testViewBillingRendersAfterClientIsArchived(): void
    {
        $quote = $this->reloadQuote($this->createQuoteWithArchivedClient());

        $result = $this->buildViewBilling()->quoteAction(Request::create('/view/quote/' . $quote->getUuid()->toString()), $quote->getUuid()->toString());

        self::assertIsArray($result);
        self::assertSame($quote->getId()->toString(), $result['quote']->getId()->toString());
    }

    public function testQuoteGridStillListsQuoteWithArchivedClient(): void
    {
        $quoteId = $this->createQuoteWithArchivedClient();

        $results = $this->runGridQuery(self::getContainer()->get(QuoteGrid::class), Quote::class);

        self::assertCount(1, $results);
        self::assertSame($quoteId->toString(), $results[0]->getId()->toString());
    }

    public function testQuoteViewIsUnaffectedWhenNothingIsArchived(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);

        $quote = QuoteFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => QuoteStatus::Draft,
        ]);

        $quoteId = $quote->getId();
        $this->em->clear();

        $quote = $this->reloadQuote($quoteId);
        $request = Request::create('/quote/' . $quoteId);
        self::getContainer()->get('request_stack')->push($request);

        $result = self::getContainer()->get(View::class)($request, $quote);

        self::assertIsArray($result);

        $html = self::getContainer()->get('twig')
            ->resolveTemplate('@SolidInvoiceQuote/Default/view.html.twig')
            ->renderBlock('content', $result);

        self::assertStringContainsString($quote->getQuoteId(), (string) $html);

        $results = $this->runGridQuery(self::getContainer()->get(QuoteGrid::class), Quote::class);

        self::assertCount(1, $results);
    }

    private function createQuoteWithArchivedClient(): Ulid
    {
        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);

        $quote = QuoteFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => QuoteStatus::Draft,
        ]);

        $quoteId = $quote->getId();

        self::getContainer()->get(ClientRepository::class)->archiveClients([$client->getId()->toBase32()]);
        $this->em->clear();

        return $quoteId;
    }

    private function reloadQuote(Ulid $quoteId): Quote
    {
        $quote = $this->em->getRepository(Quote::class)->find($quoteId);
        self::assertInstanceOf(Quote::class, $quote);

        return $quote;
    }

    /**
     * @param class-string $entityFQCN
     * @return list<object>
     */
    private function runGridQuery(GridInterface $grid, string $entityFQCN): array
    {
        $grid->initialize([]);

        $query = $grid->query(
            $this->em,
            new Query($this->em->getRepository($entityFQCN)->createQueryBuilder(ORMSource::ALIAS), ORMSource::ALIAS),
        );

        ($query->getCallback(Query::BEFORE_QUERY) ?? static fn () => null)();

        try {
            return $query->getQueryBuilder()->getQuery()->getResult();
        } finally {
            ($query->getCallback(Query::AFTER_QUERY) ?? static fn () => null)();
        }
    }

    private function buildViewBilling(): ViewBilling
    {
        $container = self::getContainer();

        $authChecker = $this->createStub(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturn(false);

        $gate = $this->createStub(EmailVerificationGateInterface::class);
        $gate->method('isCompanyGated')->willReturn(false);

        return new ViewBilling(
            $container->get('doctrine'),
            $container->get(ClientRepository::class),
            $authChecker,
            $this->createStub(RouterInterface::class),
            $container->get(CompanySelector::class),
            $container->get(Generator::class),
            $container->get(Environment::class),
            $gate,
            $container->get(BillingTemplateResolver::class),
        );
    }
}
