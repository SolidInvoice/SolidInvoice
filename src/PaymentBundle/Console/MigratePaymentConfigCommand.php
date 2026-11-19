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

namespace SolidInvoice\PaymentBundle\Console;

use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RuntimeException;
use Payum\Core\Model\GatewayConfigInterface;
use Payum\Core\Security\CryptedInterface;
use Payum\Core\Security\CypherInterface;
use Payum\Core\Storage\StorageInterface;
use SolidWorx\Platform\PlatformBundle\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;
use function sprintf;

#[AsCommand(
    name: 'solidinvoice:payment:migrate-config',
    description: 'Migrate payment method config to use encryption',
)]
class MigratePaymentConfigCommand extends Command
{
    public function __construct(
        #[Autowire(service: 'payum.dynamic_gateways.encrypted_config_storage')]
        private readonly StorageInterface $storage,
        #[Autowire(service: 'payum.dynamic_gateways.cypher')]
        private readonly CypherInterface $cypher,
    ) {
        parent::__construct();
    }

    protected function handle(): int
    {
        $count = 0;

        foreach ($this->io->progressIterate($this->storage->findBy([])) as $gatewayConfig) {
            if (! $gatewayConfig instanceof GatewayConfigInterface || ! $gatewayConfig instanceof CryptedInterface) {
                throw new LogicException(sprintf('The gateway config %s must implement %s and %s.', $gatewayConfig::class, GatewayConfigInterface::class, CryptedInterface::class));
            }

            try {
                $gatewayConfig->decrypt($this->cypher);
            } catch (Throwable $e) {
                throw new RuntimeException(sprintf(
                    'Cannot decrypt the config of gateway "%s" with the configured key.',
                    $gatewayConfig->getGatewayName(),
                ), 0, $e);
            }

            // Without setConfig() a config stored as plain text is flagged as encrypted but keeps its plain values.
            $gatewayConfig->setConfig($gatewayConfig->getConfig());
            $gatewayConfig->encrypt($this->cypher);
            $this->storage->update($gatewayConfig);

            ++$count;
        }

        $this->io->writeln(sprintf('Re-encrypted %d gateway config(s).', $count));

        return self::SUCCESS;
    }
}
