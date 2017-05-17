<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Action\Grid;

use CSBill\CoreBundle\Response\AjaxResponse;
use CSBill\CoreBundle\Traits\JsonTrait;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\InvoiceBundle\Exception\InvalidTransitionException;
use CSBill\InvoiceBundle\Model\Graph;
use CSBill\InvoiceBundle\Repository\InvoiceRepository;
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
