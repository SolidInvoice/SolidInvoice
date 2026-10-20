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

namespace SolidInvoice\PaymentBundle\Tests\Listener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Credit;
use SolidInvoice\ClientBundle\Exception\InsufficientCreditException;
use SolidInvoice\ClientBundle\Repository\CreditRepository;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\PaymentBundle\Event\PaymentCompleteEvent;
use SolidInvoice\PaymentBundle\Listener\PaymentCompleteListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\WorkflowInterface;

#[CoversClass(PaymentCompleteListener::class)]
final class PaymentCompleteListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testOnPaymentCompleteHandlesInsufficientCredit(): void
    {
        $client = Mockery::mock(Client::class);

        $paymentMethod = Mockery::mock(PaymentMethod::class);
        $paymentMethod->shouldReceive('getGatewayName')->andReturn('credit');

        $payment = Mockery::mock(Payment::class);
        $payment->shouldReceive('getMethod')->andReturn($paymentMethod);
        $payment->shouldReceive('getStatus')->andReturn(PaymentStatus::Captured);
        $payment->shouldReceive('getClient')->andReturn($client);
        $payment->shouldReceive('getTotalAmount')->andReturn(1000);
        $payment->shouldReceive('setStatus')->with(PaymentStatus::Failed)->once();
        $payment->shouldReceive('getInvoice')->andReturn(null);

        $creditRepository = Mockery::mock(CreditRepository::class);
        $creditRepository->shouldReceive('deductCredit')
            ->with($client, 1000)
            ->andThrow(InsufficientCreditException::class);

        $em = Mockery::mock(EntityManagerInterface::class);
        $em->shouldReceive('persist')->with($payment)->once();
        $em->shouldReceive('flush')->once();

        $registry = Mockery::mock(ManagerRegistry::class);
        $registry->shouldReceive('getRepository')->with(Credit::class)->andReturn($creditRepository);
        $registry->shouldReceive('getManager')->andReturn($em);

        $router = Mockery::mock(RouterInterface::class);
        $router->shouldReceive('generate')->with('_payments_index')->andReturn('/payments');

        $workflow = Mockery::mock(WorkflowInterface::class);

        $event = new PaymentCompleteEvent($payment);

        $listener = new PaymentCompleteListener($workflow, $registry, $router);
        $listener->onPaymentComplete($event);

        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertInstanceOf(FlashResponse::class, $response);

        self::assertInstanceOf(FlashResponse::class, $response);
        $flashes = iterator_to_array($response->getFlash());
        self::assertSame(
            'payment.create.exception.not_enough_credit',
            $flashes[FlashResponse::FLASH_DANGER]
        );
    }
}
