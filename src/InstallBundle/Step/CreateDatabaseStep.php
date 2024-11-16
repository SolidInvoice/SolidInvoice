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

use Doctrine\DBAL\DriverManager;
use Doctrine\Persistence\ManagerRegistry;
use function in_array;

final class CreateDatabaseStep implements InstallationStepInterface
{
    public function __construct(
        private readonly ManagerRegistry $doctrine
    ) {
    }

    public static function priority(): int
    {
        return 20;
    }

    public function execute(?callable $callback = null): void
    {
        $connection = $this->doctrine->getConnection();
        $params = $connection->getParams();
        $dbName = $params['dbname'];

        unset($params['dbname']);

        $tmpConnection = DriverManager::getConnection(
            $params,
            $connection->getConfiguration(),
        );
        $schemaManager = $tmpConnection->createSchemaManager();

        if (! in_array($dbName, $schemaManager->listDatabases(), true)) {
            $schemaManager->createDatabase($dbName);
        }
    }

    public static function getLabel(): string
    {
        return 'Creating database';
    }
}
