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

use BackedEnum;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\McpBundle\Mcp\Attribute\McpScopeRequired;
use SolidInvoice\McpBundle\Mcp\McpScopeGuard;
use SolidInvoice\McpBundle\Mcp\Tool\EntityNormalizer;
use SolidInvoice\McpBundle\Mcp\Tool\UlidParser;
use SolidInvoice\McpBundle\Security\McpScope;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use Symfony\Bridge\Doctrine\Types\UlidType;

final readonly class ClientReadTools
{
    public function __construct(
        private ClientRepository $clientRepository,
        private InvoiceRepository $invoiceRepository,
        private PaymentRepository $paymentRepository,
        private EntityNormalizer $normalizer,
        private McpScopeGuard $scopeGuard,
    ) {
    }

    /**
     * Summary of a client's billing activity: totals invoiced, outstanding, paid, plus counts.
     *
     * @param string $client_id Client ULID
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'get_client_summary', description: 'Summary of a client: totals invoiced, outstanding balance, total paid, and counts.')]
    #[McpScopeRequired(McpScope::Read)]
    public function getClientSummary(string $client_id): array
    {
        $this->scopeGuard->require(McpScope::Read);

        $client = $this->clientRepository->find(UlidParser::parse($client_id, 'client_id'));

        if (! $client instanceof Client) {
            throw new ToolCallException(sprintf('Client %s not found.', $client_id));
        }

        $statusRows = $this->invoiceRepository->createQueryBuilder('i')
            ->select('i.status AS status, COUNT(i) AS cnt')
            ->andWhere('i.client = :client')
            ->setParameter('client', $client->getId(), UlidType::NAME)
            ->groupBy('i.status')
            ->getQuery()
            ->getResult();

        $countByStatus = [];

        foreach ($statusRows as $row) {
            $status = $row['status'];
            $key = $status instanceof BackedEnum ? $status->value : (string) $status;
            $countByStatus[$key] = (int) $row['cnt'];
        }

        $totalInvoiced = (string) ($this->invoiceRepository->createQueryBuilder('i')
            ->select('COALESCE(SUM(i.total), 0)')
            ->andWhere('i.client = :client')
            ->setParameter('client', $client->getId(), UlidType::NAME)
            ->getQuery()
            ->getSingleScalarResult() ?? 0);

        $outstanding = (string) ($this->invoiceRepository->createQueryBuilder('i')
            ->select('COALESCE(SUM(i.balance), 0)')
            ->andWhere('i.client = :client AND i.status IN (:statuses)')
            ->setParameter('client', $client->getId(), UlidType::NAME)
            ->setParameter('statuses', [InvoiceStatus::Pending, InvoiceStatus::Overdue])
            ->getQuery()
            ->getSingleScalarResult() ?? 0);

        $totalPaid = (string) ($this->paymentRepository->createQueryBuilder('p')
            ->select('COALESCE(SUM(p.totalAmount), 0)')
            ->andWhere('p.client = :client AND p.status = :status')
            ->setParameter('client', $client->getId(), UlidType::NAME)
            ->setParameter('status', PaymentStatus::Captured->value)
            ->getQuery()
            ->getSingleScalarResult() ?? 0);

        $clientStatus = $client->getStatus();

        return [
            'client' => [
                'id' => $client->getId()?->toRfc4122(),
                'name' => $client->getName(),
                'status' => $clientStatus instanceof BackedEnum ? $clientStatus->value : $clientStatus,
                'currency' => $client->getCurrencyCode(),
            ],
            'counts_by_status' => $countByStatus,
            'total_invoiced_amount' => $totalInvoiced,
            'outstanding_amount' => $outstanding,
            'total_paid_amount' => $totalPaid,
            'currency' => $client->getCurrencyCode(),
        ];
    }

    /**
     * List the tax identifiers configured for a client.
     *
     * @param string $client_id Client ULID
     *
     * @return list<array<string, mixed>>
     */
    #[McpTool(name: 'list_client_tax_identifiers', description: 'List the tax identifiers (e.g. VAT, GSTIN, TIN) configured for a client.')]
    #[McpScopeRequired(McpScope::Read)]
    public function listTaxIdentifiers(string $client_id): array
    {
        $this->scopeGuard->require(McpScope::Read);

        $client = $this->clientRepository->find(UlidParser::parse($client_id, 'client_id'));

        if (! $client instanceof Client) {
            throw new ToolCallException(sprintf('Client %s not found.', $client_id));
        }

        return array_values(array_map(
            $this->normalizer->normalize(...),
            $client->getTaxIdentifiers()->toArray(),
        ));
    }
}
