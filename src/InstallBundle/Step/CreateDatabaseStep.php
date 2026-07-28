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

namespace SolidInvoice\InstallBundle\Step;

use Doctrine\DBAL\DriverManager;
use Doctrine\Persistence\ManagerRegistry;
use Generator;
use SolidInvoice\InstallBundle\DTO\Installation;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use function in_array;
use function str_contains;
use function str_replace;

/**
 * @see \SolidInvoice\InstallBundle\Tests\Step\CreateDatabaseStepTest
 */
#[AsTaggedItem('Creating database', priority: 20)]
final readonly class CreateDatabaseStep implements InstallationStepInterface
{
    public function __construct(
        private ManagerRegistry $doctrine,
        #[Autowire(param: 'kernel.project_dir')]
        private string $projectDir,
    ) {
    }

    public static function priority(): int
    {
        return 20;
    }

    public function execute(Installation $installationData, ?callable $callback = null): Generator
    {
        $connection = $this->doctrine->getConnection();
        $params = $connection->getParams();

        if ($params['driver'] !== 'pdo_sqlite') {
            $dbName = $params['dbname'];
            unset($params['dbname']);

            // The database being created cannot be connected to yet, but DBAL 4's MySQL schema
            // manager resolves its metadata provider from the connection's database and throws
            // DatabaseRequired when there is none — so dropping dbname entirely fails before it can
            // create anything. information_schema exists on every MySQL and MariaDB server, is
            // readable without extra grants, and CREATE DATABASE is a server-level statement, so it
            // works as the connection target. PostgreSQL needs no equivalent: it falls back to a
            // default database on its own.
            if (str_contains($params['driver'], 'mysql')) {
                $params['dbname'] = 'information_schema';
            }
        } else {
            $dbName = str_replace($this->projectDir . '/', './', $params['path']);
        }

        $tmpConnection = DriverManager::getConnection(
            $params,
            $connection->getConfiguration(),
        );

        if ($params['driver'] === 'pdo_sqlite') {
            // Force the underlying connection to open, which creates the SQLite
            // file (Connection::connect() is protected in DBAL 4).
            $tmpConnection->getNativeConnection();
            $tmpConnection->close();
        } else {
            $schemaManager = $tmpConnection->createSchemaManager();
            if (! in_array($dbName, $schemaManager->introspectDatabaseNames(), true)) {
                $schemaManager->createDatabase($dbName);
            }
        }

        if ($callback !== null) {
            yield from $callback(sprintf('Database "%s" created', $dbName));
        }
    }

    public static function getLabel(): string
    {
        return 'Creating database';
    }
}
