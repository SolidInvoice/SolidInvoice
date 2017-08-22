<?php

declare(strict_types = 1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
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
use Symfony\Component\Workflow\StateMachine;

final class Archive implements AjaxResponse
{
    use JsonTrait;

    /**
     * @var InvoiceRepository
     */
    private $repository;

    /**
     * @var StateMachine
     */
    private $stateMachine;

    public function __construct(InvoiceRepository $repository, StateMachine $stateMachine)
    {
        $this->repository = $repository;
        $this->stateMachine = $stateMachine;
    }

    public function __invoke(Request $request)
    {
        /* @var Invoice[] $invoices */
        $invoices = $this->repository->findBy(['id' => $request->request->get('data')]);

        foreach ($invoices as $invoice) {
            if (!$this->stateMachine->can($invoice, Graph::TRANSITION_ARCHIVE)) {
                throw new InvalidTransitionException('archive');
            }

            $this->stateMachine->apply($invoice, Graph::TRANSITION_ARCHIVE);
        }

        return $this->json([]);
    }
}
