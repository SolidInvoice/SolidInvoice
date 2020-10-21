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

namespace SolidInvoice\InstallBundle\Listener;

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CoreBundle\Entity\Version;
use SolidInvoice\CoreBundle\Repository\VersionRepository;
use SolidInvoice\CoreBundle\SolidInvoiceCoreBundle;
use SolidInvoice\InstallBundle\Installer\Database\Migration;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Listener class to intercept requests and upgrade the database if necessary.
 */
class UpgradeListener implements EventSubscriberInterface
{
    /**
     * @var string
     */
    private $installed;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var Migration
     */
    private $migration;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    /**
     * @param string $installed
     */
    public function __construct(?string $installed, ManagerRegistry $registry, Migration $migration)
    {
        $this->installed = $installed;
        $this->registry = $registry;
        $this->migration = $migration;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (null === $this->installed) {
            return;
        }

        if (HttpKernel::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        /** @var VersionRepository $versionRepository */
        $versionRepository = $this->registry->getRepository(Version::class);

        if (version_compare($versionRepository->getCurrentVersion(), SolidInvoiceCoreBundle::VERSION, '<')) {
            $this->migration->migrate();
        }
    }
}
