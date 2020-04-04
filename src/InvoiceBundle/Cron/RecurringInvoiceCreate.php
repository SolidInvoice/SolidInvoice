<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Cron;

use Carbon\Carbon;
use Cron\CronExpression;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use SolidInvoice\CronBundle\CommandInterface;
use SolidInvoice\InvoiceBundle\Cloner\InvoiceCloner;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Item;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use Symfony\Component\Workflow\StateMachine;

/**
 * Class RecurringInvoiceCreate.
 */
class RecurringInvoiceCreate implements CommandInterface
{
    /**
     * @var ObjectManager
     */
    private $entityManager;

    /**
     * @var StateMachine
     */
    private $stateMachine;

    /**
     * @var InvoiceCloner
     */
    private $invoiceCloner;

    /**
     * RecurringInvoiceCreate constructor.
     */
    public function __construct(ManagerRegistry $registry, InvoiceCloner $invoiceCloner, StateMachine $stateMachine)
    {
        $this->entityManager = $registry->getManager();
        $this->stateMachine = $stateMachine;
        $this->invoiceCloner = $invoiceCloner;
    }

    /**
     * {@inheritdoc}
     */
    public function isDue(): bool
    {
        // We want to run this process always when a cron runs, to ensure we always send out the invoices
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        /** @var InvoiceRepository $invoiceRepository */
        $invoiceRepository = $this->entityManager->getRepository(Invoice::class);

        $invoices = $invoiceRepository->getRecurringInvoices();

        foreach ($invoices as $invoice) {
            $recurringInfo = $invoice->getRecurringInfo();
            if (null !== ($recurringInfo->getDateEnd()) && Carbon::instance($recurringInfo->getDateEnd())->isFuture()) {
                continue;
            }

            $cron = CronExpression::factory($recurringInfo->getFrequency());

            if (true === $cron->isDue(Carbon::now())) {
                $newInvoice = $this->invoiceCloner->clone($invoice);
                $this->setItemsDescription($newInvoice);

                $this->stateMachine->apply($newInvoice, Graph::TRANSITION_ACCEPT);
            }
        }
    }

    private function setItemsDescription(Invoice $invoice)
    {
        $now = Carbon::now();

        /** @var Item $item */
        foreach ($invoice->getItems() as $item) {
            $description = $item->getDescription();

            $description = str_replace(
                [
                    '{day}',
                    '{day_name}',
                    '{month}',
                    '{year}',
                ],
                [
                    $now->day,
                    $now->format('l'),
                    $now->format('F'),
                    $now->year,
                ],
                $description
            );

            $item->setDescription($description);
        }

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();
    }
}
