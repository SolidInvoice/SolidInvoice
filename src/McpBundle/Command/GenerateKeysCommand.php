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

namespace SolidInvoice\McpBundle\Command;

use SolidInvoice\McpBundle\OAuth\KeyManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'mcp:keys:generate',
    description: 'Generates RSA signing keys for MCP OAuth access-token JWTs',
)]
final class GenerateKeysCommand extends Command
{
    public function __construct(
        private readonly KeyManager $keyManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Overwrite existing keys');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = (bool) $input->getOption('force');

        if ($this->keyManager->hasKeys() && ! $force) {
            $io->info(sprintf('Keys already exist at %s. Use --force to regenerate.', $this->keyManager->getKeyDir()));

            return Command::SUCCESS;
        }

        $generated = $this->keyManager->generate($force);

        if (! $generated) {
            $io->info('Keys already exist; nothing to do.');

            return Command::SUCCESS;
        }

        $io->success(sprintf('RSA keys generated at %s.', $this->keyManager->getKeyDir()));

        return Command::SUCCESS;
    }
}
