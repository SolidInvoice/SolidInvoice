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

/**
 * Listener class to intercept requests and upgrade the database if necessary.
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
        private Migration $migration
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
            $this->migration->migrate();
        }
    }
}
