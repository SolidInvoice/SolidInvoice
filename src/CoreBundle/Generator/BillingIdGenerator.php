<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Generator;

use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator\IdGeneratorInterface;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class BillingIdGenerator
{
    /**
     * @param ServiceLocator<IdGeneratorInterface> $generators
     */
    public function __construct(
        #[TaggedLocator(IdGeneratorInterface::class, defaultIndexMethod: 'getName')]
        private readonly ServiceLocator $generators,
        private readonly SystemConfig $config,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws ContainerExceptionInterface
     */
    public function generate(object $entity, array $options = [], ?string $strategy = null): string
    {
        $settingSection = match (true) {
            $entity instanceof Invoice => 'invoice',
            $entity instanceof Quote => 'quote',
            default => throw new InvalidArgumentException('Invalid entity type'),
        };

        $strategy ??= $this->config->get($settingSection . '/id_generation/strategy');
        $invoiceId = $this->generators->get($strategy)->generate($entity, $options);

        return sprintf(
            '%s%s%s',
            $this->config->get($settingSection . '/id_generation/prefix') ?? '',
            $invoiceId,
            $this->config->get($settingSection . '/id_generation/suffix') ?? ''
        );
    }
}
