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
use DateTimeInterface;
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
    public function __construct(
        private readonly ManagerRegistry $registry
    ) {
    }

    public function buildSchedule(Schedule $schedule): void
    {
        $invoiceRepository = $this->registry->getRepository(RecurringInvoice::class);

        /** @var RecurringInvoice[] $recurringInvoices */
        $recurringInvoices = $invoiceRepository->findBy(['status' => 'active']);

        foreach ($recurringInvoices as $recurringInvoice) {
            if ($recurringInvoice->getDateEnd() instanceof DateTimeInterface && ! Carbon::instance($recurringInvoice->getDateEnd())->isFuture()) {
                continue;
            }

            $schedule
                ->addMessage(new CreateInvoiceFromRecurring($recurringInvoice))
                ->description(sprintf('Create recurring invoice (%s)', $recurringInvoice->getId()))
                ->cron($recurringInvoice->getFrequency())
            ;
        }
    }
}
