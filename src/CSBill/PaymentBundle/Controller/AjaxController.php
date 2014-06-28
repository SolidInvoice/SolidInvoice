<?php

namespace CSBill\PaymentBundle\Controller;

use CSBill\CoreBundle\Controller\BaseController;
use CSBill\PaymentBundle\Entity\PaymentMethod;
use CSBill\PaymentBundle\Form\PaymentMethodForm;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AjaxController extends BaseController
{
    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function loadSettingsAction(Request $request)
    {
        $form = $this->createForm(
            new PaymentMethodForm(),
            new PaymentMethod(),
            array(
                'manager' => $this->get('csbill_payment.method.manager')
            )
        );

        $form->handleRequest($request);

        return new JsonResponse(array(
            'content' => $this->renderView(
                'CSBillPaymentBundle:Ajax:loadmethodsettings.html.twig',
                array(
                    'form' => $form->createView()
                )
            )
        ));
    }

    /**
     * Deletes a payment method
     *
     * @param  PaymentMethod $paymentMethod
     * @return JsonResponse
     */
    public function deleteAction(PaymentMethod $paymentMethod)
    {
        $entityManager = $this->getEm();
        $entityManager->remove($paymentMethod);
        $entityManager->flush();

        $this->flash($this->trans('payment_delete_success'), 'success');

        return new JsonResponse(array("status" => "success"));
    }
}
