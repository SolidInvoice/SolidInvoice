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

namespace SolidInvoice\InstallBundle\Test;

use DateTimeInterface;
use Doctrine\ORM\Tools\SchemaTool;
use SolidInvoice\CoreBundle\Entity\Version;
use SolidInvoice\CoreBundle\Repository\VersionRepository;
use SolidInvoice\CoreBundle\SolidInvoiceCoreBundle;
use SolidInvoice\InstallBundle\Installer\Database\Migration;
use function date;

trait EnsureApplicationInstalled
{
    /**
     * @before
     */
    public function installApplication(): void
    {
        $kernel = self::bootKernel();

        $entityManager = $kernel->getContainer()->get('doctrine.orm.entity_manager');
        $metaData = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->dropDatabase();

        $kernel->getContainer()->get(Migration::class)->migrate();

        /** @var VersionRepository $version */
        $version = $entityManager->getRepository(Version::class);
        $version->updateVersion(SolidInvoiceCoreBundle::VERSION);

        $_SERVER['locale'] = $_ENV['locale'] = 'en_US';
        $_SERVER['installed'] = $_ENV['installed'] = date(DateTimeInterface::ATOM);
    }
}
