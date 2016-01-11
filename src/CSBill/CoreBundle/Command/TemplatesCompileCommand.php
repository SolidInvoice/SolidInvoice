<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

class TemplatesCompileCommand extends ContainerAwareCommand
{
    const HANDLEBARS_PATH = '../node_modules/handlebars/bin/handlebars';
    const OUTPUT_FILE_NAME = 'js/hbs-templates.js';

    /**
     * @var array
     */
    private $dirs = [];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('handlebars:compile')
            ->setDescription('Pre-compiles all the handlebars templates')
            ->addOption('optimize', 'o', InputOption::VALUE_NONE, 'Optimize the compiled templates')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $optimize = $input->getOption('optimize');
        $container = $this->getContainer();

        $kernel = $container->get('kernel');

        /** @var Bundle $bundle */
        foreach ($kernel->getBundles() as $bundle) {
            if (is_dir($dir = $bundle->getPath().'/Resources/public/templates')) {
                $this->dirs[] = $dir;
            }
        }

        $this->process($optimize);
    }

    /**
     * @return string
     */
    private function getNode()
    {
        $finder = new ExecutableFinder();

        return $finder->find('node', $finder->find('nodejs'));
    }

    /**
     * @param bool $optimize
     *
     * @throws \Exception
     */
    private function process($optimize = false)
    {
        $webRoot = $this->getContainer()->getParameter('oro_require_js.web_root');

        $node = $this->getNode();

        if (null === $node) {
            throw new \Exception('Could not find node bin. Please ensure node is installed and available in your $PATH');
        }

        $command = [
            $node,
            self::HANDLEBARS_PATH,
            implode(' ', $this->dirs),
            '-f',
            self::OUTPUT_FILE_NAME,
            '-a',
            '-e',
            'hbs',
        ];

        if (true === $optimize) {
            $command[] = '-m';
        }

        $builder = new ProcessBuilder($command);
        $builder->setWorkingDirectory($webRoot);

        $process = new Process(implode(' ', $command), $webRoot);
        $process->setTimeout(0);

        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }
    }
}
