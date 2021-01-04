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

namespace SolidInvoice\InvoiceBundle\Cron;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Cron\CronExpression;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use SolidInvoice\CronBundle\CommandInterface;
use SolidInvoice\InvoiceBundle\Entity\BaseInvoice;
use SolidInvoice\InvoiceBundle\Entity\Item;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Exception\InvalidTransitionException;
use SolidInvoice\InvoiceBundle\Manager\InvoiceManager;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\InvoiceBundle\Repository\RecurringInvoiceRepository;
use Symfony\Component\Workflow\StateMachine;

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
     * @var InvoiceManager
     */
    private $invoiceManager;

    public function __construct(ManagerRegistry $registry, InvoiceManager $invoiceManager, StateMachine $stateMachine)
    {
        $this->entityManager = $registry->getManager();
        $this->stateMachine = $stateMachine;
        $this->invoiceManager = $invoiceManager;
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
     *
     * @throws InvalidTransitionException
     */
    public function process(): void
    {
        /** @var RecurringInvoiceRepository $invoiceRepository */
        $invoiceRepository = $this->entityManager->getRepository(RecurringInvoice::class);

        /** @var RecurringInvoice[] $invoices */
        $invoices = $invoiceRepository->findBy(['status' => 'active']);
        $now = Carbon::now();

        foreach ($invoices as $invoice) {
            if (null !== ($invoice->getDateEnd()) && Carbon::instance($invoice->getDateEnd())->isFuture()) {
                continue;
            }

            $cron = CronExpression::factory($invoice->getFrequency());

            if ($cron->isDue($now)) {
                $newInvoice = $this->invoiceManager->createFromRecurring($invoice);
                $this->setItemsDescription($newInvoice);
                $this->invoiceManager->create($newInvoice);

                $this->stateMachine->apply($newInvoice, Graph::TRANSITION_ACCEPT);
            }
        }

        $this->entityManager->flush();
    }

    private function setItemsDescription(BaseInvoice $invoice): void
    {
        $now = CarbonImmutable::now();

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
    }
}
