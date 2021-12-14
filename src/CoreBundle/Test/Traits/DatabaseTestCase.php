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

        DriverManager::getConnection($params)->getSchemaManager()->dropAndCreateDatabase($dbName);

        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        if (isset($this->disableSchemaUpdate) && $this->disableSchemaUpdate) {
            return;
        }

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
