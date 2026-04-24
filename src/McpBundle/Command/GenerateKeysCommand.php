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
use SolidWorx\Platform\PlatformBundle\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

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

    protected function handle(): int
    {
        $force = (bool) $this->io->getOption('force');

        if ($this->keyManager->hasKeys() && ! $force) {
            $this->io->info(sprintf('Keys already exist at %s. Use --force to regenerate.', $this->keyManager->getKeyDir()));

            return self::SUCCESS;
        }

        $generated = $this->keyManager->generate($force);

        if (! $generated) {
            $this->io->info('Keys already exist; nothing to do.');

            return self::SUCCESS;
        }

        $this->io->success(sprintf('RSA keys generated at %s.', $this->keyManager->getKeyDir()));

        return self::SUCCESS;
    }
}
