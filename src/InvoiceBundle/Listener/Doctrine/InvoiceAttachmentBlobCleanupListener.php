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
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use SolidInvoice\CoreBundle\Storage\AttachmentStorageInterface;
use SolidInvoice\InvoiceBundle\Entity\InvoiceAttachment;

/**
 * Deletes an attachment's blob once its row has actually been removed.
 * This covers every removal path, not only the API `Delete` operation:
 * deleting an `Invoice` cascades onto its attachments, and
 * `Invoice::removeAttachment()` relies on `orphanRemoval`. Both end up as a
 * scheduled entity deletion on the same flush, which this listener collects.
 *
 * The blob is deleted in `postFlush`, after the removal has committed, so a
 * failed flush never deletes a file whose row still exists.
 *
 * @see \SolidInvoice\InvoiceBundle\Tests\Listener\Doctrine\InvoiceAttachmentBlobCleanupListenerTest
 */
#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
final class InvoiceAttachmentBlobCleanupListener
{
    /**
     * @var list<string>
     */
    private array $storageKeysToDelete = [];

    public function __construct(
        private readonly AttachmentStorageInterface $storage,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $unitOfWork = $args->getObjectManager()->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof InvoiceAttachment) {
                $this->storageKeysToDelete[] = $entity->getStorageKey();
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        foreach ($this->storageKeysToDelete as $storageKey) {
            $this->storage->delete($storageKey);
        }

        $this->storageKeysToDelete = [];
    }
}
