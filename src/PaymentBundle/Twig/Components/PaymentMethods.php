<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\PaymentBundle\Twig\Components;

use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\PaymentBundle\Factory\PaymentFactories;
use SolidInvoice\PaymentBundle\Repository\PaymentMethodRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Symfony\UX\TwigComponent\Attribute\PreMount;
use function array_diff;
use function array_intersect;
use function array_keys;
use function array_map;
use function strtolower;

#[AsLiveComponent(name: 'PaymentMethods')]
final class PaymentMethods extends AbstractController
{
    use DefaultActionTrait;

    public function __construct(
        private readonly PaymentFactories $factories,
        private readonly PaymentMethodRepository $repository
    ) {
    }

    #[LiveProp(writable: true, url: true)]
    public string $method = '';

    #[PreMount]
    public function preMount(): void
    {
        $paymentMethods = $this->paymentMethods();

        $this->method = $this->method ?: ($paymentMethods['enabled'][0] ?? $paymentMethods['disabled'][0] ?? '');
    }

    /**
     * @return array{enabled: string[], disabled: string[]}
     */
    #[ExposeInTemplate]
    #[LiveListener('paymentMethodUpdated')]
    public function paymentMethods(): array
    {
        $paymentMethods = array_keys($this->factories->getFactories());

        $enabledMethods = array_map(
            static fn (PaymentMethod $method): string => strtolower($method->getGatewayName()),
            $this->repository->findBy(['enabled' => 1])
        );

        $enabled = array_intersect($paymentMethods, $enabledMethods);
        $disabled = array_diff($paymentMethods, $enabledMethods);

        sort($enabled);
        sort($disabled);

        return [
            'enabled' => $enabled,
            'disabled' => $disabled,
        ];
    }
}
