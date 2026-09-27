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

namespace SolidInvoice\InvoiceBundle\Listener\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use LogicException;
use SolidInvoice\InvoiceBundle\Entity\BaseInvoice;
use function sprintf;

/**
 * Rejects a write to {@see BaseInvoice::getCurrencyCode()} once it already holds a
 * value. {@see \SolidInvoice\InvoiceBundle\Listener\FreezeInvoiceCurrencyListener} is the
 * only writer that runs while the column is still null; once it has stamped a currency,
 * this guard makes the column immutable regardless of which code path attempts the change.
 *
 * @see \SolidInvoice\InvoiceBundle\Tests\Listener\Doctrine\InvoiceCurrencyGuardListenerTest
 */
#[AsDoctrineListener(Events::preUpdate)]
final readonly class InvoiceCurrencyGuardListener
{
    public function preUpdate(PreUpdateEventArgs $event): void
    {
        $entity = $event->getObject();

        if (! $entity instanceof BaseInvoice) {
            return;
        }

        if (! $event->hasChangedField('currencyCode')) {
            return;
        }

        if ($event->getOldValue('currencyCode') !== null) {
            throw new LogicException(sprintf('%s#currencyCode is frozen once set and cannot be changed.', $entity::class));
        }
    }
}
