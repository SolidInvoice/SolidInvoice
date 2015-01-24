<?php

namespace CSBill\InstallBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AppInstallCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:install')
            ->setDescription('Installs the application')
            ->setDefinition($this->getInputDefinition());
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('admin-email') || !$input->getOption('admin-password')) {
            throw new \Exception('An admin email and password is required for the installation process');
        }

        $command = $this->getApplication()->find('doctrine:migrations:migrate');

        $arguments = array(
            'command' => 'doctrine:migrations:migrate',
            '--no-interaction',
            '-vvv' => true
        );

        //$input->setOption('--no-interaction', true);

        $input = new ArrayInput($arguments);
        var_dump($command->run($input, $output));
        exit;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        /** @var \Symfony\Component\Console\Helper\DialogHelper $dialog */
        $dialog = $this->getHelper('dialog');

        if (!$input->getOption('admin-email')) {
            $input->setOption(
                'admin-email',
                $dialog->ask($output, 'Please enter the email address for the admin user: ')
            );
        }

        if (!$input->getOption('admin-password')) {
            $input->setOption(
                'admin-password',
                $dialog->askHiddenResponse($output, 'Please enter a password for the admin user: ')
            );
        }
    }

    /**
     * @return array
     */
    private function getInputDefinition()
    {
        return array(
            new InputOption('admin-username', null, InputOption::VALUE_REQUIRED, 'The username for the admin user', 'admin'),
            new InputOption('admin-email', null, InputOption::VALUE_REQUIRED, 'Please enter the name of the database'),
            new InputOption('admin-password', null, InputOption::VALUE_REQUIRED, 'Please enter the password for the database user'),
        );
    }

    private function initializeDatabase()
    {
        $command = $this->getApplication()->find('doctrine:migrations:migrate');

        $arguments = array(
            'command' => 'doctrine:migrations:migrate',
            '--no-interaction' => true,
            '-vvv' => true
        );

        $input = new ArrayInput($arguments);
        $returnCode = $command->run($input, $output);
    }
}