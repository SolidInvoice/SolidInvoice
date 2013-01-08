<?php
/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\InstallBundle\Command;

use CSBill\InstallBundle\Exception\ApplicationInstalledException;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class InstallCommand extends ContainerAwareCommand
{
    protected $invalid_options = array('help', 'quiet', 'verbose', 'version', 'ansi', 'no-ansi', 'no-interaction', 'shell', 'process-isolation', 'env', 'no-debug');

    protected function configure()
    {
        $this
            ->setName('app:install')
            ->setDescription('Install the application')
            ->addOption('accept', null, InputOption::VALUE_NONE, 'Do you accept the terms and conditions? (y/n) ')
            ->addOption('database_user', null, InputOption::VALUE_REQUIRED, 'What is your database username? ')
            ->addOption('database_host', null, InputOption::VALUE_REQUIRED, 'What is your database host? [localhost] ', 'localhost')
            ->addOption('database_name', null, InputOption::VALUE_REQUIRED, 'What is the name of the database you want to use? [csbill]', 'csbill')
            ->addOption('database_password', null, InputOption::VALUE_REQUIRED, 'What is your database password? ', '')
            ->addOption('database_port', null, InputOption::VALUE_REQUIRED, 'What is the port your database runs on? [3306]', 3306)
            ->addOption('email_address', null, InputOption::VALUE_REQUIRED, 'What is the email address of the administrator? ')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Please enter a password for the administrator ')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();

                    if (count($repository->findAll()) > 0) {
        $installer = $container->get('csbill.installer');

        if($installer->isInstalled())
        {
        	throw new ApplicationInstalledException();
        }

        $options = $this->getArgumentOptions($input, $output);

        // only current supported driver is mysql
        $options['database_driver'] = 'pdo_mysql';

        $installer = $container->get('csbill.installer');
        $progress = $this->getHelperSet()->get('progress');

        $progress->start($output, count($installer->getSteps()));

        do {
            $step = $installer->getStep();

            $response = $installer->validateStep($options);
        	$progress->advance();

            $output->writeln(sprintf('Installation: %s', $step->title));

        } while ($response !== false && stripos($response->getTargetUrl(), 'success') === false);

        $progress->finish();

        if (!$response) {
            $errors = $step->getErrors();
            $output->writeln('<error>'.implode("\n", $errors).'</error>');
        } else {
            $output->writeln('<info>Your applicaiton has been successfully installed</info>');
        }
    }

    protected function getArgumentOptions(InputInterface $input, OutputInterface $output)
    {
    	$options = array();

    	$dialog = $this->getHelperSet()->get('dialog');

    	foreach ($this->getDefinition()->getOptions() as $argument) {
    		$name = $argument->getName();

    		if (in_array($name, $this->invalid_options)) {
    			continue;
    		}

    		if (!$argument->acceptValue()) {
    			if ($input->hasParameterOption('--'.$name)) {
    				$options[$name] = 'y';
    			} else {
    				do {
    					$value = $dialog->ask($output, '<question>'.$argument->getDescription().'</question>', $input->getParameterOption('--'.$name));

    					if ($value === 'n') {
    						return;
    					} elseif ($value !== 'y') {
    						$output->writeln("<comment>Please only enter 'y' or 'n'</comment>");
    					}
    				} while ($value !== 'y');

    				$options[$name] = $value;
    			}
    		} else {

    			if (!$input->getParameterOption('--'.$name)) {

    				if ($input->hasParameterOption('--'.$name.'=')) {
    					$value = '';
    				} else {
    					$value = $argument->getDefault();

    					do {
    						$value = $dialog->ask($output, '<question>'.$argument->getDescription().'</question>', $value);
    					} while ($value === null);
    				}

    			} else {
    				$value = $input->getOption($name);
    			}

    			$options[$name] = $value;
    		}
    	}

    	return $options;
    }
}
