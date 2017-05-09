<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Action\Ajax;

use CSBill\CoreBundle\Response\AjaxResponse;
use CSBill\CoreBundle\Traits\JsonTrait;
use CSBill\PaymentBundle\Entity\PaymentMethod;
use CSBill\PaymentBundle\Factory\PaymentFactories;
use CSBill\PaymentBundle\Repository\PaymentMethodRepository;
use Symfony\Component\HttpFoundation\Request;

final class MethodList implements AjaxResponse
{
    use JsonTrait;

    /**
     * @var PaymentFactories
     */
    private $factories;

    /**
     * @var PaymentMethodRepository
     */
    private $repository;

    public function __construct(PaymentFactories $factories, PaymentMethodRepository $repository)
    {
        $this->factories = $factories;
        $this->repository = $repository;
    }

    public function __invoke(Request $request)
    {
        $paymentMethods = array_keys($this->factories->getFactories());

        $enabledMethods = array_map(
            function (PaymentMethod $method): string {
                return strtolower($method->getGatewayName());
            }, $this->repository->findBy(['enabled' => 1])
        );

        return $this->json(
            [
                'enabled' => array_intersect($paymentMethods, $enabledMethods),
                'disabled' => array_diff($paymentMethods, $enabledMethods),
            ]
        );
    }
}