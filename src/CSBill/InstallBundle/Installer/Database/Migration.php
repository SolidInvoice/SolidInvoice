<?php
/**
 * This file is part of the MiWay Business Insurance project.
 *
 * @author      MiWay Development Team
 * @copyright   Copyright (c) 2014 MiWay Insurance Ltd
 */

namespace CSBill\InstallBundle\Installer\Database;

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
     * @return array
     * @throws \Doctrine\DBAL\Migrations\MigrationException
     */
    public function migrate()
    {
        $dir = $this->container->getParameter('doctrine_migrations.dir_name');

        if (!$this->filesystem->exists($dir)) {
            $this->filesystem->mkdir($dir);
        }

        $configuration = $this->getConfiguration($dir);

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
     *
     * @return Configuration
     */
    private function getConfiguration($dir)
    {
        $conn = $this->container->get('doctrine')->getConnection();

        $configuration = new Configuration($conn);
        $configuration->setMigrationsNamespace($this->container->getParameter('doctrine_migrations.namespace'));
        $configuration->setMigrationsDirectory($dir);
        $configuration->registerMigrationsFromDirectory($dir);
        $configuration->setName($this->container->getParameter('doctrine_migrations.name'));
        $configuration->setMigrationsTableName($this->container->getParameter('doctrine_migrations.table_name'));

        return $configuration;
    }
}
