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

namespace SolidInvoice\InvoiceBundle\Schedule;

use Carbon\Carbon;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Message\CreateInvoiceFromRecurring;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder as ScheduleBuilderInterface;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Schedule\ScheduleBuilderTest
 */
final class ScheduleBuilder implements ScheduleBuilderInterface
{
    private ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function buildSchedule(Schedule $schedule): void
    {
        $invoiceRepository = $this->registry->getRepository(RecurringInvoice::class);

        $qb = $invoiceRepository->createQueryBuilder('r');

        $qb
            ->select('r')
            ->where('r.status = :status')
            ->andWhere('r.dateEnd IS NULL OR r.dateEnd > :now')
            ->setParameter('status', 'active')
            ->setParameter('now', Carbon::now())
        ;

        foreach ($qb->getQuery()->toIterable() as $recurringInvoice) {
            $schedule
                ->addMessage(new CreateInvoiceFromRecurring($recurringInvoice))
                ->description(sprintf('Create recurring invoice (%s)', $recurringInvoice->getId()))
                ->cron($recurringInvoice->getFrequency())
            ;
        }
    }
}
