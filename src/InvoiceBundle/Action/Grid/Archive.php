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
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Exception\InvalidTransitionException;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Workflow\WorkflowInterface;

final class Archive implements AjaxResponse
{
    use JsonTrait;

    public function __construct(
        private readonly InvoiceRepository $repository,
        private readonly WorkflowInterface $invoiceStateMachine
    ) {
    }

    public function __invoke(Request $request)
    {
        /** @var Invoice[] $invoices */
        $invoices = $this->repository->findBy(['id' => $request->request->get('data')]);

        foreach ($invoices as $invoice) {
            if (! $this->invoiceStateMachine->can($invoice, Graph::TRANSITION_ARCHIVE)) {
                throw new InvalidTransitionException('archive');
            }

            $this->invoiceStateMachine->apply($invoice, Graph::TRANSITION_ARCHIVE);
        }

        return $this->json([]);
    }
}
