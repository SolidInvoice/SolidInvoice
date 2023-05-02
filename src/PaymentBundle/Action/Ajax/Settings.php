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

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use SolidInvoice\CoreBundle\Response\AjaxResponse;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\PaymentBundle\Factory\PaymentFactories;
use SolidInvoice\PaymentBundle\Form\Handler\PaymentMethodSettingsHandler;
use SolidWorx\FormHandler\FormHandler;
use Symfony\Component\HttpFoundation\Request;

final class Settings implements AjaxResponse
{
    public function __construct(
        private readonly PaymentFactories $factories,
        private readonly FormHandler $handler
    ) {
    }

    /**
     * @ParamConverter("paymentMethod", options={"mapping": {"method": "gatewayName"}})
     */
    public function __invoke(Request $request, ?PaymentMethod $paymentMethod)
    {
        $methodName = $request->attributes->get('method');

        if (! $paymentMethod instanceof PaymentMethod) {
            $paymentMethod = new PaymentMethod();
            $paymentMethod->setGatewayName($methodName);
            $paymentMethod->setFactoryName($this->factories->getFactory($methodName));
            $paymentMethod->setName(ucwords(str_replace('_', ' ', (string) $methodName)));
        }

        return $this->handler->handle(PaymentMethodSettingsHandler::class, ['payment_method' => $paymentMethod]);
    }
}
