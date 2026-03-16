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

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\TimeTrackingBundle\Entity\TimeEntry;
use SolidInvoice\TimeTrackingBundle\Entity\Timer;
use SolidInvoice\TimeTrackingBundle\Enum\TimerStatus;
use SolidInvoice\TimeTrackingBundle\Repository\TimerRepository;
use SolidInvoice\UserBundle\Entity\User;

final class TimerManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TimerRepository $timerRepository,
        private readonly TimeEntryManager $timeEntryManager,
    ) {
    }

    /**
     * Start a new timer for the user.
     * Throws RuntimeException if an active timer already exists for this user.
     */
    public function start(User $user, ?Client $client = null, ?string $description = null): Timer
    {
        if ($this->timerRepository->findActiveForUser($user) !== null) {
            throw new RuntimeException('A timer is already active. Please stop or pause it before starting a new one.');
        }

        $timer = new Timer();
        $timer->setUser($user);
        $timer->setClient($client);
        $timer->setDescription($description);
        $timer->setStatus(TimerStatus::Running);

        $this->entityManager->persist($timer);
        $this->entityManager->flush();

        return $timer;
    }

    /**
     * Pause an active (running) timer, accumulating elapsed seconds.
     */
    public function pause(Timer $timer): Timer
    {
        if ($timer->getStatus() !== TimerStatus::Running) {
            throw new RuntimeException('Timer is not running.');
        }

        $now = new DateTimeImmutable();
        $elapsed = $timer->getElapsedSeconds() + ($now->getTimestamp() - $timer->getLastStartedAt()->getTimestamp());

        $timer->setElapsedSeconds($elapsed);
        $timer->setStatus(TimerStatus::Paused);

        $this->entityManager->flush();

        return $timer;
    }

    /**
     * Resume a paused timer.
     */
    public function resume(Timer $timer): Timer
    {
        if ($timer->getStatus() !== TimerStatus::Paused) {
            throw new RuntimeException('Timer is not paused.');
        }

        $timer->setLastStartedAt(new DateTimeImmutable());
        $timer->setStatus(TimerStatus::Running);

        $this->entityManager->flush();

        return $timer;
    }

    /**
     * Stop a timer.
     * If the timer has a client, a TimeEntry is automatically created from it.
     * Returns the created TimeEntry, or null if no client was set.
     */
    public function stop(Timer $timer): ?TimeEntry
    {
        if ($timer->getStatus() === TimerStatus::Stopped) {
            throw new RuntimeException('Timer is already stopped.');
        }

        $now = new DateTimeImmutable();

        // Accumulate any remaining running time
        if ($timer->getStatus() === TimerStatus::Running) {
            $elapsed = $timer->getElapsedSeconds() + ($now->getTimestamp() - $timer->getLastStartedAt()->getTimestamp());
            $timer->setElapsedSeconds($elapsed);
        }

        $timer->setStatus(TimerStatus::Stopped);

        $timeEntry = null;
        if ($timer->getClient() !== null) {
            // Auto-create TimeEntry only if client is set
            $timeEntry = $this->timeEntryManager->createFromTimer($timer);
        }

        $this->entityManager->flush();

        return $timeEntry;
    }

    public function saveTimer(Timer $timer): void
    {
        $this->entityManager->flush();
    }

    /**
     * Calculate the current total elapsed seconds for a timer (including any currently running interval).
     */
    public function getCurrentElapsed(Timer $timer): int
    {
        if ($timer->getStatus() === TimerStatus::Running) {
            $now = new DateTimeImmutable();
            return $timer->getElapsedSeconds() + ($now->getTimestamp() - $timer->getLastStartedAt()->getTimestamp());
        }

        return $timer->getElapsedSeconds();
    }
}
