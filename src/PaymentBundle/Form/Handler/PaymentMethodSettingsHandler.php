<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\PaymentBundle\Form\Handler;

use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\CoreBundle\Traits\SaveableTrait;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\PaymentBundle\Factory\PaymentFactories;
use SolidInvoice\PaymentBundle\Form\Type\PaymentMethodType;
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormHandlerOptionsResolver;
use SolidWorx\FormHandler\FormHandlerResponseInterface;
use SolidWorx\FormHandler\FormHandlerSuccessInterface;
use SolidWorx\FormHandler\FormRequest;
use SolidWorx\FormHandler\Options;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

class PaymentMethodSettingsHandler implements FormHandlerInterface, FormHandlerSuccessInterface, FormHandlerResponseInterface, FormHandlerOptionsResolver
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
    public function getForm(FormFactoryInterface $factory, Options $options)
    {
        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = $options->get('payment_method');

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
    public function onSuccess(FormRequest $form, $data): ?Response
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

        $session = $form->getRequest()->getSession();

        if ($session) {
            $session->getFlashBag()->add(FlashResponse::FLASH_SUCCESS, 'payment.method.updated');
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse(FormRequest $formRequest)
    {
        return new Template(
            '@SolidInvoicePayment/Ajax/loadmethodsettings.html.twig',
            [
                'form' => $formRequest->getForm()->createView(),
                'method' => $formRequest->getForm()->getData()->getGatewayName(),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('payment_method')
            ->setAllowedTypes('payment_method', PaymentMethod::class);
    }
}
