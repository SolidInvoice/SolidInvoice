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

namespace SolidInvoice\TimeTrackingBundle\Manager;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\CoreBundle\Enum\LineItemType;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\TimeTrackingBundle\Entity\TimeEntry;
use SolidInvoice\TimeTrackingBundle\Entity\Timer;
use SolidInvoice\TimeTrackingBundle\Enum\TimeEntryStatus;
use SolidInvoice\TimeTrackingBundle\Repository\TimeEntryRepository;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Component\Workflow\WorkflowInterface;

final class TimeEntryManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TimeEntryRepository $timeEntryRepository,
        private readonly TotalCalculator $totalCalculator,
        private readonly WorkflowInterface $invoiceStateMachine,
        private readonly SystemConfig $systemConfig,
    ) {
    }

    /**
     * Resolve the effective hourly rate for a client.
     * Uses client's rate if set, otherwise falls back to global settings rate.
     */
    public function resolveRate(Client $client): BigNumber
    {
        $clientRate = $client->getHourlyRate();
        if ($clientRate !== null && ! $clientRate->isZero()) {
            return $clientRate;
        }

        $globalRate = $this->systemConfig->get('time_tracking/hourly_rate');
        if ($globalRate !== null && $globalRate !== '0') {
            return BigDecimal::of($globalRate);
        }

        return BigDecimal::zero();
    }

    /**
     * Create a TimeEntry from a completed Timer.
     */
    public function createFromTimer(Timer $timer): TimeEntry
    {
        $timeEntry = new TimeEntry();
        $timeEntry->setDuration($timer->getElapsedSeconds());
        $timeEntry->setDate(new DateTimeImmutable());
        $timeEntry->setDescription($timer->getDescription());
        $timeEntry->setTimer($timer);
        $timeEntry->setUser($timer->getUser());
        $timeEntry->setStatus(TimeEntryStatus::Pending);

        if ($timer->getClient() !== null) {
            $timeEntry->setClient($timer->getClient());
            $timeEntry->setHourlyRate($this->resolveRate($timer->getClient()));
        }

        $this->entityManager->persist($timeEntry);

        return $timeEntry;
    }

    /**
     * Create a manual TimeEntry.
     */
    public function createManualEntry(
        User $user,
        Client $client,
        int $durationSeconds,
        DateTimeImmutable $date,
        ?string $description = null,
    ): TimeEntry {
        $timeEntry = new TimeEntry();
        $timeEntry->setUser($user);
        $timeEntry->setClient($client);
        $timeEntry->setDuration($durationSeconds);
        $timeEntry->setDate($date);
        $timeEntry->setDescription($description);
        $timeEntry->setStatus(TimeEntryStatus::Pending);
        $timeEntry->setHourlyRate($this->resolveRate($client));

        $this->entityManager->persist($timeEntry);
        $this->entityManager->flush();

        return $timeEntry;
    }

    /**
     * Generate a draft invoice from selected time entries.
     * All entries must belong to the same client and be in Pending status.
     *
     * @param string[] $entryIds
     * @throws InvalidArgumentException if entries are invalid
     */
    public function generateInvoice(array $entryIds): Invoice
    {
        if ($entryIds === []) {
            throw new InvalidArgumentException('No time entries selected.');
        }

        $entries = $this->timeEntryRepository->findByIds($entryIds);

        if ($entries === []) {
            throw new InvalidArgumentException('No valid time entries found.');
        }

        // Validate all entries belong to the same client
        $client = null;
        foreach ($entries as $entry) {
            if ($entry->isLocked()) {
                throw new InvalidArgumentException(sprintf('Time entry "%s" is already invoiced.', $entry->getId()));
            }
            if ($entry->getStatus() !== TimeEntryStatus::Pending) {
                throw new InvalidArgumentException('All entries must be in pending status.');
            }
            if ($client === null) {
                $client = $entry->getClient();
            } elseif ($client !== $entry->getClient()) {
                throw new InvalidArgumentException('All selected time entries must belong to the same client.');
            }
        }

        if ($client === null) {
            throw new InvalidArgumentException('A client must be assigned to the selected time entries before generating an invoice.');
        }

        // Create the invoice
        $invoice = new Invoice();
        $invoice->setClient($client);
        $invoice->setInvoiceDate(new DateTimeImmutable());

        // Create one line per time entry
        foreach ($entries as $entry) {
            $line = new Line();
            $line->setDescription($entry->getDescription() ?? sprintf('Time entry - %s', $entry->getDate()->format('Y-m-d')));
            $line->setPrice($entry->getHourlyRate());
            $line->setQty($entry->getDuration() / 3600.0);
            $line->setLineItemType(LineItemType::TimeTracking);

            $invoice->addLine($line);
        }

        $this->totalCalculator->calculateTotals($invoice);
        $this->invoiceStateMachine->apply($invoice, Graph::TRANSITION_NEW);

        $this->entityManager->persist($invoice);

        // Mark entries as invoiced
        foreach ($entries as $entry) {
            $entry->setStatus(TimeEntryStatus::Invoiced);
            $entry->setInvoice($invoice);
        }

        $this->entityManager->flush();

        return $invoice;
    }
}
