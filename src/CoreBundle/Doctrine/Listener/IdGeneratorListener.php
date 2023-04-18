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

namespace SolidInvoice\CoreBundle\Doctrine\Listener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator\AutoIncrementIdGenerator;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\QuoteBundle\Entity\Quote;

/**
 * @see \SolidInvoice\CoreBundle\Tests\Doctrine\Listener\IdGeneratorListenerTest
 */
final class IdGeneratorListener implements EventSubscriber
{
    private ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @return list<string>
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
        ];
    }

    public function prePersist(PrePersistEventArgs $eventArgs): void
    {
        $object = $eventArgs->getObject();

        if ($object instanceof Invoice) {
            $generator = new AutoIncrementIdGenerator($this->registry);
            $object->setInvoiceId($generator->generate($object, 'invoiceId'));
        }

        if ($object instanceof Quote) {
            $generator = new AutoIncrementIdGenerator($this->registry);
            $object->setQuoteId($generator->generate($object, 'quoteId'));
        }
    }
}
