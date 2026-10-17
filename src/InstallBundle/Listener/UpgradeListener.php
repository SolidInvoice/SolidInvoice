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

namespace SolidInvoice\InstallBundle\Listener;

use SolidInvoice\InstallBundle\Installer\Database\Migration;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Lock\LockFactory;

/**
 * Listener class to intercept requests and upgrade the database if necessary.
 */
readonly class UpgradeListener implements EventSubscriberInterface
{
    private const string LOCK_RESOURCE = 'solidinvoice.database_migration';

    private const float LOCK_TTL = 300.0;

    /**
     * @return array<string, list<int|string>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function __construct(
        private ?string $installed,
        private Migration $migration,
        private LockFactory $lockFactory,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (null === $this->installed || '' === $this->installed) {
            return;
        }

        if (HttpKernelInterface::MAIN_REQUEST !== $event->getRequestType()) {
            return;
        }

        if (! $this->migration->isUpToDate()) {
            $this->migrateUnderLock();
        }
    }

    /**
     * The lock is backed by the database itself (see services.php), so it serialises every
     * host pointed at that database, not just concurrent requests on this one. A request that
     * cannot acquire it falls through and serves normally instead of queueing behind a
     * migration that may include a long-running backfill.
     */
    private function migrateUnderLock(): void
    {
        $lock = $this->lockFactory->createLock(self::LOCK_RESOURCE, self::LOCK_TTL);

        if (! $lock->acquire(false)) {
            return;
        }

        try {
            foreach ($this->migration->migrate() as $_) {
                $lock->refresh(self::LOCK_TTL);
            }
        } finally {
            $lock->release();
        }
    }
}
