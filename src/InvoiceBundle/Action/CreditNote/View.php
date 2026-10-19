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

namespace SolidInvoice\InvoiceBundle\Action\CreditNote;

use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use Symfony\Bridge\Twig\Attribute\Template;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Action\CreditNote\ViewTest
 */
final readonly class View
{
    public function __construct(
        private ClientRepository $clientRepository,
    ) {
    }

    /**
     * @return array{creditNote: CreditNote}
     */
    #[Template('@SolidInvoiceInvoice/CreditNote/view.html.twig')]
    public function __invoke(CreditNote $creditNote): array
    {
        $this->clientRepository->initializeArchived($creditNote->getClient());

        return [
            'creditNote' => $creditNote,
        ];
    }
}
