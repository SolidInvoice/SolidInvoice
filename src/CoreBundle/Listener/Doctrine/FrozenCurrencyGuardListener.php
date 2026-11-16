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

namespace SolidInvoice\CoreBundle\Listener\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use LogicException;
use SolidInvoice\CoreBundle\Contracts\HasFrozenCurrencyInterface;
use SolidInvoice\CoreBundle\Currency\FrozenCurrencyThaw;
use function sprintf;

/**
 * Rejects a write to {@see HasFrozenCurrencyInterface::getCurrencyCode()} once it
 * already holds a value. {@see \SolidInvoice\CoreBundle\Listener\FreezeCurrencyListener}
 * is the only writer that runs while the column is still null; once it has stamped a
 * currency, this guard makes the column immutable, except a `non-null → NULL` write
 * that {@see FrozenCurrencyThaw} has sanctioned — the one change that listener makes
 * when a document returns to Draft. Every other write, including a `non-null → different
 * non-null` write made while a sanction happens to be present, is still rejected.
 *
 * @see \SolidInvoice\CoreBundle\Tests\Listener\Doctrine\FrozenCurrencyGuardListenerTest
 */
#[AsDoctrineListener(Events::preUpdate)]
final readonly class FrozenCurrencyGuardListener
{
    public function __construct(
        private FrozenCurrencyThaw $thaw,
    ) {
    }

    public function preUpdate(PreUpdateEventArgs $event): void
    {
        $entity = $event->getObject();

        if (! $entity instanceof HasFrozenCurrencyInterface) {
            return;
        }

        if (! $event->hasChangedField('currencyCode')) {
            return;
        }

        $old = $event->getOldValue('currencyCode');

        if ($old === null) {
            return;
        }

        if ($event->getNewValue('currencyCode') === null) {
            if ($this->thaw->consume($entity)) {
                return;
            }

            throw new LogicException(sprintf('%s#currencyCode can only be cleared by a return to Draft on a document with no payments.', $entity::class));
        }

        throw new LogicException(sprintf('%s#currencyCode is frozen once set and cannot be changed.', $entity::class));
    }
}
