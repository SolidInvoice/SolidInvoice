<?php

declare(strict_types=1);
/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;

class YamlFormatCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('yaml:format');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var SplFileInfo $file */
        foreach ($this->findFiles() as $file) {
            $path = $file->getRealPath();

            $yml = Yaml::parse($file->getContents());

            file_put_contents($path, Yaml::dump($yml, PHP_INT_MAX));
        }
    }

    /**
     * @return \Iterator
     */
    private function findFiles()
    {
        $container = $this->getContainer();
        $finder = Finder::create()
            ->files()
            ->ignoreDotFiles(true)
            ->ignoreUnreadableDirs(true)
            ->ignoreVCS(true)
            ->in($container->getParameter('kernel.root_dir').'/**')
            ->in(dirname($container->getParameter('kernel.root_dir')).'/src/**/*')
            ->name('*.yml');

        return $finder->getIterator();
    }
}
