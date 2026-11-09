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

namespace SolidInvoice\ClientBundle\Mcp;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\ClientBundle\Repository\ContactRepository;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\McpBundle\Mcp\Attribute\McpScopeRequired;
use SolidInvoice\McpBundle\Mcp\McpScopeGuard;
use SolidInvoice\McpBundle\Mcp\Output\ClientSummaryStatsOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\ClientOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\ContactOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\TaxIdentifierOutput;
use SolidInvoice\McpBundle\Mcp\Output\ListOutput;
use SolidInvoice\McpBundle\Mcp\Output\McpOutput;
use SolidInvoice\McpBundle\Mcp\Output\StatusCountOutput;
use SolidInvoice\McpBundle\Mcp\Tool\CurrencyTotals;
use SolidInvoice\McpBundle\Mcp\Tool\EntityNormalizer;
use SolidInvoice\McpBundle\Mcp\Tool\Paginator;
use SolidInvoice\McpBundle\Mcp\Tool\UlidParser;
use SolidInvoice\McpBundle\Security\McpScope;
use Symfony\Bridge\Doctrine\Types\UlidType;

final readonly class ClientReadTools
{
    public function __construct(
        private ClientRepository $clientRepository,
        private ContactRepository $contactRepository,
        private InvoiceRepository $invoiceRepository,
        private EntityNormalizer $normalizer,
        private CurrencyTotals $currencyTotals,
        private Paginator $paginator,
        private McpScopeGuard $scopeGuard,
    ) {
    }

    /**
     * Fetch a single client by its ID.
     *
     * @param string $id Client ULID
     */
    #[McpTool(name: 'get_client', title: 'Get client', description: 'Fetch a single client by ID.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    #[McpScopeRequired(McpScope::Read)]
    #[McpOutput(ClientOutput::class)]
    public function getClient(string $id): ClientOutput
    {
        $this->scopeGuard->require(McpScope::Read);

        $client = $this->clientRepository->find(UlidParser::parse($id));

        if (! $client instanceof Client) {
            throw new ToolCallException(sprintf('Client %s not found.', $id));
        }

        return $this->normalizer->client($client);
    }

    /**
     * List clients, newest first.
     *
     * @param int $page           1-indexed page number
     * @param int $items_per_page Results per page (1..100)
     */
    #[McpTool(name: 'list_clients', title: 'List clients', description: 'List clients of the current company, with pagination.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    #[McpScopeRequired(McpScope::Read)]
    #[McpOutput(ClientOutput::class, list: true)]
    public function listClients(int $page = 1, int $items_per_page = 25): ListOutput
    {
        $this->scopeGuard->require(McpScope::Read);

        return $this->paginator->paginate($this->clientRepository->createQueryBuilder('c')->orderBy('c.created', 'DESC'), $page, $items_per_page);
    }

    /**
     * Fetch a single client contact by its ID.
     *
     * @param string $id Contact ULID
     */
    #[McpTool(name: 'get_contact', title: 'Get contact', description: 'Fetch a single client contact by ID.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    #[McpScopeRequired(McpScope::Read)]
    #[McpOutput(ContactOutput::class)]
    public function getContact(string $id): ContactOutput
    {
        $this->scopeGuard->require(McpScope::Read);

        $contact = $this->contactRepository->find(UlidParser::parse($id));

        if (! $contact instanceof Contact) {
            throw new ToolCallException(sprintf('Contact %s not found.', $id));
        }

        return $this->normalizer->contact($contact);
    }

    /**
     * List client contacts, newest first, optionally for one client.
     *
     * @param string|null $client_id      Client ULID (optional)
     * @param int         $page           1-indexed page number
     * @param int         $items_per_page Results per page (1..100)
     */
    #[McpTool(name: 'list_contacts', title: 'List contacts', description: 'List client contacts with pagination, optionally for a specific client.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    #[McpScopeRequired(McpScope::Read)]
    #[McpOutput(ContactOutput::class, list: true)]
    public function listContacts(?string $client_id = null, int $page = 1, int $items_per_page = 25): ListOutput
    {
        $this->scopeGuard->require(McpScope::Read);

        $qb = $this->contactRepository->createQueryBuilder('c')->orderBy('c.created', 'DESC');

        if ($client_id !== null) {
            $client = $this->clientRepository->find(UlidParser::parse($client_id, 'client_id'));

            if (! $client instanceof Client) {
                throw new ToolCallException(sprintf('Client %s not found.', $client_id));
            }

            $qb->andWhere('c.client = :client')->setParameter('client', $client->getId(), UlidType::NAME);
        }

        return $this->paginator->paginate($qb, $page, $items_per_page);
    }

    /**
     * Summary of a client's billing activity: totals invoiced, outstanding, paid, plus counts.
     * Totals are per currency: an issued invoice keeps its currency when the client's changes.
     *
     * @param string $client_id Client ULID
     */
    #[McpTool(name: 'get_client_summary', title: 'Get client summary', description: 'Summary of a client: totals invoiced, outstanding balance, total paid (each per currency), and invoice counts by status.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    #[McpScopeRequired(McpScope::Read)]
    #[McpOutput(ClientSummaryStatsOutput::class)]
    public function getClientSummary(string $client_id): ClientSummaryStatsOutput
    {
        $this->scopeGuard->require(McpScope::Read);

        $client = $this->findClient($client_id);

        /** @var list<array{status: InvoiceStatus|string, cnt: int|string}> $statusRows */
        $statusRows = $this->invoiceRepository->createQueryBuilder('i')
            ->select('i.status AS status, COUNT(i) AS cnt')
            ->andWhere('i.client = :client')
            ->setParameter('client', $client->getId(), UlidType::NAME)
            ->groupBy('i.status')
            ->getQuery()
            ->getResult();

        return new ClientSummaryStatsOutput(
            client: $this->normalizer->client($client),
            counts_by_status: array_map(
                static fn (array $row): StatusCountOutput => new StatusCountOutput($row['status'] instanceof InvoiceStatus ? $row['status'] : InvoiceStatus::from($row['status']), (int) $row['cnt']),
                $statusRows,
            ),
            total_invoiced: $this->currencyTotals->invoices('total', client: $client),
            outstanding: $this->currencyTotals->invoices('balance', [InvoiceStatus::Pending, InvoiceStatus::Overdue], $client),
            total_paid: $this->currencyTotals->capturedPayments($client),
        );
    }

    /**
     * List the tax identifiers configured for a client. Not paginated: every identifier is on page 1.
     *
     * @param string $client_id Client ULID
     */
    #[McpTool(name: 'list_client_tax_identifiers', title: 'List client tax identifiers', description: 'List the tax identifiers (e.g. VAT, GSTIN, TIN) configured for a client.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    #[McpScopeRequired(McpScope::Read)]
    #[McpOutput(TaxIdentifierOutput::class, list: true)]
    public function listTaxIdentifiers(string $client_id): ListOutput
    {
        $this->scopeGuard->require(McpScope::Read);

        $identifiers = array_map(
            $this->normalizer->taxIdentifier(...),
            $this->findClient($client_id)->getTaxIdentifiers()->toArray(),
        );

        return new ListOutput($identifiers, 1, \count($identifiers), \count($identifiers));
    }

    private function findClient(string $clientId): Client
    {
        $client = $this->clientRepository->find(UlidParser::parse($clientId, 'client_id'));

        if (! $client instanceof Client) {
            throw new ToolCallException(sprintf('Client %s not found.', $clientId));
        }

        return $client;
    }
}
