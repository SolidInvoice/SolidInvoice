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

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

/**
 * When no contacts are provided for a new Quote via the API, automatically
 * assign all contacts from the Quote's client. This matches the behaviour of
 * the UI (QuoteFormManager) and prevents the "users: You need to select at
 * least 1 user to attach to the Quote" validation error for API clients that
 * specify a client but omit the users field.
 *
 * @implements ProcessorInterface<object, object>
 */
#[AsDecorator(decorates: PersistProcessor::class)]
final readonly class QuoteAutoUsersStateProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<object, object> $inner
     */
    public function __construct(
        private ProcessorInterface $inner,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof Quote && $operation instanceof Post && $data->getUsers()->isEmpty() && $data->getClient() !== null) {
            foreach ($data->getClient()->getContacts() as $contact) {
                assert($contact instanceof Contact);
                $data->addUser($contact);
            }
        }

        return $this->inner->process($data, $operation, $uriVariables, $context);
    }
}
