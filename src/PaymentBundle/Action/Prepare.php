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

namespace SolidInvoice\PaymentBundle\Action;

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
use Money\Currency;
use Money\Money;
use Payum\Core\Payum;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Workflow\StateMachine;

// @TODO: Refactor this class to make it cleaner

final class Prepare
{
    use SaveableTrait;

    /**
     * @var StateMachine
     */
    private $stateMachine;

    /**
     * @var PaymentMethodRepository
     */
    private $paymentMethodRepository;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorization;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var Currency
     */
    private $currency;

    /**
     * @var PaymentFactories
     */
    private $paymentFactories;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var Payum
     */
    private $payum;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(
        StateMachine $stateMachine,
        PaymentMethodRepository $paymentMethodRepository,
        AuthorizationCheckerInterface $authorization,
        TokenStorageInterface $tokenStorage,
        FormFactoryInterface $formFactory,
        Currency $currency,
        PaymentFactories $paymentFactories,
        EventDispatcherInterface $eventDispatcher,
        Payum $payum,
        RouterInterface $router
    ) {
        $this->stateMachine = $stateMachine;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->authorization = $authorization;
        $this->tokenStorage = $tokenStorage;
        $this->formFactory = $formFactory;
        $this->currency = $currency;
        $this->paymentFactories = $paymentFactories;
        $this->eventDispatcher = $eventDispatcher;
        $this->payum = $payum;
        $this->router = $router;
    }

    public function __invoke(Request $request, ?Invoice $invoice)
    {
        if (null === $invoice) {
            throw new NotFoundHttpException();
        }

        if (!$this->stateMachine->can($invoice, Graph::TRANSITION_PAY)) {
            throw new \Exception('This invoice cannot be paid');
        }

        if ($this->paymentMethodRepository->getTotalMethodsConfigured($this->authorization->isGranted('ROLE_SUPER_ADMIN')) < 1) {
            throw new \Exception('No payment methods available');
        }

        $preferredChoices = $this->paymentMethodRepository->findBy(['gatewayName' => 'credit']);

        $currency = $invoice->getClient()->getCurrency();
        $form = $this->formFactory->create(
            PaymentType::class,
            [
                'amount' => $invoice->getBalance(),
            ],
            [
                'user' => $this->getUser(),
                'currency' => $currency ? $currency->getCode() : $this->currency->getCode(),
                'preferred_choices' => $preferredChoices,
            ]
        );

        $form->handleRequest($request);

        $paymentFactories = array_keys($this->paymentFactories->getFactories('offline'));

        if ($form->isValid()) {
            $data = $form->getData();
            /** @var Money $amount */
            $amount = $data['amount'];

            /** @var PaymentMethod $paymentMethod */
            $paymentMethod = $data['payment_method'];

            $paymentName = $paymentMethod->getGatewayName();

            if (in_array($paymentName, $paymentFactories, true)) {
                if ('credit' === $paymentName) {
                    $clientCredit = $invoice->getClient()->getCredit()->getValue();

                    $invalid = '';
                    if ($amount->greaterThan($clientCredit)) {
                        $invalid = 'payment.create.exception.not_enough_credit';
                    } elseif ($amount->greaterThan($invoice->getBalance())) {
                        $invalid = 'payment.create.exception.amount_exceeds_balance';
                    }

                    if (!empty($invalid)) {
                        $request->getSession()->getFlashbag()->add(FlashResponse::FLASH_DANGER, $invalid);

                        return new Template(
                            '@SolidInvoicePayment/Payment/create.html.twig',
                            [
                                'form' => $form->createView(),
                                'invoice' => $invoice,
                                'internal' => array_keys($paymentFactories),
                            ]
                        );
                    }
                }

                $data['capture_online'] = true;
            }

            $payment = new Payment();
            $payment->setInvoice($invoice);
            $payment->setStatus(Status::STATUS_NEW);
            $payment->setMethod($data['payment_method']);
            /** @var \Money\Money $money */
            $money = $data['amount'];
            $payment->setTotalAmount($money->getAmount());
            $payment->setCurrencyCode($money->getCurrency()->getCode());
            $payment->setDescription('');
            $payment->setClient($invoice->getClient());
            $payment->setNumber($invoice->getId());
            $payment->setClientEmail($invoice->getClient()->getContacts()->first()->getEmail());
            $invoice->addPayment($payment);
            $this->save($payment);

            if (array_key_exists('capture_online', $data) && true === $data['capture_online']) {
                $captureToken = $this->payum
                    ->getTokenFactory()
                    ->createCaptureToken(
                        $paymentName,
                        $payment,
                        '_payments_done' // the route to redirect after capture;
                    );

                return new RedirectResponse($captureToken->getTargetUrl());
            } else {
                $payment->setStatus(Status::STATUS_CAPTURED);
                $payment->setCompleted(new \DateTime('now'));
                $this->save($payment);

                $event = new PaymentCompleteEvent($payment);
                $this->eventDispatcher->dispatch(PaymentEvents::PAYMENT_COMPLETE, $event);

                if ($response = $event->getResponse()) {
                    return $response;
                }

                return new RedirectResponse($this->router->generate('_payments_index'));
            }
        }

        return new Template(
            '@SolidInvoicePayment/Payment/create.html.twig',
            [
                'form' => $form->createView(),
                'invoice' => $invoice,
                'internal' => $paymentFactories,
            ]
        );
    }

    protected function getUser(): ?UserInterface
    {
        if (null === $token = $this->tokenStorage->getToken()) {
            return null;
        }

        if (!is_object($user = $token->getUser())) {
            return null;
        }

        return $user;
    }
}
