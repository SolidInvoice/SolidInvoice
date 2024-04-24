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

use SolidInvoice\PaymentBundle\Factory\PaymentFactories;
use SolidInvoice\PaymentBundle\Repository\PaymentMethodRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Symfony\UX\TwigComponent\Attribute\PreMount;

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
        $this->method = $this->method ?: $this->paymentMethods()[0]?->getGatewayName() ?? '';
    }

    /**
     * @return array{enabled: string[], disabled: string[]}
     */
    #[ExposeInTemplate]
    #[LiveListener('paymentMethodUpdated')]
    public function paymentMethods(): array
    {
        return $this->repository->findBy([], ['name' => 'ASC']);
    }
}
