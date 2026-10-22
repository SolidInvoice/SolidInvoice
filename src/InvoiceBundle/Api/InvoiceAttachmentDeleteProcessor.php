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

namespace SolidInvoice\InvoiceBundle\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use SolidInvoice\CoreBundle\Storage\AttachmentStorageInterface;
use SolidInvoice\InvoiceBundle\Entity\InvoiceAttachment;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Deletes the attachment's blob once its database row is removed, so `Delete`
 * never leaves an orphaned file behind in {@see AttachmentStorageInterface}.
 * The default `RemoveProcessor` only removes the row.
 *
 * @implements ProcessorInterface<InvoiceAttachment, mixed>
 */
final readonly class InvoiceAttachmentDeleteProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<mixed, mixed> $decorated
     */
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.remove_processor')]
        private ProcessorInterface $decorated,
        private AttachmentStorageInterface $storage,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $storageKey = $data instanceof InvoiceAttachment ? $data->getStorageKey() : null;

        $result = $this->decorated->process($data, $operation, $uriVariables, $context);

        if ($storageKey !== null) {
            $this->storage->delete($storageKey);
        }

        return $result;
    }
}
