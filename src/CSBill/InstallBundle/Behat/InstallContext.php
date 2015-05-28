<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InstallBundle\Behat;

use Behat\Gherkin\Node\TableNode;
use CSBill\CoreBundle\Behat\DefaultContext;
use CSBill\UserBundle\Entity\User;
use Doctrine\DBAL\DriverManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class InstallContext extends DefaultContext
{
    /**
     * @Given /^The application is not installed$/
     */
    public function applicationIsNotInstalled()
    {
        $connection = $this->getContainer()->get('doctrine')->getConnection();
        $params = $connection->getParams();
        $name = $params['dbname'];
        unset($params['dbname']);
        $connection->close();
        $connection = DriverManager::getConnection($params);
        $name = $connection->getDatabasePlatform()->quoteSingleIdentifier($name);

        if (in_array($name, $connection->getSchemaManager()->listDatabases())) {
            // Drop the current database
            $connection->getSchemaManager()->dropDatabase($name);
            // recreate current database
            $connection->getSchemaManager()->createDatabase($name);
        }

        $fs = new Filesystem();

        $configFile = $this->kernel->getRootDir().'/config/parameters.yml';

        $yaml = Yaml::parse(file_get_contents($configFile));
        $yaml['parameters']['installed'] = null;

        $fs->dumpFile($configFile, Yaml::dump($yaml), 0755);

        $fs->remove($this->kernel->getCacheDir().'/'.$this->kernel->getContainerCacheClass());
    }

    /**
     * @Then /^The config value "(?P<config>(?:[^"]|\\")*)" should not be empty$/
     *
     * @param string $config
     *
     * @throws \Exception
     */
    public function configIsNotEmpty($config)
    {
        $configFile = $this->kernel->getRootDir().'/config/parameters.yml';

        $yaml = Yaml::parse(file_get_contents($configFile))['parameters'];

        if (!array_key_exists($config, $yaml)) {
            throw new \Exception(sprintf('Key "%s" does not exist in config file', $config));
        }

        if (empty($yaml[$config])) {
            throw new \Exception(sprintf('Config "%s" should not be empty', $config));
        }
    }

    /**
     * @Then /^The config should contain the following values:$/
     *
     * @param TableNode $table
     *
     * @throws \Exception
     */
    public function configContainsValues(TableNode $table)
    {
        $configFile = $this->kernel->getRootDir().'/config/parameters.yml';

        $yaml = Yaml::parse(file_get_contents($configFile))['parameters'];

        foreach ($table->getRowsHash() as $config => $value) {
            if (!array_key_exists($config, $yaml)) {
                throw new \Exception(sprintf('Key "%s" does not exist in config file', $config));
            }

            if ($yaml[$config] != $value) {
                throw new \Exception(sprintf('Config "%s" does not match expected value. Expected "%s", got "%s"', $config, $value, $yaml[$config]));
            }
        }
    }

    /**
     * @Then /^the following user must exist:$/
     *
     * @param TableNode $table
     *
     * @throws \Exception
     */
    public function userExists(TableNode $table)
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $userRepository = $entityManager->getRepository('CSBillUserBundle:User');
        $passwordEncoder = $this->getContainer()->get('security.password_encoder');

        /** @var User[] $users */
        $users = $userRepository->findAll();

        foreach ($table->getHash() as $row) {
            $match = false;
            foreach ($users as $user) {
                if (
                    $user->getUsername() === $row['username'] &&
                    $user->getEmail() === $row['email'] &&
                    $user->getPassword() === $passwordEncoder->encodePassword($user, $row['password'])
                ) {
                    $match = true;
                    break;
                }
            }

            if (false === $match) {
                throw new \Exception(sprintf('User with username "%s" does not exist', $row['username']));
            }
        }
    }
}
