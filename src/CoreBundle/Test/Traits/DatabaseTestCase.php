<?php

namespace SolidInvoice\CoreBundle\Test\Traits;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Tools\SchemaTool;

trait DatabaseTestCase
{
    /**
     * @before
     */
    public function setUpDatabaseSchema(): void
    {
        $kernel = self::bootKernel();
        $em = $kernel->getContainer()->get('doctrine')->getManager();

        /** @var Connection $connection */
        $connection = $em->getConnection();
        $params = $connection->getParams();
        $dbName = $params['dbname'];

        unset($params['dbname']);

        DriverManager::getConnection($params)->getSchemaManager()->createDatabase($dbName);

        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema($em->getMetadataFactory()->getAllMetadata());
    }

    /**
     * @after
     */
    public function dropDatabaseSchema(): void
    {
        $kernel = self::bootKernel();
        $em = $kernel->getContainer()->get('doctrine')->getManager();
        $connection = $em->getConnection();
        $params = $connection->getParams();
        DriverManager::getConnection($params)->getSchemaManager()->dropDatabase($params['dbname']);

        $this->tearDown();
    }
}
