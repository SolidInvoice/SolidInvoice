<?php
/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\InstallBundle\Installer\Step;

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
     * Writes all the configuration values to the paramaters file
     *
     * @param  array             $params
     * @throws \RuntimeException
     * @return void;
     */
    public function writeConfigFile($params = array())
    {
        $config = $this->rootDir . '/config/parameters.yml';

        $yaml = new Parser();

        try {
            $value = $yaml->parse(file_get_contents($config));
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

        // @TODO : check if we have permission to write to file, otherwise check if we can update permission on file
        file_put_contents($config, $yaml);
    }

    /**
     * Executes all doctrine migrations to create database structure
     *
     * @return string
     */
    public function executeMigrations()
    {
        $command = 'php %s/console doctrine:migrations:migrate --no-interaction --env=%s';

        if ($this->debug) {
            $command .= " --no-debug";
        }

        return $this->runProcess(sprintf($command, $this->rootDir, $this->environment));
    }

    /**
     * Load all fictures
     *
     * @return string
     */
    public function executeFixtures()
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
