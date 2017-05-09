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

namespace CSBill\PaymentBundle\Form\Handler;

use CSBill\CoreBundle\Response\FlashResponse;
use CSBill\CoreBundle\Templating\Template;
use CSBill\CoreBundle\Traits\SaveableTrait;
use CSBill\PaymentBundle\Entity\PaymentMethod;
use CSBill\PaymentBundle\Factory\PaymentFactories;
use CSBill\PaymentBundle\Form\Type\PaymentMethodType;
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormHandlerResponseInterface;
use SolidWorx\FormHandler\FormHandlerSuccessInterface;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class PaymentMethodSettingsHandler implements FormHandlerInterface, FormHandlerSuccessInterface, FormHandlerResponseInterface
{
    use SaveableTrait;

    /**
     * @var PaymentFactories
     */
    private $paymentFactories;

    /**
     * @var RouterInterface
     */
    private $router;

    private $originalSettings = [];

    public function __construct(PaymentFactories $paymentFactories, RouterInterface $router)
    {
        $this->paymentFactories = $paymentFactories;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function getForm(FormFactoryInterface $factory = null, ...$options)
    {
        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = $options[0];

        $this->originalSettings = $paymentMethod->getConfig();

        return $factory->create(
            PaymentMethodType::class,
            $paymentMethod,
            [
                'config' => $this->paymentFactories->getForm($paymentMethod->getGatewayName()),
                'internal' => $this->paymentFactories->isOffline($paymentMethod->getGatewayName()),
                'action' => $this->router->generate('_xhr_payments_settings', ['method' => $paymentMethod->getGatewayName()]),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function onSuccess($data, FormRequest $form): ?Response
    {
        /* @var PaymentMethod $data */

        $settings = (array) $data->getConfig();

        foreach ($settings as $key => $value) {
            if ('password' === $key && null === $value && !empty($this->originalSettings[$key])) {
                $settings[$key] = $this->originalSettings[$key];
            }
        }

        $this->originalSettings = [];

        $data->setConfig($settings);
        $this->save($data);

        $form->getRequest()->getSession()->getFlashBag()->add(FlashResponse::FLASH_SUCCESS, 'payment.method.updated');

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse(FormRequest $formRequest)
    {
        return new Template(
            '@CSBillPayment/Ajax/loadmethodsettings.html.twig',
            [
                'form' => $formRequest->getForm()->createView(),
                'method' => $formRequest->getForm()->getData()->getGatewayName(),
            ]
        );
    }
}
