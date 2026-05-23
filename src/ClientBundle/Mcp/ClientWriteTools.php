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
use SolidInvoice\TaxBundle\Entity\TaxIdentifier;
use SolidInvoice\TaxBundle\Repository\TaxIdentifierRepository;

final readonly class ClientWriteTools
{
    public function __construct(
        private ClientRepository $clientRepository,
        private TaxIdentifierRepository $taxIdentifierRepository,
        private EntityManagerInterface $entityManager,
        private EntityNormalizer $normalizer,
        private McpScopeGuard $scopeGuard,
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

    /**
     * Add a tax identifier (e.g. VAT, GSTIN, TIN, ABN, CNPJ, TRN) to a client.
     *
     * @param string $client_id Client ULID
     * @param string $label     Identifier label (e.g. "VAT", "GSTIN", "TIN", "ABN", "CNPJ", "TRN", "Other")
     * @param string $value     Identifier value
     * @param bool   $primary   Whether this is the primary identifier for the client
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'add_client_tax_identifier', description: 'Add a tax identifier (VAT, GSTIN, TIN, ABN, CNPJ, TRN, Other) to a client.')]
    #[McpScopeRequired(McpScope::Write)]
    public function addTaxIdentifier(string $client_id, string $label, string $value, bool $primary = false): array
    {
        $this->scopeGuard->require(McpScope::Write);

        $label = trim($label);
        $value = trim($value);

        if ($label === '') {
            throw new ToolCallException('Tax identifier label cannot be empty.');
        }

        if ($value === '') {
            throw new ToolCallException('Tax identifier value cannot be empty.');
        }

        $client = $this->clientRepository->find(UlidParser::parse($client_id, 'client_id'));

        if (! $client instanceof Client) {
            throw new ToolCallException(sprintf('Client %s not found.', $client_id));
        }

        $identifier = new TaxIdentifier();
        $identifier->setLabel($label);
        $identifier->setValue($value);
        $identifier->setPrimary($primary);
        $identifier->setCompany($client->getCompany());

        $client->addTaxIdentifier($identifier);

        $this->entityManager->persist($identifier);
        $this->entityManager->flush();

        return $this->normalizer->normalize($identifier);
    }

    /**
     * Remove a tax identifier from a client.
     *
     * @param string $tax_identifier_id Tax identifier ULID
     *
     * @return array{removed: bool}
     */
    #[McpTool(name: 'remove_client_tax_identifier', description: 'Remove a tax identifier from a client by identifier ULID.')]
    #[McpScopeRequired(McpScope::Write)]
    public function removeTaxIdentifier(string $tax_identifier_id): array
    {
        $this->scopeGuard->require(McpScope::Write);

        $identifier = $this->taxIdentifierRepository->find(UlidParser::parse($tax_identifier_id, 'tax_identifier_id'));

        if (! $identifier instanceof TaxIdentifier) {
            throw new ToolCallException(sprintf('Tax identifier %s not found.', $tax_identifier_id));
        }

        $this->entityManager->remove($identifier);
        $this->entityManager->flush();

        return ['removed' => true];
    }
}
