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

use Pagerfanta\Pagerfanta;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Action\ViewBilling;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Contracts\EmailVerificationGateInterface;
use SolidInvoice\CoreBundle\Pdf\Generator;
use SolidInvoice\CoreBundle\Response\PdfResponse;
use SolidInvoice\CoreBundle\Templates\BillingTemplateResolver;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\DataGridBundle\Export\GridQueryService;
use SolidInvoice\DataGridBundle\GridBuilder\Query;
use SolidInvoice\DataGridBundle\GridInterface;
use SolidInvoice\DataGridBundle\Paginator\Adapter\QueryAdapter;
use SolidInvoice\DataGridBundle\Render\GridFieldRenderer;
use SolidInvoice\DataGridBundle\Source\SourceInterface;
use SolidInvoice\InvoiceBundle\Action\View;
use SolidInvoice\InvoiceBundle\DataGrid\ArchivedInvoiceGrid;
use SolidInvoice\InvoiceBundle\DataGrid\ArchivedRecurringInvoiceGrid;
use SolidInvoice\InvoiceBundle\DataGrid\InvoiceGrid;
use SolidInvoice\InvoiceBundle\DataGrid\RecurringInvoiceGrid;
use SolidInvoice\InvoiceBundle\Email\InvoiceEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;
use SolidInvoice\InvoiceBundle\Listener\Mailer\InvoicePdfListener;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\InvoiceBundle\Repository\RecurringInvoiceRepository;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\InvoiceBundle\Test\Factory\RecurringInvoiceFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Uid\Ulid;
use Twig\Environment;

/**
 * Archiving a client must not hard-break the invoices it already issued: see SOL-299.
 * Every case reloads through a fresh entity manager, because the bug this guards against
 * only reproduces once the client is a lazy, filtered proxy rather than the in-memory
 * object the archive call already had a reference to.
 */
final class ArchivedClientDocumentRenderTest extends KernelTestCase
{
    use DoctrineTestTrait;

    public function testInvoiceViewRendersAfterClientIsArchived(): void
    {
        $invoice = $this->reloadInvoice($this->createInvoiceWithArchivedClient());
        $request = Request::create('/invoice/' . $invoice->getId());
        self::getContainer()->get('request_stack')->push($request);

        $result = self::getContainer()->get(View::class)($request, $invoice);

        self::assertIsArray($result);

        // The array return is rendered by Symfony's #[Template] listener after the
        // action returns, so only actually rendering the block reaches the lazy
        // client access that used to throw.
        $html = self::getContainer()->get('twig')
            ->resolveTemplate('@SolidInvoiceInvoice/Default/view.html.twig')
            ->renderBlock('content', $result);

        self::assertStringContainsString($invoice->getInvoiceId(), (string) $html);
    }

    public function testInvoicePdfRendersAfterClientIsArchived(): void
    {
        $invoice = $this->reloadInvoice($this->createInvoiceWithArchivedClient());

        $request = Request::create('/invoice/' . $invoice->getId() . '.pdf');
        $request->setRequestFormat('pdf');

        $result = self::getContainer()->get(View::class)($request, $invoice);

        self::assertInstanceOf(PdfResponse::class, $result);
    }

    public function testInvoiceEmailAttachmentRendersAfterClientIsArchived(): void
    {
        $invoice = $this->reloadInvoice($this->createInvoiceWithArchivedClient());

        $listener = self::getContainer()->get(InvoicePdfListener::class);
        $message = new InvoiceEmail($invoice);

        $listener(new MessageEvent($message, Envelope::create($message), 'smtp'));

        self::assertCount(1, $message->getAttachments());
    }

    public function testViewBillingRendersAfterClientIsArchived(): void
    {
        $invoice = $this->reloadInvoice($this->createInvoiceWithArchivedClient());

        $result = $this->buildViewBilling()->invoiceAction(Request::create('/view/invoice/' . $invoice->getUuid()->toString()), $invoice->getUuid()->toString());

        self::assertIsArray($result);
        self::assertSame($invoice->getId()->toString(), $result['invoice']->getId()->toString());
    }

    public function testInvoiceGridStillListsInvoiceWithArchivedClient(): void
    {
        $invoiceId = $this->createInvoiceWithArchivedClient();

        $page = $this->fetchGridPage(self::getContainer()->get(InvoiceGrid::class));
        $results = iterator_to_array($page);

        self::assertSame(1, $page->getNbResults());
        self::assertCount(1, $results);
        self::assertSame($invoiceId->toString(), $results[0]->getId()->toString());
    }

    public function testRecurringInvoiceGridDoesNotThrowWithArchivedClient(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);

        RecurringInvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => RecurringInvoiceStatus::Active,
        ]);

        $this->archiveClient($client->getId());

        $grid = self::getContainer()->get(RecurringInvoiceGrid::class);
        $page = $this->fetchGridPage($grid);
        $results = iterator_to_array($page);

        self::assertSame(1, $page->getNbResults());
        self::assertCount(1, $results);

        $this->assertColumnsRenderWithoutThrowing($grid, $results[0]);
    }

    public function testArchivedInvoiceGridDoesNotThrowWithArchivedClient(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
        ]);

        self::getContainer()->get(InvoiceRepository::class)->archiveInvoices([$invoice->getId()->toBase32()]);
        $this->archiveClient($client->getId());

        $grid = self::getContainer()->get(ArchivedInvoiceGrid::class);
        $page = $this->fetchGridPage($grid);
        $results = iterator_to_array($page);

        // Reachable in two clicks: archive an invoice, later archive its client,
        // open Invoices -> Archived. The query succeeding is not the bug -- only
        // rendering the row was throwing, which is why this asserts the renderer.
        self::assertSame(1, $page->getNbResults());
        self::assertCount(1, $results);

        $this->assertColumnsRenderWithoutThrowing($grid, $results[0]);
    }

    public function testArchivedRecurringInvoiceGridDoesNotThrowWithArchivedClient(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);

        $recurringInvoice = RecurringInvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => RecurringInvoiceStatus::Active,
        ]);

        self::getContainer()->get(RecurringInvoiceRepository::class)->archiveInvoices([$recurringInvoice->getId()->toBase32()]);
        $this->archiveClient($client->getId());

        $grid = self::getContainer()->get(ArchivedRecurringInvoiceGrid::class);
        $page = $this->fetchGridPage($grid);
        $results = iterator_to_array($page);

        self::assertSame(1, $page->getNbResults());
        self::assertCount(1, $results);

        $this->assertColumnsRenderWithoutThrowing($grid, $results[0]);
    }

    /**
     * Rendering every column is the actual regression check: the query succeeding
     * was never the bug here, only rendering was. Rendering the client column
     * used to throw EntityNotFoundException, and rendering a money column used to
     * throw InvalidArgumentException once the client resolved to null (see
     * BaseRecurringInvoiceGrid's currency guard). No throw is the assertion.
     */
    private function assertColumnsRenderWithoutThrowing(GridInterface $grid, object $row): void
    {
        $renderer = self::getContainer()->get(GridFieldRenderer::class);

        foreach ($grid->columns() as $column) {
            $rendered = $renderer->render($column, $row);

            self::assertIsString($rendered);
        }
    }

    public function testDirectClientLoadStillHonoursArchivableFilter(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);
        $this->archiveClient($client->getId());

        $this->em->clear();

        $reloaded = $this->em->getRepository(Client::class)->find($client->getId());

        self::assertNull($reloaded, 'An archived client must still be hidden from a direct load');
    }

    public function testInvoiceViewIsUnaffectedWhenNothingIsArchived(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
        ]);

        $invoiceId = $invoice->getId();
        $this->em->clear();
        self::assertInstanceOf(Ulid::class, $invoiceId);

        $invoice = $this->reloadInvoice($invoiceId);
        $request = Request::create('/invoice/' . $invoiceId);
        self::getContainer()->get('request_stack')->push($request);

        $result = self::getContainer()->get(View::class)($request, $invoice);

        self::assertIsArray($result);

        $html = self::getContainer()->get('twig')
            ->resolveTemplate('@SolidInvoiceInvoice/Default/view.html.twig')
            ->renderBlock('content', $result);

        self::assertStringContainsString($invoice->getInvoiceId(), (string) $html);

        $page = $this->fetchGridPage(self::getContainer()->get(InvoiceGrid::class));

        self::assertSame(1, $page->getNbResults());
        self::assertCount(1, iterator_to_array($page));
    }

    private function createInvoiceWithArchivedClient(): Ulid
    {
        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
        ]);

        $invoiceId = $invoice->getId();

        $this->archiveClient($client->getId());

        return $invoiceId;
    }

    private function archiveClient(Ulid $clientId): void
    {
        self::getContainer()->get(ClientRepository::class)->archiveClients([$clientId->toBase32()]);
        $this->em->clear();
    }

    private function reloadInvoice(Ulid $invoiceId): Invoice
    {
        $invoice = $this->em->getRepository(Invoice::class)->find($invoiceId);
        self::assertInstanceOf(Invoice::class, $invoice);

        return $invoice;
    }

    /**
     * Drives a grid through the real pipeline a live page uses: ORMSource::fetch()
     * builds the query and calls the grid's own query(), GridQueryService applies
     * sort/search/filter state, then QueryAdapter (used by the DataGrid Twig
     * component) wraps execution in the grid's before/after-query callbacks. A
     * hand-rolled query bypasses GridQueryService and Pagerfanta's count path,
     * which is exactly where a join-condition bug like this one bites.
     *
     * @return Pagerfanta<object>
     */
    private function fetchGridPage(GridInterface $grid): Pagerfanta
    {
        $grid->initialize([]);

        $query = self::getContainer()->get(SourceInterface::class)->fetch($grid);
        $builder = $query->getQueryBuilder();

        self::getContainer()->get(GridQueryService::class)->applyFilters($grid, $builder, '', '', []);

        return Pagerfanta::createForCurrentPageWithMaxPerPage(
            new QueryAdapter(
                $builder,
                beforeQuery: $query->getCallback(Query::BEFORE_QUERY),
                afterQuery: $query->getCallback(Query::AFTER_QUERY),
            ),
            1,
            10,
        );
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
