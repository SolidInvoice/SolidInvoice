<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;

class YamlFormatCommand extends Command
{
    protected static $defaultName = 'yaml:format';

    private string $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var SplFileInfo $file */
        foreach ($this->findFiles() as $file) {
            $path = $file->getRealPath();

            $yml = Yaml::parse($file->getContents());

            file_put_contents($path, Yaml::dump($yml, PHP_INT_MAX));
        }

        return (int) Command::SUCCESS;
    }

    private function findFiles(): iterable
    {
        $finder = Finder::create()
            ->files()
            ->ignoreDotFiles(true)
            ->ignoreUnreadableDirs(true)
            ->ignoreVCS(true)
            ->in($this->projectDir . '/app')
            ->in($this->projectDir . '/src/**/*')
            ->name('*.yml');

        return $finder->getIterator();
    }
}
