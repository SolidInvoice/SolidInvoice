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

namespace SolidInvoice\QuoteBundle\Action;

use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\QuoteBundle\Model\Graph;
use SolidInvoice\QuoteBundle\Repository\QuoteRepository;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\Request;

final readonly class Index
{
    public function __construct(
        private QuoteRepository $repository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    #[Template('@SolidInvoiceQuote/Default/index.html.twig')]
    public function __invoke(Request $request): array
    {
        $isArchived = $request->query->get('archived', '0') === '1';

        // Get quote counts by status
        $pendingCount = $this->repository->getTotalQuotes(Graph::STATUS_PENDING);
        $acceptedCount = $this->repository->getTotalQuotes(Graph::STATUS_ACCEPTED);
        $cancelledCount = $this->repository->getTotalQuotes(Graph::STATUS_CANCELLED);
        $draftCount = $this->repository->getTotalQuotes(Graph::STATUS_DRAFT);
        $declinedCount = $this->repository->getTotalQuotes(Graph::STATUS_DECLINED);

        // Calculate total active quotes
        $totalActiveQuotes = $pendingCount + $acceptedCount + $cancelledCount + $draftCount + $declinedCount;

        // Get archived quotes count (need to temporarily disable the filter)
        $filters = $this->entityManager->getFilters();
        $filters->disable('archivable');
        try {
            $totalArchivedQuotes = $this->repository->count(['archived' => true]);
        } finally {
            $filters->enable('archivable');
        }

        return [
            'isArchived' => $isArchived,
            'totalActiveQuotes' => $totalActiveQuotes,
            'totalArchivedQuotes' => $totalArchivedQuotes,
            'pendingCount' => $pendingCount,
            'acceptedCount' => $acceptedCount,
            'declinedCount' => $declinedCount,
            'draftCount' => $draftCount,
            'status_list_count' => [
                Graph::STATUS_PENDING => $pendingCount,
                Graph::STATUS_ACCEPTED => $acceptedCount,
                Graph::STATUS_CANCELLED => $cancelledCount,
                Graph::STATUS_DRAFT => $draftCount,
                Graph::STATUS_DECLINED => $declinedCount,
            ],
        ];
    }
}
