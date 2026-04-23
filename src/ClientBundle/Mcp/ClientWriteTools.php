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

namespace SolidInvoice\ClientBundle\Mcp;

use Doctrine\ORM\EntityManagerInterface;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\McpBundle\Mcp\Attribute\McpScopeRequired;
use SolidInvoice\McpBundle\Mcp\McpScopeGuard;
use SolidInvoice\McpBundle\Mcp\Tool\EntityNormalizer;
use SolidInvoice\McpBundle\Mcp\Tool\UlidParser;
use SolidInvoice\McpBundle\Security\McpScope;

final class ClientWriteTools
{
    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly EntityNormalizer $normalizer,
        private readonly McpScopeGuard $scopeGuard,
    ) {
    }

    /**
     * Add a new contact to an existing client.
     *
     * @param string      $client_id  Client ULID
     * @param string|null $first_name Contact first name
     * @param string|null $last_name  Contact last name
     * @param string      $email      Contact email (required, must be valid)
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'add_contact', description: 'Add a new contact (email + optional name) to an existing client.')]
    #[McpScopeRequired(McpScope::Write)]
    public function addContact(string $client_id, string $email, ?string $first_name = null, ?string $last_name = null): array
    {
        $this->scopeGuard->require(McpScope::Write);

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new ToolCallException(sprintf('Invalid email address: %s.', $email));
        }

        $client = $this->clientRepository->find(UlidParser::parse($client_id, 'client_id'));

        if (! $client instanceof Client) {
            throw new ToolCallException(sprintf('Client %s not found.', $client_id));
        }

        $contact = new Contact();
        $contact->setEmail($email);
        $contact->setFirstName($first_name);
        $contact->setLastName($last_name);
        $contact->setClient($client);
        $contact->setCompany($client->getCompany());

        $client->addContact($contact);

        $this->entityManager->persist($contact);
        $this->entityManager->flush();

        return $this->normalizer->normalize($contact);
    }
}
