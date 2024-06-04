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

namespace SolidInvoice\InvoiceBundle\Action\Grid;

use SolidInvoice\CoreBundle\Response\AjaxResponse;
use SolidInvoice\CoreBundle\Traits\JsonTrait;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Exception\InvalidTransitionException;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\InvoiceBundle\Repository\RecurringInvoiceRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Workflow\WorkflowInterface;

final class RecurringArchive implements AjaxResponse
{
    use JsonTrait;

    public function __construct(
        private readonly RecurringInvoiceRepository $repository,
        private readonly WorkflowInterface $recurringInvoiceStateMachine
    ) {
    }

    public function __invoke(Request $request)
    {
        /** @var RecurringInvoice[] $invoices */
        $invoices = $this->repository->findBy(['id' => $request->request->get('data')]);

        foreach ($invoices as $invoice) {
            if (! $this->recurringInvoiceStateMachine->can($invoice, Graph::TRANSITION_ARCHIVE)) {
                throw new InvalidTransitionException('archive');
            }

            $this->recurringInvoiceStateMachine->apply($invoice, Graph::TRANSITION_ARCHIVE);
        }

        return $this->json([]);
    }
}
