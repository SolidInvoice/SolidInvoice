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

namespace SolidInvoice\PaymentBundle\Listener\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Entity\SecurityToken;

/**
 * @see \SolidInvoice\PaymentBundle\Tests\Listener\Doctrine\PaymentSecurityTokenRemoverTest
 */
#[AsEntityListener(event: Events::preRemove, entity: Payment::class)]
final class PaymentSecurityTokenRemover
{
    public function preRemove(Payment $payment, PreRemoveEventArgs $args): void
    {
        $em = $args->getObjectManager();

        foreach ($em->getRepository(SecurityToken::class)->findBy(['payment' => $payment]) as $token) {
            $em->remove($token);
        }
    }
}
