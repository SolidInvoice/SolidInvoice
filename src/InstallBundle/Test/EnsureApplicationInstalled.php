<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InstallBundle\Test;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use DateTimeInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Tools\SchemaTool;
use SolidInvoice\CoreBundle\ConfigWriter;
use SolidInvoice\InstallBundle\Installer\Database\Migration;
use Throwable;
use function getenv;

trait EnsureApplicationInstalled
{
    /**
     * @before
     */
    public function installApplication(): void
    {
        StaticDriver::setKeepStaticConnections(false);

        $kernel = self::bootKernel();
        $em = $kernel->getContainer()->get('doctrine')->getManager();

        /** @var Connection $connection */
        $connection = $em->getConnection();
        $params = $connection->getParams();
        $dbName = $params['dbname'];

        unset($params['dbname']);

        try {
            DriverManager::getConnection($params)->getSchemaManager()->createDatabase($dbName);
        } catch (Throwable $e) {
            // Database already exists
        }

        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropDatabase();
        self::$container->get(Migration::class)->migrate();
        self::$container->get(ConfigWriter::class)->dump([
            'database_host' => getenv('database_host') ?: '127.0.0.1',
            'database_user' => 'root',
            'database_password' => null,
            'installed' => date(DateTimeInterface::ATOM),
        ]);

        StaticDriver::setKeepStaticConnections(true);
    }
}
