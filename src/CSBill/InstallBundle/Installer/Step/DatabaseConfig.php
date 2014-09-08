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

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Exception\ParseException;
use CSBill\InstallBundle\Installer\AbstractFormStep;
use CSBill\InstallBundle\Form\Step\DatabaseConfigForm;

class DatabaseConfig extends AbstractFormStep
{
    /**
     * Array of currently implemented database drivers
     *
     * @var array
     */
    protected $implementedDrivers = array(
        'mysql'
    );

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var bool
     */
    private $debug;

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
            'drivers'            => $this->drivers,
            'host'               => 'localhost',
            'port'               => 3306,
            'connection_factory' => $this->get('doctrine.dbal.connection_factory')
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

        $kernel = $this->get('kernel');

        $this->rootDir = $kernel->getRootDir();
        $this->environment = $kernel->getEnvironment();
        $this->debug = $kernel->isDebug();

        $this->writeConfigFile($data);

        // @TODO stream the response back to the user, so they can get feedback on the process running
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
        $config = $this->rootDir . '/config/parameters.yml';

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
        // as this is the only time (for now) that we modify the parameters.yml file.
        // We still need to add an extra step so we can write smtp settings
        // @TODO add a more secure random string generator for enhanced security
        $value['parameters']['secret'] = md5(uniqid(php_uname(), true));

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
     * Load all fictures
     *
     * @return string
     */
    private function executeFixtures()
    {
        $command = 'php %s/console doctrine:fixtures:load --no-interaction --env=%s';

        if ($this->debug) {
            $command .= " --no-debug";
        }

        return $this->runProcess(sprintf($command, $this->rootDir, $this->environment));
    }

    /**
     * Runs a specific command with the Process Component
     *
     * @param  string            $command The command that needs to be run
     * @throws \RuntimeException
     * @return string            The output of the processed command
     */
    private function runProcess($command)
    {
        $process = new Process($command);
        $process->setTimeout(3600);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        return $process->getOutput();
    }
}
