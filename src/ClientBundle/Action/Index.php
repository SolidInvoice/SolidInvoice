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

namespace SolidInvoice\ClientBundle\Action;

use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Model\Status;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\Request;

final class Index
{
    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly InvoiceRepository $invoiceRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    #[Template('@SolidInvoiceClient/Default/index.html.twig')]
    public function __invoke(Request $request): array
    {
        $isArchived = $request->query->get('archived', '0') === '1';

        // Get client counts
        $totalActiveClients = $this->clientRepository->getTotalClients(Status::STATUS_ACTIVE);

        // Get archived clients count (need to temporarily disable the filter)
        $filters = $this->entityManager->getFilters();
        $filters->disable('archivable');
        $totalArchivedClients = $this->clientRepository->getTotalClients(Status::STATUS_ARCHIVED);
        $filters->enable('archivable');

        // Get total contacts count
        $totalContacts = (int) $this->entityManager
            ->getRepository(Contact::class)
            ->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Get outstanding amounts by currency
        $totalOutstanding = $this->invoiceRepository->getTotalOutstandingByCurrency();

        return [
            'isArchived' => $isArchived,
            'totalActiveClients' => $totalActiveClients,
            'totalArchivedClients' => $totalArchivedClients,
            'totalContacts' => $totalContacts,
            'totalOutstanding' => $totalOutstanding,
        ];
    }
}
