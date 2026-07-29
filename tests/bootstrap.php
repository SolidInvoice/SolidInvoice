<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Doctrine\DBAL\DriverManager;
use Doctrine\Deprecations\Deprecation;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv('SOLIDINVOICE_ENV', 'SOLIDINVOICE_DEBUG'))->bootEnv(dirname(__DIR__) . '/.env', 'test');

if (class_exists(Deprecation::class)) {
    Deprecation::enableWithTriggerError();
}

libxml_use_internal_errors(true);

(static function (): void {
    $kernel = new Kernel('test', true);
    $kernel->boot();

    // Create the test database through a temporary connection without a database name,
    // mirroring InstallBundle's CreateDatabaseStep. The doctrine:database:create command
    // cannot be used here: it determines the database platform from the main connection,
    // which requires connecting to the (not yet existing) database.
    /** @var ManagerRegistry $doctrine */
    $doctrine = $kernel->getContainer()->get('doctrine');
    $connection = $doctrine->getConnection();
    $params = $connection->getParams();

    if (isset($params['primary'])) {
        $params = $params['primary'];
    }

    if (($params['driver'] ?? '') === 'pdo_sqlite') {
        // Opening the connection is enough to create the SQLite database file.
        $tmpConnection = DriverManager::getConnection($params, $connection->getConfiguration());
        $tmpConnection->getNativeConnection();
        $tmpConnection->close();
    } elseif (isset($params['dbname'])) {
        $dbName = $params['dbname'];
        unset($params['dbname']);

        if (str_contains($params['driver'] ?? '', 'pgsql')) {
            $params['dbname'] = $params['default_dbname'] ?? 'postgres';
        }

        $tmpConnection = DriverManager::getConnection($params, $connection->getConfiguration());
        $schemaManager = $tmpConnection->createSchemaManager();

        if (! in_array($dbName, $schemaManager->listDatabases(), true)) {
            $schemaManager->createDatabase($tmpConnection->getDatabasePlatform()->quoteSingleIdentifier($dbName));
        }

        $tmpConnection->close();
    }

    $application = new Application($kernel);
    $application->setAutoExit(false);

    $application->run(new ArrayInput([
        'command' => 'doctrine:schema:update',
        '--force' => true,
        '--complete' => true,
        '--quiet' => true,
    ]));

    $kernel->shutdown();
})();

date_default_timezone_set('Africa/Johannesburg');
