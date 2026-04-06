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

namespace SolidInvoice\ClientBundle\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProcessorInterface<Contact, Contact> */
final class ContactPersistProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<mixed, mixed> $decorated
     */
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $decorated,
        private readonly ClientRepository $clientRepository,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof Contact && isset($uriVariables['clientId'])) {
            // findOneBy (not find) ensures the global CompanyFilter is applied, preventing cross-company access.
            $client = $this->clientRepository->findOneBy(['id' => $uriVariables['clientId']]);
            if (! $client instanceof Client) {
                throw new NotFoundHttpException(sprintf('Client "%s" not found.', $uriVariables['clientId']));
            }
            $data->setClient($client);
        }

        return $this->decorated->process($data, $operation, $uriVariables, $context);
    }
}
