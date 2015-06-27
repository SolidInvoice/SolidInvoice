<?php

/*
 * This file is part of CSBill package.
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
        if (null === $paymentMethod) {
            $paymentMethod = new PaymentMethod();
            $paymentMethod->setPaymentMethod($request->attributes->get('method'));
            $paymentMethod->setName(ucwords(str_replace('_', ' ', $request->attributes->get('method'))));
        }

        $originalSettings = $paymentMethod->getSettings();

        $registry = $this->get('form.registry');

        $form = $this->createForm(
            new PaymentMethodForm(),
            $paymentMethod,
            array(
                'settings' => $registry->hasType($paymentMethod->getPaymentMethod()) ? $paymentMethod->getPaymentMethod() : null,
            )
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

        return new JsonResponse(array(
            'content' => $this->renderView(
                'CSBillPaymentBundle:Ajax:loadmethodsettings.html.twig',
                array(
                    'form' => $form->createView(),
                    'method' => $paymentMethod->getPaymentMethod(),
                )
            ),
        ));
    }

    /**
     * Deletes a payment method.
     *
     * @param PaymentMethod $paymentMethod
     *
     * @return JsonResponse
     */
    public function deleteAction(PaymentMethod $paymentMethod)
    {
        $entityManager = $this->getEm();
        $entityManager->remove($paymentMethod);
        $entityManager->flush();

        $this->flash($this->trans('payment_delete_success'), 'success');

        return new JsonResponse(array('status' => 'success'));
    }
}
