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

namespace SolidInvoice\McpBundle\Mcp\Tool;

use Doctrine\ORM\EntityManagerInterface;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\McpBundle\Mcp\Attribute\McpScopeRequired;
use SolidInvoice\McpBundle\Mcp\McpScopeGuard;
use SolidInvoice\McpBundle\Security\McpScope;
use SolidInvoice\TaxBundle\Entity\Tax;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Generic create / update / delete tools for safe, flat business resources.
 * Complex resources (invoice, quote, payment) use dedicated tools that route
 * through the existing managers and workflows.
 *
 * Company is always set server-side from the active company; any client-supplied
 * "company" field is ignored.
 */
final class ResourceWriteTools
{
    /**
     * @var array<string, class-string>
     */
    private const array CREATABLE = [
        'client' => Client::class,
        'contact' => Contact::class,
        'tax' => Tax::class,
    ];

    /**
     * @var array<string, class-string>
     */
    private const array UPDATABLE = [
        'client' => Client::class,
        'contact' => Contact::class,
        'tax' => Tax::class,
    ];

    /**
     * @var array<string, class-string>
     */
    private const array DELETABLE = [
        'client' => Client::class,
        'contact' => Contact::class,
        'tax' => Tax::class,
    ];

    /**
     * Allowlist of settable fields per resource. Anything outside this list
     * is rejected — AI agents cannot reach framework fields like setCreatedBy,
     * timestamp traits, or any future audit field by guessing setter names.
     *
     * @var array<string, list<string>>
     */
    private const array SETTABLE_FIELDS = [
        Client::class => ['name', 'website', 'status', 'currency_code', 'vat_number'],
        Contact::class => ['first_name', 'last_name', 'email'],
        Tax::class => ['name', 'rate', 'type'],
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
        private readonly EntityNormalizer $normalizer,
        private readonly CompanySelector $companySelector,
        private readonly CompanyRepository $companyRepository,
        private readonly McpScopeGuard $scopeGuard,
        private readonly PropertyAccessorInterface $propertyAccessor,
    ) {
    }

    /**
     * Create a new record of a safe, flat business resource.
     *
     * Supported: client, contact, tax.
     *
     * @param string               $resource Resource name (client, contact, tax)
     * @param array<string, mixed> $data     Field values. "company" is always ignored; the token's bound company is used.
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'create_resource', description: 'Create a new record of a safe, flat resource (client, contact, tax).')]
    #[McpScopeRequired(McpScope::Write)]
    public function createResource(string $resource, array $data): array
    {
        $this->scopeGuard->require(McpScope::Write);
        $class = $this->resolveCreatable($resource);

        $company = $this->requireActiveCompany();

        $entity = $this->hydrateNew($class, $data, $company);

        $errors = $this->validator->validate($entity);

        if (\count($errors) > 0) {
            $messages = [];

            foreach ($errors as $error) {
                $messages[] = sprintf('%s: %s', $error->getPropertyPath(), $error->getMessage());
            }

            throw new ToolCallException(sprintf('Validation failed: %s', implode('; ', $messages)));
        }

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $this->normalizer->normalize($entity);
    }

    /**
     * Update scalar fields on an existing record. "company" cannot be changed.
     *
     * @param string               $resource Resource name (client, contact, tax)
     * @param string               $id       ULID of the record
     * @param array<string, mixed> $data     Partial field values (only provided fields are changed)
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'update_resource', description: 'Update scalar fields on an existing record (client, contact, tax).')]
    #[McpScopeRequired(McpScope::Write)]
    public function updateResource(string $resource, string $id, array $data): array
    {
        $this->scopeGuard->require(McpScope::Write);
        $class = $this->resolveUpdatable($resource);

        $ulid = UlidParser::parse($id);
        $entity = $this->entityManager->getRepository($class)->find($ulid);

        if ($entity === null) {
            throw new ToolCallException(sprintf('%s with id %s not found.', $resource, $id));
        }

        $this->applyFields($entity, $data);

        $errors = $this->validator->validate($entity);

        if (\count($errors) > 0) {
            $messages = [];

            foreach ($errors as $error) {
                $messages[] = sprintf('%s: %s', $error->getPropertyPath(), $error->getMessage());
            }

            throw new ToolCallException(sprintf('Validation failed: %s', implode('; ', $messages)));
        }

        $this->entityManager->flush();

        return $this->normalizer->normalize($entity);
    }

    /**
     * Delete a record.
     *
     * @param string $resource Resource name (client, contact, tax)
     * @param string $id       ULID of the record
     *
     * @return array{deleted: bool, resource: string, id: string}
     */
    #[McpTool(name: 'delete_resource', description: 'Delete a record (client, contact, tax) belonging to the active company.')]
    #[McpScopeRequired(McpScope::Write)]
    public function deleteResource(string $resource, string $id): array
    {
        $this->scopeGuard->require(McpScope::Write);
        $class = $this->resolveDeletable($resource);

        $ulid = UlidParser::parse($id);
        $entity = $this->entityManager->getRepository($class)->find($ulid);

        if ($entity === null) {
            throw new ToolCallException(sprintf('%s with id %s not found.', $resource, $id));
        }

        $this->entityManager->remove($entity);
        $this->entityManager->flush();

        return [
            'deleted' => true,
            'resource' => $resource,
            'id' => $id,
        ];
    }

    /**
     * @param class-string         $class
     * @param array<string, mixed> $data
     */
    private function hydrateNew(string $class, array $data, Company $company): object
    {
        return match ($class) {
            Client::class => $this->buildClient($data, $company),
            Contact::class => $this->buildContact($data, $company),
            Tax::class => $this->buildTax($data, $company),
            default => throw new ToolCallException(sprintf('Cannot create resource of class %s.', $class)),
        };
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildClient(array $data, Company $company): Client
    {
        $client = new Client();
        $client->setCompany($company);

        $this->applyFields($client, $data);

        return $client;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildContact(array $data, Company $company): Contact
    {
        $contact = new Contact();
        $contact->setCompany($company);

        $clientId = $data['client_id'] ?? null;

        if (! \is_string($clientId) || $clientId === '') {
            throw new ToolCallException('Contact requires a "client_id".');
        }

        $client = $this->entityManager->getRepository(Client::class)->find(UlidParser::parse($clientId, 'client_id'));

        if (! $client instanceof Client) {
            throw new ToolCallException(sprintf('Client %s not found.', $clientId));
        }

        $contact->setClient($client);
        $client->addContact($contact);

        unset($data['client_id'], $data['client']);

        $this->applyFields($contact, $data);

        return $contact;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildTax(array $data, Company $company): Tax
    {
        $tax = new Tax();
        $tax->setCompany($company);

        $this->applyFields($tax, $data);

        return $tax;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function applyFields(object $entity, array $data): void
    {
        // Never accept client-supplied company / ID. These are system-managed.
        unset($data['company'], $data['company_id'], $data['id']);

        $allowed = self::SETTABLE_FIELDS[$entity::class] ?? [];

        foreach ($data as $field => $value) {
            if (! \is_string($field)) {
                continue;
            }

            if (! \in_array($field, $allowed, true)) {
                throw new ToolCallException(sprintf(
                    'Field "%s" is not settable on this resource. Allowed fields: %s.',
                    $field,
                    implode(', ', $allowed),
                ));
            }

            $value = $this->coerceFieldValue($entity, $field, $value);

            try {
                // PropertyAccess resolves snake_case -> camelCase property/setter,
                // handles method-style setters, constructor-promoted properties,
                // and public properties uniformly.
                $this->propertyAccessor->setValue($entity, $field, $value);
            } catch (NoSuchPropertyException $exception) {
                throw new ToolCallException(sprintf(
                    'Field "%s" is not settable on this resource: %s',
                    $field,
                    $exception->getMessage(),
                ));
            }
        }
    }

    /**
     * Coerces string input into BackedEnum values so PropertyAccess can call
     * the typed setter without a TypeError. Other types pass through
     * unchanged — the setter's own signature handles final validation.
     */
    private function coerceFieldValue(object $entity, string $field, mixed $value): mixed
    {
        $camelProperty = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $field))));

        $reflection = new \ReflectionClass($entity);

        if (! $reflection->hasProperty($camelProperty)) {
            return $value;
        }

        $type = $reflection->getProperty($camelProperty)->getType();

        if (! $type instanceof \ReflectionNamedType) {
            return $value;
        }

        $typeName = $type->getName();

        if ($value === null && $type->allowsNull()) {
            return null;
        }

        if (enum_exists($typeName) && is_subclass_of($typeName, \BackedEnum::class) && \is_string($value)) {
            $enumValue = $typeName::tryFrom($value);

            if ($enumValue === null) {
                throw new ToolCallException(sprintf('Invalid value "%s" for %s.', $value, $typeName));
            }

            return $enumValue;
        }

        return $value;
    }

    private function requireActiveCompany(): Company
    {
        $companyId = $this->companySelector->getCompany();

        if ($companyId === null) {
            throw new ToolCallException('No active company on this request.');
        }

        $company = $this->companyRepository->find($companyId);

        if (! $company instanceof Company) {
            throw new ToolCallException('Active company not found.');
        }

        return $company;
    }

    /**
     * @return class-string
     */
    private function resolveCreatable(string $resource): string
    {
        if (! isset(self::CREATABLE[$resource])) {
            throw new ToolCallException(sprintf(
                'Resource "%s" cannot be created via create_resource. Supported: %s.',
                $resource,
                implode(', ', array_keys(self::CREATABLE)),
            ));
        }

        return self::CREATABLE[$resource];
    }

    /**
     * @return class-string
     */
    private function resolveUpdatable(string $resource): string
    {
        if (! isset(self::UPDATABLE[$resource])) {
            throw new ToolCallException(sprintf(
                'Resource "%s" cannot be updated via update_resource. Supported: %s.',
                $resource,
                implode(', ', array_keys(self::UPDATABLE)),
            ));
        }

        return self::UPDATABLE[$resource];
    }

    /**
     * @return class-string
     */
    private function resolveDeletable(string $resource): string
    {
        if (! isset(self::DELETABLE[$resource])) {
            throw new ToolCallException(sprintf(
                'Resource "%s" cannot be deleted via delete_resource. Supported: %s.',
                $resource,
                implode(', ', array_keys(self::DELETABLE)),
            ));
        }

        return self::DELETABLE[$resource];
    }
}
