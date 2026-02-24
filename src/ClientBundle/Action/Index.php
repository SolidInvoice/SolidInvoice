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
use SolidInvoice\ClientBundle\Enum\ClientStatus;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\Request;

final readonly class Index
{
    public function __construct(
        private ClientRepository $clientRepository,
        private InvoiceRepository $invoiceRepository,
        private EntityManagerInterface $entityManager,
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
        $totalActiveClients = $this->clientRepository->getTotalClients(ClientStatus::Active);

        // Get archived clients count (need to temporarily disable the filter)
        $filters = $this->entityManager->getFilters();
        $filters->disable('archivable');
        $totalArchivedClients = $this->clientRepository->getTotalClients(ClientStatus::Archived);
        $filters->enable('archivable');

        // Get total contacts count
        $totalContacts = $this->entityManager->getRepository(Contact::class)->count([]);

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
