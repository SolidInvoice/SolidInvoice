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

use DateTime;
use Payum\Core\Model\Token;
use Payum\Core\Payum;
use SolidInvoice\CoreBundle\Traits\SaveableTrait;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\PaymentBundle\Event\PaymentCompleteEvent;
use SolidInvoice\PaymentBundle\Event\PaymentEvents;
use SolidInvoice\PaymentBundle\PaymentAction\Request\StatusRequest;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

final class Done
{
    use SaveableTrait;

    public function __construct(
        private readonly Payum $payum,
        private readonly RouterInterface $router,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AuthorizationCheckerInterface $authorization,
    ) {
    }

    public function __invoke(Request $request): Response | null
    {
        /** @var Token $token */
        $token = $this->payum->getHttpRequestVerifier()->verify($request);

        $paymentMethod = $this->payum->getGateway($token->getGatewayName());
        $paymentMethod->execute($status = new StatusRequest($token));

        /** @var Payment $payment */
        $payment = $status->getFirstModel();

        $payment->setStatus(PaymentStatus::from((string) $status->getValue()));
        $payment->setCompleted(new DateTime('now'));

        $this->save($payment);

        $event = new PaymentCompleteEvent($payment);
        $this->eventDispatcher->dispatch($event, PaymentEvents::PAYMENT_COMPLETE);

        if (($response = $event->getResponse()) instanceof Response) {
            return $response;
        }

        try {
            $isAuthenticated = $this->authorization->isGranted('IS_AUTHENTICATED_REMEMBERED');
        } catch (AuthenticationCredentialsNotFoundException) {
            $isAuthenticated = false;
        }

        if (! $isAuthenticated && ($invoice = $payment->getInvoice()) instanceof Invoice) {
            return new RedirectResponse(
                $this->router->generate('_view_invoice_external', ['uuid' => $invoice->getUuid()])
            );
        }

        return new RedirectResponse($this->router->generate('_payments_index'));
    }
}
