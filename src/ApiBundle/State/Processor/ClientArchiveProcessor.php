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

namespace SolidInvoice\ApiBundle\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Archives or restores a client through the same repository calls as the client grid's
 * batch actions. Both are idempotent, as they are there.
 *
 * @implements ProcessorInterface<null, Client>
 */
final readonly class ClientArchiveProcessor implements ProcessorInterface
{
    public const string ARCHIVE = 'client_archive';

    public const string RESTORE = 'client_restore';

    public function __construct(
        private ClientRepository $repository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Client
    {
        $id = (string) $uriVariables['id'];

        // An archived client is hidden by the archivable filter, and restore must still find it.
        // The company filter stays on, so another company's client is a 404. findOneBy() rather
        // than find(), which skips the filters on an identity-map hit.
        $filters = $this->entityManager->getFilters();
        $filters->suspend('archivable');

        try {
            $client = $this->repository->findOneBy(['id' => $id]);
        } finally {
            $filters->restore('archivable');
        }

        if (! $client instanceof Client) {
            throw new NotFoundHttpException();
        }

        if ($operation->getName() === self::RESTORE) {
            $this->repository->restoreClients([$id]);
        } else {
            $this->repository->archiveClients([$id]);
        }

        return $client;
    }
}
