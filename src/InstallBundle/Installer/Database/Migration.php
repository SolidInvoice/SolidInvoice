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

namespace SolidInvoice\InstallBundle\Installer\Database;

use Closure;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Exception\MigrationException;
use Doctrine\Migrations\OutputWriter;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class Migration.
 *
 * Performs database migrations
 */
class Migration
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(Filesystem $filesystem, ContainerInterface $container)
    {
        $this->filesystem = $filesystem;
        $this->container = $container;
    }

    /**
     * @param Closure $outputWriter
     *
     * @throws InvalidArgumentException|MigrationException|IOException
     */
    public function migrate(Closure $outputWriter = null): array
    {
        $dir = $this->container->getParameter('doctrine_migrations.dir_name');

        if (! $this->filesystem->exists($dir)) {
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

        $migration = $configuration->getDependencyFactory()->getMigrator();

        return $migration->migrate();
    }

    /**
     * @param string  $dir
     * @param Closure $outputWriter
     */
    private function getConfiguration($dir, Closure $outputWriter = null): Configuration
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
