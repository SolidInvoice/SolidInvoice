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
use SolidInvoice\DataGridBundle\GridBuilder\Query;
use SolidInvoice\DataGridBundle\GridInterface;
use SolidInvoice\DataGridBundle\Render\GridFieldRenderer;
use SolidInvoice\DataGridBundle\Source\ORMSource;
use SolidInvoice\InvoiceBundle\Action\View;
use SolidInvoice\InvoiceBundle\DataGrid\InvoiceGrid;
use SolidInvoice\InvoiceBundle\DataGrid\RecurringInvoiceGrid;
use SolidInvoice\InvoiceBundle\Email\InvoiceEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;
use SolidInvoice\InvoiceBundle\Listener\Mailer\InvoicePdfListener;
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

        $results = $this->runGridQuery(self::getContainer()->get(InvoiceGrid::class), Invoice::class);

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
        $results = $this->runGridQuery($grid, RecurringInvoice::class);

        self::assertCount(1, $results);

        $columns = [];

        foreach ($grid->columns() as $column) {
            $columns[$column->getField()] = $column;
        }

        $renderer = self::getContainer()->get(GridFieldRenderer::class);

        // Rendering the client column used to throw EntityNotFoundException, and
        // rendering a money column used to throw InvalidArgumentException once the
        // client resolved to null (see BaseRecurringInvoiceGrid's currency guard).
        self::assertNotSame('', $renderer->render($columns['client'], $results[0]));
        self::assertNotSame('', $renderer->render($columns['total'], $results[0]));
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

        $invoice = $this->reloadInvoice($invoiceId);
        $request = Request::create('/invoice/' . $invoiceId);
        self::getContainer()->get('request_stack')->push($request);

        $result = self::getContainer()->get(View::class)($request, $invoice);

        self::assertIsArray($result);

        $html = self::getContainer()->get('twig')
            ->resolveTemplate('@SolidInvoiceInvoice/Default/view.html.twig')
            ->renderBlock('content', $result);

        self::assertStringContainsString($invoice->getInvoiceId(), (string) $html);

        $results = $this->runGridQuery(self::getContainer()->get(InvoiceGrid::class), Invoice::class);

        self::assertCount(1, $results);
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
