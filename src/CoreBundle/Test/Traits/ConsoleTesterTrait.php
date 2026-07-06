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

namespace SolidInvoice\CoreBundle\Test\Traits;

use const PHP_EOL;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use function fopen;
use function fwrite;
use function rewind;

/**
 * Minimal replacement for Symfony's console TesterTrait, which can no longer be
 * composed into a KernelTestCase: its assertCommandIsSuccessful() signature
 * conflicts with the static assertion of the same name that KernelTestCase
 * gained in Symfony 8.
 */
trait ConsoleTesterTrait
{
    private InputInterface $input;

    private StreamOutput $output;

    private int $statusCode;

    /**
     * @param array{decorated?: bool, verbosity?: OutputInterface::VERBOSITY_*} $options
     */
    private function initOutput(array $options): void
    {
        $this->output = new StreamOutput(fopen('php://memory', 'w', false));

        if (isset($options['decorated'])) {
            $this->output->setDecorated($options['decorated']);
        }

        if (isset($options['verbosity'])) {
            $this->output->setVerbosity($options['verbosity']);
        }
    }

    /**
     * @param list<string> $inputs
     *
     * @return resource
     */
    private static function createStream(array $inputs)
    {
        $stream = fopen('php://memory', 'r+', false);

        foreach ($inputs as $input) {
            fwrite($stream, $input . PHP_EOL);
        }

        rewind($stream);

        return $stream;
    }
}
