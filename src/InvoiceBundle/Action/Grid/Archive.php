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

use CSBill\CoreBundle\Traits\JsonTrait;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\InvoiceBundle\Exception\InvalidTransitionException;
use CSBill\InvoiceBundle\Manager\InvoiceManager;
use CSBill\InvoiceBundle\Model\Graph;
use CSBill\InvoiceBundle\Repository\InvoiceRepository;
use Finite\Factory\FactoryInterface;
use Symfony\Component\HttpFoundation\Request;

class Archive
{
    use JsonTrait;

    /**
     * @var InvoiceRepository
     */
    private $repository;

    /**
     * @var InvoiceManager
     */
    private $invoiceManager;

    /**
     * @var FactoryInterface
     */
    private $factory;

    public function __construct(FactoryInterface $factory, InvoiceRepository $repository, InvoiceManager $invoiceManager)
    {
        $this->repository = $repository;
        $this->invoiceManager = $invoiceManager;
        $this->factory = $factory;
    }

    public function __invoke(Request $request)
    {
        /* @var Invoice[] $invoices */
        $invoices = $this->repository->findBy(['id' => $request->request->get('data')]);

        foreach ($invoices as $invoice) {
            if (!$this->factory->get($invoice, Graph::GRAPH)->can('archive')) {
                throw new InvalidTransitionException('archive');
            }

            $this->invoiceManager->archive($invoice);
        }

        return $this->json([]);
    }
}