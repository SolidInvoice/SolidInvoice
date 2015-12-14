<?php
/**
 * This file is part of the CSBill project.
 *
 * @author    pierre
 */

namespace CSBill\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Class TemplatesCompileCommand
 *
 * @package CSBill\CoreBundle\Command
 */
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
            ->setDescription('Pre-compiles all the handlebars templates');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();

        $kernel = $container->get('kernel');

        /** @var Bundle $bundle */
        foreach ($kernel->getBundles() as $bundle) {

            if (is_dir($dir = $bundle->getPath().'/Resources/public/templates')) {
                $this->dirs[] = $dir;
            }
        }

        $this->process();
    }

    private function getNode()
    {
        $finder = new ExecutableFinder();

        return $finder->find('node', $finder->find('nodejs'));
    }

    private function process()
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
            '-m',
            '-e',
            'hbs',
        ];

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