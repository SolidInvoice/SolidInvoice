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
use Mcp\Schema\ToolAnnotations;
use Money\Currency;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Enum\ClientStatus;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\ClientBundle\Repository\ContactRepository;
use SolidInvoice\McpBundle\Mcp\Attribute\McpScopeRequired;
use SolidInvoice\McpBundle\Mcp\McpScopeGuard;
use SolidInvoice\McpBundle\Mcp\Output\DeletedOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\ClientOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\ContactOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\TaxIdentifierOutput;
use SolidInvoice\McpBundle\Mcp\Output\McpOutput;
use SolidInvoice\McpBundle\Mcp\Tool\EntityNormalizer;
use SolidInvoice\McpBundle\Mcp\Tool\UlidParser;
use SolidInvoice\McpBundle\Security\McpScope;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\TaxBundle\Entity\TaxIdentifier;
use SolidInvoice\TaxBundle\Repository\TaxIdentifierRepository;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class ClientWriteTools
{
    public function __construct(
        private ClientRepository $clientRepository,
        private ContactRepository $contactRepository,
        private TaxIdentifierRepository $taxIdentifierRepository,
        private EntityManagerInterface $entityManager,
        private EntityNormalizer $normalizer,
        private McpScopeGuard $scopeGuard,
        private ValidatorInterface $validator,
        private SystemConfig $systemConfig,
    ) {
    }

    /**
     * Create a new client in the current company.
     *
     * @param string      $name          Client name
     * @param string|null $currency_code ISO 4217 currency the client is billed in (defaults to the company's)
     * @param string|null $website       Website URL (optional)
     * @param string|null $status        One of: active, inactive, archived (default active)
     */
    #[McpTool(name: 'create_client', title: 'Create client', description: 'Create a new client (name, currency, website, status).', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: false))]
    #[McpScopeRequired(McpScope::Write)]
    #[McpOutput(ClientOutput::class)]
    public function createClient(string $name, ?string $currency_code = null, ?string $website = null, ?string $status = null): ClientOutput
    {
        $this->scopeGuard->require(McpScope::Write);

        $client = new Client();
        $client->setName($name);
        // A client without a currency follows the system default, as ClientListener::postLoad() resolves it.
        $client->setCurrency($this->systemConfig->getCurrency());
        $this->applyClientFields($client, $currency_code, $website, $status);
        $this->validate($client);

        $this->entityManager->persist($client);
        $this->entityManager->flush();

        return $this->normalizer->client($client);
    }

    /**
     * Update an existing client. Pass only the fields you want to change.
     *
     * @param string      $id            Client ULID
     * @param string|null $name          New name (optional)
     * @param string|null $currency_code New ISO 4217 currency (optional)
     * @param string|null $website       New website URL (optional)
     * @param string|null $status        New status: active, inactive, archived (optional)
     */
    #[McpTool(name: 'update_client', title: 'Update client', description: 'Update an existing client. Pass only the fields you want to change.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: true, openWorldHint: false))]
    #[McpScopeRequired(McpScope::Write)]
    #[McpOutput(ClientOutput::class)]
    public function updateClient(string $id, ?string $name = null, ?string $currency_code = null, ?string $website = null, ?string $status = null): ClientOutput
    {
        $this->scopeGuard->require(McpScope::Write);

        $client = $this->findClient($id, 'id');

        if ($name !== null) {
            $client->setName($name);
        }

        $this->applyClientFields($client, $currency_code, $website, $status);
        $this->validate($client);

        $this->entityManager->flush();

        return $this->normalizer->client($client);
    }

    /**
     * @param string $id Client ULID
     */
    #[McpTool(name: 'delete_client', title: 'Delete client', description: 'Delete a client belonging to the current company.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: true, openWorldHint: false))]
    #[McpScopeRequired(McpScope::Write)]
    #[McpOutput(DeletedOutput::class)]
    public function deleteClient(string $id): DeletedOutput
    {
        $this->scopeGuard->require(McpScope::Write);

        $this->entityManager->remove($this->findClient($id, 'id'));
        $this->entityManager->flush();

        return new DeletedOutput(deleted: true, id: $id);
    }

    /**
     * Add a new contact to an existing client.
     *
     * @param string      $client_id  Client ULID
     * @param string      $email      Contact email (must be valid)
     * @param string      $first_name Contact first name
     * @param string|null $last_name  Contact last name (optional)
     */
    #[McpTool(name: 'create_contact', title: 'Create contact', description: 'Add a new contact (email and name) to an existing client.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: false))]
    #[McpScopeRequired(McpScope::Write)]
    #[McpOutput(ContactOutput::class)]
    public function createContact(string $client_id, string $email, string $first_name, ?string $last_name = null): ContactOutput
    {
        $this->scopeGuard->require(McpScope::Write);

        $client = $this->findClient($client_id, 'client_id');

        $contact = new Contact();
        $contact->setEmail($email);
        $contact->setFirstName($first_name);
        $contact->setLastName($last_name);
        $contact->setClient($client);
        $contact->setCompany($client->getCompany());

        $client->addContact($contact);

        $this->validate($contact);

        $this->entityManager->persist($contact);
        $this->entityManager->flush();

        return $this->normalizer->contact($contact);
    }

    /**
     * Update an existing contact. Pass only the fields you want to change.
     *
     * @param string      $id         Contact ULID
     * @param string|null $first_name New first name (optional)
     * @param string|null $last_name  New last name (optional)
     * @param string|null $email      New email (optional)
     */
    #[McpTool(name: 'update_contact', title: 'Update contact', description: 'Update an existing client contact. Pass only the fields you want to change.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: true, openWorldHint: false))]
    #[McpScopeRequired(McpScope::Write)]
    #[McpOutput(ContactOutput::class)]
    public function updateContact(string $id, ?string $first_name = null, ?string $last_name = null, ?string $email = null): ContactOutput
    {
        $this->scopeGuard->require(McpScope::Write);

        $contact = $this->findContact($id);

        if ($first_name !== null) {
            $contact->setFirstName($first_name);
        }

        if ($last_name !== null) {
            $contact->setLastName($last_name);
        }

        if ($email !== null) {
            $contact->setEmail($email);
        }

        $this->validate($contact);

        $this->entityManager->flush();

        return $this->normalizer->contact($contact);
    }

    /**
     * Delete a client contact.
     *
     * @param string $id Contact ULID
     */
    #[McpTool(name: 'delete_contact', title: 'Delete contact', description: 'Delete a client contact belonging to the current company.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: true, openWorldHint: false))]
    #[McpScopeRequired(McpScope::Write)]
    #[McpOutput(DeletedOutput::class)]
    public function deleteContact(string $id): DeletedOutput
    {
        $this->scopeGuard->require(McpScope::Write);

        $contact = $this->findContact($id);
        $contact->getClient()?->removeContact($contact);

        $this->entityManager->remove($contact);
        $this->entityManager->flush();

        return new DeletedOutput(deleted: true, id: $id);
    }

    /**
     * Add a tax identifier (e.g. VAT, GSTIN, TIN, ABN, CNPJ, TRN) to a client.
     *
     * @param string $client_id Client ULID
     * @param string $label     Identifier label (e.g. "VAT", "GSTIN", "TIN", "ABN", "CNPJ", "TRN", "Other")
     * @param string $value     Identifier value
     * @param bool   $primary   Whether this is the primary identifier for the client
     */
    #[McpTool(name: 'add_client_tax_identifier', title: 'Add client tax identifier', description: 'Add a tax identifier (VAT, GSTIN, TIN, ABN, CNPJ, TRN, Other) to a client.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: false))]
    #[McpScopeRequired(McpScope::Write)]
    #[McpOutput(TaxIdentifierOutput::class)]
    public function addTaxIdentifier(string $client_id, string $label, string $value, bool $primary = false): TaxIdentifierOutput
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

        return $this->normalizer->taxIdentifier($identifier);
    }

    /**
     * Remove a tax identifier from a client.
     *
     * @param string $tax_identifier_id Tax identifier ULID
     */
    #[McpTool(name: 'remove_client_tax_identifier', title: 'Remove client tax identifier', description: 'Remove a tax identifier from a client by identifier ULID.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: true, idempotentHint: true, openWorldHint: false))]
    #[McpScopeRequired(McpScope::Write)]
    #[McpOutput(DeletedOutput::class)]
    public function removeTaxIdentifier(string $tax_identifier_id): DeletedOutput
    {
        $this->scopeGuard->require(McpScope::Write);

        $ulid = UlidParser::parse($tax_identifier_id, 'tax_identifier_id');
        $identifier = $this->taxIdentifierRepository->find($ulid);

        if (! $identifier instanceof TaxIdentifier) {
            throw new ToolCallException(sprintf('Tax identifier %s not found.', $tax_identifier_id));
        }

        $this->entityManager->remove($identifier);
        $this->entityManager->flush();

        return new DeletedOutput(deleted: true, id: $ulid->toBase58());
    }

    private function findClient(string $id, string $field): Client
    {
        $client = $this->clientRepository->find(UlidParser::parse($id, $field));

        if (! $client instanceof Client) {
            throw new ToolCallException(sprintf('Client %s not found.', $id));
        }

        return $client;
    }

    private function findContact(string $id): Contact
    {
        $contact = $this->contactRepository->find(UlidParser::parse($id));

        if (! $contact instanceof Contact) {
            throw new ToolCallException(sprintf('Contact %s not found.', $id));
        }

        return $contact;
    }

    private function applyClientFields(Client $client, ?string $currencyCode, ?string $website, ?string $status): void
    {
        if ($currencyCode !== null) {
            $currencyCode = strtoupper($currencyCode);
            // The cached Currency would otherwise still report the old code until the next load.
            $client->setCurrencyCode($currencyCode)->setCurrency(new Currency($currencyCode));
        }

        if ($website !== null) {
            $client->setWebsite($website);
        }

        if ($status !== null) {
            $client->setStatus(ClientStatus::tryFrom($status) ?? throw new ToolCallException(sprintf(
                'Invalid status "%s". Valid values: %s.',
                $status,
                implode(', ', array_column(ClientStatus::cases(), 'value')),
            )));
        }
    }

    private function validate(object $entity): void
    {
        $errors = $this->validator->validate($entity);

        if (\count($errors) === 0) {
            return;
        }

        $messages = [];

        foreach ($errors as $error) {
            $messages[] = sprintf('%s: %s', $error->getPropertyPath(), $error->getMessage());
        }

        throw new ToolCallException(sprintf('Validation failed: %s', implode('; ', $messages)));
    }
}
