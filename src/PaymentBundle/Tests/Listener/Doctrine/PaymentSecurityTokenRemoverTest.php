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

namespace SolidInvoice\PaymentBundle\Tests\Listener\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Entity\SecurityToken;
use SolidInvoice\PaymentBundle\Listener\Doctrine\PaymentSecurityTokenRemover;

#[CoversClass(PaymentSecurityTokenRemover::class)]
final class PaymentSecurityTokenRemoverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testPreRemoveDeletesAssociatedTokens(): void
    {
        $payment = new Payment();
        $token1 = new SecurityToken();
        $token2 = new SecurityToken();

        $em = Mockery::mock(EntityManagerInterface::class);
        $repo = Mockery::mock();
        $em->shouldReceive('getRepository')->with(SecurityToken::class)->andReturn($repo);
        $repo->shouldReceive('findBy')->once()->with(['payment' => $payment])->andReturn([$token1, $token2]);
        $em->shouldReceive('remove')->once()->with($token1);
        $em->shouldReceive('remove')->once()->with($token2);

        $args = new PreRemoveEventArgs($payment, $em);

        $listener = new PaymentSecurityTokenRemover();
        $listener->preRemove($payment, $args);
    }

    public function testPreRemoveDoesNothingWhenNoTokensFound(): void
    {
        $payment = new Payment();

        $em = Mockery::mock(EntityManagerInterface::class);
        $repo = Mockery::mock();
        $em->shouldReceive('getRepository')->with(SecurityToken::class)->andReturn($repo);
        $repo->shouldReceive('findBy')->once()->with(['payment' => $payment])->andReturn([]);
        $em->shouldNotReceive('remove');

        $args = new PreRemoveEventArgs($payment, $em);

        $listener = new PaymentSecurityTokenRemover();
        $listener->preRemove($payment, $args);
    }
}
