<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InstallBundle\Installer\Database;

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Migration as VersionMigration;
use Doctrine\DBAL\Migrations\OutputWriter;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class Migration.
 *
 * Performs database migrations
 */
class Migration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

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
     * @param \Closure $outputWriter
     *
     * @return array
     *
     * @throws \Doctrine\DBAL\Migrations\MigrationException
     */
    public function migrate(\Closure $outputWriter = null): array
    {
        $dir = $this->container->getParameter('doctrine_migrations.dir_name');

        if (!$this->filesystem->exists($dir)) {
            $this->filesystem->mkdir($dir);
        }

        $configuration = $this->getConfiguration($dir, $outputWriter);

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
     * @param string   $dir
     * @param \Closure $outputWriter
     *
     * @return Configuration
     */
    private function getConfiguration($dir, \Closure $outputWriter = null): Configuration
    {
        $connection = $this->container->get('database_connection');

        $configuration = new Configuration($connection, new OutputWriter($outputWriter));
        $configuration->setMigrationsNamespace($this->container->getParameter('doctrine_migrations.namespace'));
        $configuration->setMigrationsDirectory($dir);
        $configuration->registerMigrationsFromDirectory($dir);
        $configuration->setName($this->container->getParameter('doctrine_migrations.name'));
        $configuration->setMigrationsTableName($this->container->getParameter('doctrine_migrations.table_name'));

        return $configuration;
    }
}
