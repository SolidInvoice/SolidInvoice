<?php

namespace SolidInvoice\InstallBundle\Test;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Tools\SchemaTool;
use PDOException;
use SolidInvoice\CoreBundle\ConfigWriter;
use SolidInvoice\CoreBundle\Test\Traits\DatabaseTestCase;
use SolidInvoice\InstallBundle\Installer\Database\Migration;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Throwable;
use function file_exists;
use function getenv;
use function realpath;
use function rename;

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
