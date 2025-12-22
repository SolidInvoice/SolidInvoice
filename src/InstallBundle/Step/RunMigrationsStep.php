<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InstallBundle\Step;

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CoreBundle\Entity\Version;
use SolidInvoice\CoreBundle\Repository\VersionRepository;
use SolidInvoice\CoreBundle\SolidInvoiceCoreBundle;
use SolidInvoice\InstallBundle\DTO\Installation;
use SolidInvoice\InstallBundle\Installer\Database\Migration;

final readonly class RunMigrationsStep implements InstallationStepInterface
{
    public function __construct(
        private Migration $migration,
        private ManagerRegistry $registry,
    ) {
    }

    public static function priority(): int
    {
        return 10;
    }

    public function execute(Installation $installationData, ?callable $callback = null): \Generator
    {
        yield from $this->migration->migrate($callback);

        $version = SolidInvoiceCoreBundle::VERSION;

        $entityManager = $this->registry->getManager();

        /** @var VersionRepository $repository */
        $repository = $entityManager->getRepository(Version::class);

        $repository->updateVersion($version);
    }

    public static function getLabel(): string
    {
        return 'Creating database schema';
    }
}
