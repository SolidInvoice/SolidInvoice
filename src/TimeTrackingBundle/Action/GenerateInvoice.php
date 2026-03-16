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

namespace SolidInvoice\TimeTrackingBundle\Action;

use InvalidArgumentException;
use SolidInvoice\TimeTrackingBundle\Manager\TimeEntryManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

final class GenerateInvoice extends AbstractController
{
    public function __construct(
        private readonly TimeEntryManager $timeEntryManager,
        private readonly RouterInterface $router,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if (! $this->isCsrfTokenValid('generate_invoice', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');

            return new RedirectResponse($this->router->generate('_time_tracking_index'));
        }

        /** @var string[] $entryIds */
        $entryIds = $request->request->all('entry_ids');

        if ($entryIds === []) {
            $this->addFlash('error', 'Please select at least one time entry to generate an invoice.');

            return new RedirectResponse($this->router->generate('_time_tracking_index'));
        }

        try {
            $invoice = $this->timeEntryManager->generateInvoice($entryIds);
        } catch (InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());

            return new RedirectResponse($this->router->generate('_time_tracking_index'));
        }

        $this->addFlash('success', 'Invoice generated successfully from selected time entries.');

        return new RedirectResponse($this->router->generate('_invoices_view', ['id' => $invoice->getId()]));
    }
}
