<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
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
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\Filesystem\Exception\IOException;
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
     * @param Closure $outputWriter
     *
     * @return array
     *
     * @throws InvalidArgumentException|MigrationException|IOException
     */
    public function migrate(Closure $outputWriter = null): array
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

        $migration = $configuration->getDependencyFactory()->getMigrator();

        return $migration->migrate();
    }

    /**
     * @param string  $dir
     * @param Closure $outputWriter
     *
     * @return Configuration
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
