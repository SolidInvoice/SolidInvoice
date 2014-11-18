<?php
/**
 * This file is part of the MiWay Business Insurance project.
 *
 * @author      MiWay Development Team
 * @copyright   Copyright (c) 2014 MiWay Insurance Ltd
 */

namespace CSBill\InstallBundle\Installer\Database;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader as DataFixturesLoader;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class FixtureLoader
 *
 * @package CSBill\InstallBundle\Installer\Database
 */
class FixtureLoader extends ContainerAware
{
    /**
     * Executes Database Fixtures
     */
    public function execute()
    {
        $em = $this->container->get('doctrine')->getManager();

        $paths = $this->getPaths();

        $loader = new DataFixturesLoader($this->container);

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $loader->loadFromDirectory($path);
            }
        }

        $fixtures = $loader->getFixtures();
        if (!$fixtures) {
            throw new \InvalidArgumentException(
                sprintf('Could not find any fixtures to load in: %s', "\n\n- " . implode("\n- ", $paths))
            );
        }
        $purger = new ORMPurger($em);
        $purger->setPurgeMode(ORMPurger::PURGE_MODE_DELETE);
        $executor = new ORMExecutor($em, $purger);

        $executor->execute($fixtures);
    }

    /**
     * @return array
     */
    private function getPaths()
    {
        $paths = array();

        /** @var Bundle $bundle */
        foreach ($this->container->get('kernel')->getBundles() as $bundle) {
            $paths[] = $bundle->getPath() . '/DataFixtures/ORM';
        }

        return $paths;
    }
}
