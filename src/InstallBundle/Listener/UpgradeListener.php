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

use RuntimeException;
use SolidInvoice\InstallBundle\Installer\Database\Migration;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use function dirname;
use function fclose;
use function flock;
use function fopen;
use function is_dir;
use function mkdir;
use function sprintf;
use const LOCK_EX;
use const LOCK_UN;

/**
 * Listener class to intercept requests and upgrade the database if necessary.
 *
 * @see \SolidInvoice\InstallBundle\Tests\Listener\UpgradeListenerTest
 */
readonly class UpgradeListener implements EventSubscriberInterface
{
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
        #[Autowire('%kernel.cache_dir%/db_upgrade.lock')]
        private string $lockFile,
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

        if ($this->migration->isUpToDate()) {
            return;
        }

        // Several requests can arrive at once right after an upgrade. Without a lock,
        // each of them would compute and run the same schema changes (e.g. ALTER TABLE ...
        // ADD COLUMN), and every request after the first would fail with a
        // "duplicate column" error. Only one request may run the upgrade at a time,
        // and the check is repeated once the lock is held, so requests that were
        // waiting skip the work the first request already finished.
        $handle = $this->acquireLock();

        try {
            if (! $this->migration->isUpToDate()) {
                foreach ($this->migration->migrate() as $_) {
                }
            }
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * @return resource
     */
    private function acquireLock()
    {
        $dir = dirname($this->lockFile);

        if (! is_dir($dir) && ! @mkdir($dir, 0o777, true) && ! is_dir($dir)) {
            throw new RuntimeException(sprintf('Unable to create directory "%s" for the database upgrade lock.', $dir));
        }

        $handle = @fopen($this->lockFile, 'c');

        if (false === $handle) {
            throw new RuntimeException(sprintf('Unable to open the database upgrade lock file "%s".', $this->lockFile));
        }

        if (! flock($handle, LOCK_EX)) {
            fclose($handle);

            throw new RuntimeException(sprintf('Unable to acquire the database upgrade lock "%s".', $this->lockFile));
        }

        return $handle;
    }
}
