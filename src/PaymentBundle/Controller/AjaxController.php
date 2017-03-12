<?php

declare(strict_types=1);
/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Controller;

use CSBill\CoreBundle\Controller\BaseController;
use CSBill\PaymentBundle\Entity\PaymentMethod;
use CSBill\PaymentBundle\Form\Type\PaymentMethodType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AjaxController extends BaseController
{
    /**
     * @ParamConverter("paymentMethod", options={"mapping": {"method": "gatewayName"}})
     *
     * @param Request       $request
     * @param PaymentMethod $paymentMethod
     *
     * @return JsonResponse
     */
    public function loadSettingsAction(Request $request, PaymentMethod $paymentMethod = null)
    {
        $methodName = $request->attributes->get('method');
        $paymentFactories = $this->get('payum.factories');

        if (null === $paymentMethod) {
            $paymentMethod = new PaymentMethod();
            $paymentMethod->setGatewayName($methodName);
            $paymentMethod->setFactoryName($paymentFactories->getFactory($methodName));
            $paymentMethod->setName(ucwords(str_replace('_', ' ', $methodName)));
        }

        $originalSettings = $paymentMethod->getConfig();

        $form = $this->createForm(
            PaymentMethodType::class,
            $paymentMethod,
            [
                'config' => $paymentFactories->getForm($paymentMethod->getGatewayName()),
                'internal' => $paymentFactories->isOffline($paymentMethod->getGatewayName()),
                'action' => $this->generateUrl('_payment_method_settings', ['method' => $methodName]),
            ]
        );

        $form->handleRequest($request);

        if ($form->isValid()) {
            $settings = (array) $paymentMethod->getConfig();

            foreach ($settings as $key => $value) {
                if ('password' === $key && null === $value && !empty($originalSettings[$key])) {
                    $settings[$key] = $originalSettings[$key];
                }
            }

            $paymentMethod->setConfig($settings);
            $this->save($paymentMethod);
            $this->flash($this->trans('payment.method.updated'), 'success');
        }

        return $this->json(
            [
                'content' => $this->renderView(
                    'CSBillPaymentBundle:Ajax:loadmethodsettings.html.twig',
                    [
                        'form' => $form->createView(),
                        'method' => $paymentMethod->getGatewayName(),
                    ]
                ),
            ]
        );
    }
}
