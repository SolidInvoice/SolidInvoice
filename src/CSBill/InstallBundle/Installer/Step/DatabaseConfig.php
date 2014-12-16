<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InstallBundle\Installer\Step;

use CSBill\InstallBundle\Form\Step\DatabaseConfigForm;
use CSBill\InstallBundle\Installer\AbstractFormStep;
use RandomLib\Factory;
use SecurityLib\Strength;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

class DatabaseConfig extends AbstractFormStep
{
    /**
     * Array of currently implemented database drivers
     *
     * @var array
     */
    protected $implementedDrivers = array(
        'mysql',
    );

    /**
     * @var array
     */
    protected $drivers = array();

    /**
     * @return DatabaseConfigForm
     */
    public function getForm()
    {
        return new DatabaseConfigForm();
    }

    /**
     * @return array
     */
    public function getFormData()
    {
        $this->drivers = array_intersect($this->implementedDrivers, \PDO::getAvailableDrivers());

        return array(
            'drivers' => $this->drivers,
            'host' => 'localhost',
            'port' => 3306,
            'connection_factory' => $this->get('doctrine.dbal.connection_factory'),
        );
    }

    /**
     * Writes the database configuration to the parameters.yml file, and runs all the database migrations and fixtures
     */
    public function process()
    {
        $form = $this->buildForm();
        $data = $form->getData();

        $data['driver'] = sprintf('pdo_%s', $this->drivers[$data['driver']]);

        $this->writeConfigFile($data);

        $this->executeMigrations();
        $this->executeFixtures();
    }

    /**
     * Writes all the configuration values to the parameters file
     *
     * @param array $params
     *
     * @throws \Exception
     * @return void;
     */
    private function writeConfigFile($params = array())
    {
        $config = $this->get('kernel')->getRootDir() . '/config/parameters.yml';

        $yamlParser = new Parser();

        try {
            $value = $yamlParser->parse(file_get_contents($config));
        } catch (ParseException $e) {
            throw new \RuntimeException(
                "Unable to parse the YAML string: %s. Your installation might be corrupt.",
                $e->getCode(),
                $e
            );
        }

        foreach ($params as $key => $param) {
            $key = sprintf('database_%s', $key);

            // sets the database details
            $value['parameters'][$key] = $param;
        }

        // Sets a unique value for the secret token.
        // We do this when writing the database configuration,
        // as this is the only time that we modify the parameters.yml file.
        $value['parameters']['secret'] = $this->generateRandomString();

        $dumper = new Dumper();

        $yaml = $dumper->dump($value, 2);

        $fileSystem = $this->container->get('filesystem');
        $fileSystem->dumpFile($config, $yaml, 0644);

        /** @var \AppKernel $kernel */
        $kernel = $this->container->get('kernel');
        $fileSystem->remove(sprintf(
                '%s/%s.php',
                $kernel->getCacheDir(),
                $kernel->getContainerCacheClass()
            )
        );
    }

    /**
     * Executes all doctrine migrations to create database structure
     *
     * @return string
     */
    private function executeMigrations()
    {
        $migration = $this->get('csbill.installer.database.migration');

        return $migration->migrate();
    }

    /**
     * Load all fixtures
     *
     * @return string
     */
    private function executeFixtures()
    {
        $fixtureLoader = $this->get('csbill.installer.database.fixtures');

        $fixtureLoader->execute();
    }

    /**
     * Generates a secure random string
     *
     * @return string
     */
    private function generateRandomString()
    {
        $factory = new Factory;
        $generator = $factory->getGenerator(new Strength(Strength::MEDIUM));

        return $generator->generateString(32);
    }
}
