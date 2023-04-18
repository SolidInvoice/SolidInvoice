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

namespace SolidInvoice\PaymentBundle\Action\Ajax;

use SolidInvoice\CoreBundle\Response\AjaxResponse;
use SolidInvoice\CoreBundle\Traits\JsonTrait;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\PaymentBundle\Factory\PaymentFactories;
use SolidInvoice\PaymentBundle\Repository\PaymentMethodRepository;
use Symfony\Component\HttpFoundation\Request;

final class MethodList implements AjaxResponse
{
    use JsonTrait;

    private PaymentFactories $factories;

    private PaymentMethodRepository $repository;

    public function __construct(PaymentFactories $factories, PaymentMethodRepository $repository)
    {
        $this->factories = $factories;
        $this->repository = $repository;
    }

    public function __invoke(Request $request)
    {
        $paymentMethods = array_keys($this->factories->getFactories());

        $enabledMethods = array_map(
            fn (PaymentMethod $method): string => strtolower($method->getGatewayName()),
            $this->repository->findBy(['enabled' => 1])
        );

        return $this->json(
            [
                'enabled' => array_intersect($paymentMethods, $enabledMethods),
                'disabled' => array_diff($paymentMethods, $enabledMethods),
            ]
        );
    }
}
