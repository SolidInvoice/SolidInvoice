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

namespace SolidInvoice\PaymentBundle\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\PaymentBundle\Exception\InvalidGatewayException;
use SolidInvoice\PaymentBundle\Factory\PaymentFactories;
use SolidInvoice\PaymentBundle\Form\Type\PaymentMethodType;
use SolidInvoice\PaymentBundle\Repository\PaymentMethodRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use function assert;

#[AsLiveComponent]
final class PaymentSettings extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    public function __construct(
        private readonly PaymentFactories $factories,
        private readonly PaymentMethodRepository $repository
    ) {
    }

    #[LiveProp(writable: true, updateFromParent: true, url: true)]
    public string $method = '';

    #[LiveProp(writable: true)]
    public ?bool $showDeleteConfirmation = false;

    #[ExposeInTemplate]
    public function paymentMethod(): PaymentMethod
    {
        $paymentMethod = $this->repository->findOneBy(['gatewayName' => $this->method]);

        if (! $paymentMethod instanceof PaymentMethod) {
            try {
                $paymentMethod = new PaymentMethod();
                $paymentMethod->setFactoryName($this->factories->getFactory($this->method));
                $paymentMethod->setInternal($this->factories->isOffline($this->method));
            } catch (InvalidGatewayException) {
                throw $this->createNotFoundException(sprintf('Payment gateway "%s" not found', $this->method));
            }
        }

        return $paymentMethod;
    }

    #[ExposeInTemplate]
    public function gatewayIcon(): string
    {
        $paymentMethod = $this->paymentMethod();
        // Use gateway name if it exists, otherwise use the method name or factory name
        $name = $paymentMethod->getGatewayName() ?? $this->method ?? $paymentMethod->getFactoryName() ?? '';

        return match (true) {
            str_contains($name, 'stripe') => 'tabler:brand-stripe',
            str_contains($name, 'paypal') => 'tabler:brand-paypal',
            $name === 'cash' => 'tabler:cash',
            $name === 'bank_transfer' => 'tabler:building-bank',
            $name === 'offline' => 'tabler:wallet',
            $name === 'custom' => 'tabler:settings',
            default => 'tabler:credit-card',
        };
    }

    /**
     * @throws Exception
     */
    protected function instantiateForm(): FormInterface
    {
        $paymentMethod = $this->paymentMethod();
        $factory = $paymentMethod->getFactoryName();

        try {
            $config = $this->factories->getForm($factory);
        } catch (InvalidGatewayException) {
            throw $this->createNotFoundException(sprintf('Payment gateway form for "%s" not found', $factory));
        }

        return $this->createForm(
            PaymentMethodType::class,
            $paymentMethod,
            [
                'config' => $config,
                'internal' => $factory === 'offline',
            ]
        );
    }

    #[LiveAction]
    public function save(EntityManagerInterface $entityManager, RequestStack $requestStack): Response
    {
        $this->submitForm();

        $form = $this->getForm();

        // If form is not valid, redirect back to show validation errors
        if (! $form->isValid()) {
            $session = $requestStack->getSession();
            assert($session instanceof Session);

            $session->getFlashBag()->add(FlashResponse::FLASH_ERROR, 'Please correct the validation errors');

            return $this->redirectToRoute('_payment_settings_index', ['selectedGateway' => $this->method]);
        }

        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = $form->getData();

        // Only set gateway name for new payment methods
        if ($paymentMethod->getId() === null) {
            $gatewayName = (new AsciiSlugger())
                ->slug($paymentMethod->getName())
                ->lower()
                ->toString();

            // Validate gateway name to prevent XSS - only allow alphanumeric, hyphens, and underscores
            if (! preg_match('/^[a-z0-9_-]+$/', $gatewayName)) {
                $session = $requestStack->getSession();
                assert($session instanceof Session);

                $session->getFlashBag()->add(FlashResponse::FLASH_ERROR, 'Invalid gateway name format');

                return $this->redirectToRoute('_payment_settings_index', ['selectedGateway' => $this->method]);
            }

            $paymentMethod->setGatewayName($gatewayName);
        }

        $entityManager->persist($paymentMethod);
        $entityManager->flush();

        $session = $requestStack->getSession();
        assert($session instanceof Session);

        $session->getFlashBag()->add(FlashResponse::FLASH_SUCCESS, 'payment.method.updated');

        return $this->redirectToRoute('_payment_settings_index');
    }

    #[LiveAction]
    public function showDeleteConfirmation(): void
    {
        $this->showDeleteConfirmation = true;
    }

    #[LiveAction]
    public function cancelDelete(): void
    {
        $this->showDeleteConfirmation = false;
    }

    #[LiveAction]
    public function confirmDelete(RequestStack $requestStack): Response
    {
        $session = $requestStack->getSession();
        assert($session instanceof Session);

        $paymentMethod = $this->paymentMethod();

        // Check if the payment method exists in the database
        if ($paymentMethod->getId() === null) {
            $session->getFlashBag()->add(FlashResponse::FLASH_ERROR, 'Payment method does not exist');

            return $this->redirectToRoute('_payment_settings_index');
        }

        if (count($paymentMethod->getPayments()) > 0) {
            $session->getFlashBag()->add(FlashResponse::FLASH_ERROR, 'Unable to delete payment method as there are payments associated with it');

            return $this->redirectToRoute('_payment_settings_index');
        }

        $this->repository->delete($paymentMethod);

        $session->getFlashBag()->add(FlashResponse::FLASH_INFO, 'Payment method deleted');

        return $this->redirectToRoute('_payment_settings_index');
    }
}
