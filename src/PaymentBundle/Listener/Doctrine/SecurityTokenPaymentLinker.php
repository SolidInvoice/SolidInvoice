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
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Payum\Core\Model\Identity;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Entity\SecurityToken;
use Symfony\Component\Uid\Ulid;
use Throwable;

/**
 * @see \SolidInvoice\PaymentBundle\Tests\Listener\Doctrine\SecurityTokenPaymentLinkerTest
 */
#[AsEntityListener(event: Events::prePersist, entity: SecurityToken::class)]
#[AsEntityListener(event: Events::preUpdate, entity: SecurityToken::class)]
final class SecurityTokenPaymentLinker
{
    public function prePersist(SecurityToken $token, PrePersistEventArgs $args): void
    {
        $this->link($token, $args);
    }

    public function preUpdate(SecurityToken $token, PreUpdateEventArgs $args): void
    {
        $this->link($token, $args);
    }

    private function link(SecurityToken $token, PrePersistEventArgs|PreUpdateEventArgs $args): void
    {
        $details = $token->getDetails();

        if (! $details instanceof Identity || $details->getClass() !== Payment::class) {
            return;
        }

        $id = $details->getId();

        if (! $id instanceof Ulid) {
            try {
                $id = Ulid::fromString((string) $id);
            } catch (Throwable) {
                return;
            }
        }

        $em = $args->getObjectManager();
        $payment = $em->getRepository(Payment::class)->find($id);
        $token->setPayment($payment);

        if ($args instanceof PreUpdateEventArgs) {
            $em->getUnitOfWork()->recomputeSingleEntityChangeSet(
                $em->getClassMetadata(SecurityToken::class),
                $token,
            );
        }
    }
}
