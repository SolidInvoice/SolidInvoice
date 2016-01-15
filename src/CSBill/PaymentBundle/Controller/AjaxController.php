<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Controller;

use CSBill\CoreBundle\Controller\BaseController;
use CSBill\PaymentBundle\Entity\PaymentMethod;
use CSBill\PaymentBundle\Form\PaymentMethodForm;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AjaxController extends BaseController
{
    /**
     * @ParamConverter("paymentMethod", options={"mapping": {"method": "paymentMethod"}})
     *
     * @param Request       $request
     * @param PaymentMethod $paymentMethod
     *
     * @return JsonResponse
     */
    public function loadSettingsAction(Request $request, PaymentMethod $paymentMethod = null)
    {
        $methodName = $request->attributes->get('method');

        if (null === $paymentMethod) {
            $paymentMethod = new PaymentMethod();
            $paymentMethod->setPaymentMethod($methodName);
            $paymentMethod->setName(ucwords(str_replace('_', ' ', $methodName)));
        }

        $originalSettings = $paymentMethod->getSettings();

        /** @var \CSBill\PaymentBundle\Payum $payum */
        $payum = $this->get('payum');
        $registry = $this->get('form.registry');

        $form = $this->createForm(
            new PaymentMethodForm(),
            $paymentMethod,
            [
                'settings' => $registry->hasType($paymentMethod->getPaymentMethod()) ? $paymentMethod->getPaymentMethod() : null,
                'internal' => $payum->isOffline($paymentMethod->getPaymentMethod()),
                'action' => $this->generateUrl('_payment_method_settings', ['method' => $methodName]),
            ]
        );

        $form->handleRequest($request);

        if ($form->isValid()) {
            $settings = (array) $paymentMethod->getSettings();

            foreach ($settings as $key => $value) {
                if ('password' === $key && null === $value && !empty($originalSettings[$key])) {
                    $settings[$key] = $originalSettings[$key];
                }
            }

            $paymentMethod->setSettings($settings);
            $this->save($paymentMethod);
            $this->flash($this->trans('payment.method.updated'), 'success');
        }

        return $this->json(
            [
                'content' => $this->renderView(
                    'CSBillPaymentBundle:Ajax:loadmethodsettings.html.twig',
                    [
                        'form' => $form->createView(),
                        'method' => $paymentMethod->getPaymentMethod(),
                    ]
                ),
            ]
        );
    }
}
