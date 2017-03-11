<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InstallBundle\Listener;

use CSBill\CoreBundle\CSBillCoreBundle;
use CSBill\CoreBundle\Repository\VersionRepository;
use CSBill\InstallBundle\Installer\Database\Migration;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
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
     * @param string          $installed
     * @param ManagerRegistry $registry
     * @param Migration       $migration
     */
    public function __construct($installed, ManagerRegistry $registry, Migration $migration)
    {
        $this->installed = $installed;
        $this->registry = $registry;
        $this->migration = $migration;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$this->installed) {
            return;
        }

        if ($event->getRequestType() !== HttpKernel::MASTER_REQUEST) {
            return;
        }

        /** @var VersionRepository $versionRepository */
        $versionRepository = $this->registry->getRepository('CSBillCoreBundle:Version');

        if (version_compare($versionRepository->getCurrentVersion(), CSBillCoreBundle::VERSION, '<')) {
            $this->migration->migrate();
        }
    }
}
