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

namespace SolidInvoice\PaymentBundle\Action;

use const FILTER_VALIDATE_BOOLEAN;
use Brick\Math\BigInteger;
use DateTime;
use Exception;
use Money\Money;
use Payum\Core\Payum;
use Payum\Core\Registry\RegistryInterface;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\CoreBundle\Traits\SaveableTrait;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\PaymentBundle\Event\PaymentCompleteEvent;
use SolidInvoice\PaymentBundle\Event\PaymentEvents;
use SolidInvoice\PaymentBundle\Factory\PaymentFactories;
use SolidInvoice\PaymentBundle\Form\Type\PaymentType;
use SolidInvoice\PaymentBundle\Model\Status;
use SolidInvoice\PaymentBundle\Repository\PaymentMethodRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Workflow\StateMachine;
use function array_key_exists;
use function filter_var;

// @TODO: Refactor this class to make it cleaner

final class Prepare
{
    use SaveableTrait;

    /**
     * @param Payum $payum
     */
    public function __construct(
        private readonly StateMachine $invoiceStateMachine,
        private readonly PaymentMethodRepository $paymentMethodRepository,
        private readonly AuthorizationCheckerInterface $authorization,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly FormFactoryInterface $formFactory,
        private readonly PaymentFactories $paymentFactories,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly RegistryInterface $payum,
        private readonly RouterInterface $router,
        private readonly CompanySelector $companySelector
    ) {
    }

    /**
     * @throws Exception
     */
    public function __invoke(Request $request, ?Invoice $invoice): Template | Response | null
    {
        if (! $invoice instanceof Invoice) {
            throw new NotFoundHttpException();
        }

        if (! $this->invoiceStateMachine->can($invoice, Graph::TRANSITION_PAY)) {
            throw new Exception('This invoice cannot be paid');
        }

        $this->companySelector->switchCompany($invoice->getCompany()->getId());

        if ($this->paymentMethodRepository->getTotalMethodsConfigured($this->authorization->isGranted('IS_AUTHENTICATED_REMEMBERED')) < 1) {
            throw new Exception('No payment methods available');
        }

        $preferredChoices = $this->paymentMethodRepository->findBy(['gatewayName' => 'credit']);

        $form = $this->formFactory->create(
            PaymentType::class,
            [
                'amount' => $invoice->getBalance(),
            ],
            [
                'user' => $this->getUser(),
                'currency' => $invoice->getClient()->getCurrency(),
                'preferred_choices' => $preferredChoices,
            ]
        );

        $form->handleRequest($request);

        $offlinePaymentFactories = $this->paymentFactories->getFactories('offline');

        if ($form->isSubmitted() && $form->isValid()) {
            $paymentFactories = $this->paymentFactories->getFactories($form->getData()['payment_method']->getFactoryName());
            $data = $form->getData();
            $amount = BigInteger::of($data['amount']);

            /** @var PaymentMethod $paymentMethod */
            $paymentMethod = $data['payment_method'];

            $paymentName = $paymentMethod->getGatewayName();

            if (! array_key_exists($paymentName, $paymentFactories)) {
                throw new Exception('Invalid payment method');
            }

            if ('credit' === $paymentName) {
                $clientCredit = $invoice->getClient()->getCredit()->getValue();

                $invalid = '';
                if ($amount->isGreaterThan($clientCredit)) {
                    $invalid = 'payment.create.exception.not_enough_credit';
                } elseif ($amount->isGreaterThan($invoice->getBalance())) {
                    $invalid = 'payment.create.exception.amount_exceeds_balance';
                }

                if (! empty($invalid)) {
                    $session = $request->getSession();

                    if ($session instanceof Session) {
                        $session->getFlashbag()->add(FlashResponse::FLASH_DANGER, $invalid);
                    }

                    return new Template(
                        '@SolidInvoicePayment/Payment/create.html.twig',
                        [
                            'form' => $form->createView(),
                            'invoice' => $invoice,
                            'internal' => array_keys($offlinePaymentFactories),
                        ]
                    );
                }
            }

            $data['capture_online'] ??= ! array_key_exists($paymentName, $offlinePaymentFactories);

            $payment = new Payment();
            $payment->setInvoice($invoice);
            $payment->setStatus(Status::STATUS_NEW);
            $payment->setMethod($data['payment_method']);
            /** @var Money $money */
            $money = $data['amount'];
            $payment->setTotalAmount($money->getAmount());
            $payment->setCurrencyCode($money->getCurrency()->getCode());
            $payment->setDescription('');
            $payment->setClient($invoice->getClient());
            $payment->setNumber($invoice->getId()->toString());
            $payment->setClientEmail($invoice->getClient()->getContacts()->first()->getEmail());
            $invoice->addPayment($payment);
            $this->save($payment);

            if (filter_var($data['capture_online'], FILTER_VALIDATE_BOOLEAN)) {
                $captureToken = $this->payum
                    ->getTokenFactory()
                    ->createCaptureToken(
                        $paymentName,
                        $payment,
                        '_payments_done' // the route to redirect after capture;
                    );

                return new RedirectResponse($captureToken->getTargetUrl());
            }

            $payment->setStatus(Status::STATUS_CAPTURED);
            $payment->setCompleted(new DateTime('now'));
            $this->save($payment);

            $event = new PaymentCompleteEvent($payment);
            $this->eventDispatcher->dispatch($event, PaymentEvents::PAYMENT_COMPLETE);

            if (($response = $event->getResponse()) instanceof Response) {
                return $response;
            }

            return new RedirectResponse($this->router->generate('_payments_index'));
        }

        return new Template(
            '@SolidInvoicePayment/Payment/create.html.twig',
            [
                'form' => $form->createView(),
                'invoice' => $invoice,
                'internal' => \array_keys($offlinePaymentFactories),
            ]
        );
    }

    protected function getUser(): ?UserInterface
    {
        if (! ($token = $this->tokenStorage->getToken()) instanceof TokenInterface) {
            return null;
        }

        if (! is_object($user = $token->getUser())) {
            return null;
        }

        return $user;
    }
}
