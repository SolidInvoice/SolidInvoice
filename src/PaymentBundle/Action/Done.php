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

use Payum\Core\Model\Token;
use Payum\Core\Payum;
use Payum\Core\Registry\RegistryInterface;
use SolidInvoice\CoreBundle\Traits\SaveableTrait;
use SolidInvoice\PaymentBundle\Event\PaymentCompleteEvent;
use SolidInvoice\PaymentBundle\Event\PaymentEvents;
use SolidInvoice\PaymentBundle\PaymentAction\Request\StatusRequest;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

final class Done
{
    use SaveableTrait;

    /**
     * @var Payum
     */
    private $payum;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(RegistryInterface $payum, RouterInterface $router, EventDispatcherInterface $eventDispatcher)
    {
        $this->payum = $payum;
        $this->router = $router;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(Request $request)
    {
        /** @var Token $token */
        $token = $this->payum->getHttpRequestVerifier()->verify($request);

        $paymentMethod = $this->payum->getGateway($token->getGatewayName());
        $paymentMethod->execute($status = new StatusRequest($token));

        /** @var \SolidInvoice\PaymentBundle\Entity\Payment $payment */
        $payment = $status->getFirstModel();

        $payment->setStatus((string) $status->getValue());
        $payment->setCompleted(new \DateTime('now'));

        $this->save($payment);

        $event = new PaymentCompleteEvent($payment);
        $this->eventDispatcher->dispatch($event, PaymentEvents::PAYMENT_COMPLETE);

        if (null !== ($response = $event->getResponse())) {
            return $response;
        }

        return new RedirectResponse($this->router->generate('_payments_index'));
    }
}
