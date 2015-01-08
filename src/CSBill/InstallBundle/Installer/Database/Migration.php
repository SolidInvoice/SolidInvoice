<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InstallBundle\Installer\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Migration as VersionMigration;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class Migration
 *
 * Performs database migrations
 *
 * @package CSBill\InstallBundle\Installer\Database
 */
class Migration extends ContainerAware
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @param array $params
     *
     * @return array
     * @throws \Doctrine\DBAL\Migrations\MigrationException
     */
    public function migrate(array $params)
    {
        $dir = $this->container->getParameter('doctrine_migrations.dir_name');

        if (!$this->filesystem->exists($dir)) {
            $this->filesystem->mkdir($dir);
        }

        $configuration = $this->getConfiguration($dir, $params);

        $versions = $configuration->getMigrations();

        foreach ($versions as $version) {
            $migration = $version->getMigration();
            if ($migration instanceof ContainerAwareInterface) {
                $migration->setContainer($this->container);
            }
        }

        $migration = new VersionMigration($configuration);

        return $migration->migrate();
    }

    /**
     * @param string $dir
     * @param array  $params
     *
     * @return Configuration
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getConfiguration($dir, array $params)
    {
        $connection = DriverManager::getConnection($params);

        $configuration = new Configuration($connection);
        $configuration->setMigrationsNamespace($this->container->getParameter('doctrine_migrations.namespace'));
        $configuration->setMigrationsDirectory($dir);
        $configuration->registerMigrationsFromDirectory($dir);
        $configuration->setName($this->container->getParameter('doctrine_migrations.name'));
        $configuration->setMigrationsTableName($this->container->getParameter('doctrine_migrations.table_name'));

        return $configuration;
    }
}
