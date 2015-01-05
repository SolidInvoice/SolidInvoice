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
     */
    private function writeConfigFile(array $params = array())
    {
        $value = array('parameters' => array());

        foreach ($params as $key => $param) {
            $key = sprintf('database_%s', $key);

            // sets the database details
            $value['parameters'][$key] = $param;
        }

        $this->get('csbill.core.config_writer')->dump($value);
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
}
