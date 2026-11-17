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

use Payum\Core\Storage\StorageInterface;
use SolidInvoice\PaymentBundle\Repository\PaymentMethodRepository;
use SolidWorx\Platform\PlatformBundle\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'solidinvoice:payment:migrate-config',
    description: 'Migrate payment method config to use encryption',
)]
class MigratePaymentConfigCommand extends Command
{
    public function __construct(
        private readonly PaymentMethodRepository $paymentMethodRepository,
        #[Autowire(service: 'payum.dynamic_gateways.encrypted_config_storage')]
        private readonly StorageInterface $storage,
    ) {
        parent::__construct();
    }

    protected function handle(): int
    {
        foreach ($this->io->progressIterate($this->paymentMethodRepository->findAll()) as $paymentMethod) {
            $paymentMethod->setConfig($paymentMethod->getConfig());
            $this->storage->update($paymentMethod);
        }

        return self::SUCCESS;
    }
}
