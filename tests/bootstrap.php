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
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

(new Dotenv('SOLIDINVOICE_ENV', 'SOLIDINVOICE_DEBUG'))->bootEnv(dirname(__DIR__) . '/.env', 'test');

if (class_exists(Deprecation::class)) {
    Deprecation::enableWithTriggerError();
}

// suppress errors with libxml when using html snapshots and some tags (E.G svg, section) are not supported
libxml_use_internal_errors(true);
date_default_timezone_set('Africa/Johannesburg');

if (false === (bool) $_SERVER['APP_DEBUG'] && null === ($_SERVER['TEST_TOKEN'] ?? null)) {
    /*
     * Ensure a fresh cache when debug mode is disabled. When using paratest, this
     * file is required once at the very beginning, and once per process. Checking that
     * TEST_TOKEN is not set ensures this is only run once at the beginning.
     */
    new Filesystem()->remove(__DIR__ . '/../var/cache/test');
}

/*
 * The suite runs two kernels: the default one and SaasTestKernel, which has its own cache
 * dir and therefore its own database (SOLIDINVOICE_DATABASE_URL is relative to
 * %kernel.cache_dir%). Foundry's automatic reset only builds one schema per run - the one
 * belonging to whichever test class runs first - so the other database is left with a stale
 * schema, or none at all on a fresh checkout or in CI.
 *
 * Both are prepared here instead. Foundry then rebuilds whichever one it picks, which is
 * harmless. See config/packages/saas/dama_doctrine_test.yaml for the matching connection
 * key split that stops the two kernels from sharing a single static connection.
 */
(static function (): void {
    $env = $_ENV['SOLIDINVOICE_ENV'] ?? $_SERVER['SOLIDINVOICE_ENV'] ?? 'test';
    $debug = filter_var((string) ($_ENV['SOLIDINVOICE_DEBUG'] ?? $_SERVER['SOLIDINVOICE_DEBUG'] ?? 'true'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

    $prepare = static function (KernelInterface $kernel): void {
        $kernel->boot();

        // Create the database through a temporary connection without a database name,
        // mirroring InstallBundle's CreateDatabaseStep. The doctrine:database:create command
        // cannot be used here: it determines the database platform from the main connection,
        // which requires connecting to the (not yet existing) database.
        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');
        $connection = $doctrine->getConnection();
        $params = $connection->getParams();

        if (($params['driver'] ?? '') === 'pdo_sqlite') {
            $directory = dirname((string) $params['path']);

            if (! is_dir($directory) && ! mkdir($directory, 0o777, true) && ! is_dir($directory)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $directory));
            }

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

            if (! in_array($dbName, $schemaManager->introspectDatabaseNames(), true)) {
                $schemaManager->createDatabase($tmpConnection->getDatabasePlatform()->quoteSingleIdentifier($dbName));
            }

            $tmpConnection->close();
        }

        $application = new Application($kernel);
        $application->setAutoExit(false);
        $output = new BufferedOutput();
        $command = ['command' => 'doctrine:schema:update', '--force' => true, '--complete' => true];

        if ($application->run(new ArrayInput($command), $output) !== 0) {
            throw new RuntimeException(sprintf(
                'Failed to prepare the test database for %s: %s',
                $kernel::class,
                $output->fetch()
            ));
        }

        $kernel->shutdown();
    };

    $prepare(new SolidInvoice\Test\Kernel($env, $debug));
    $prepare(new SolidInvoice\Test\SaasKernel($env, $debug));
})();
