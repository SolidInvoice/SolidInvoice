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

namespace SolidInvoice\InvoiceBundle\Manager;

use DateTimeImmutable;
use Money\Currency;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\InvoiceBundle\DTO\InvoiceFormDTO;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceClientMode;
use SolidInvoice\SettingsBundle\SystemConfig;

/**
 * Manager for handling Invoice form DTO transformations
 */
final class InvoiceFormManager
{
    public function __construct(
        private readonly SystemConfig $systemConfig,
    ) {
    }

    /**
     * Creates a new Invoice entity from DTO
     * Returns an unsaved Invoice entity (client is unpersisted if mode=New)
     * Caller must persist the invoice (client will cascade)
     */
    public function createInvoiceFromDTO(InvoiceFormDTO $dto): Invoice
    {
        $invoice = new Invoice();

        // Resolve client (existing or create new unpersisted one)
        $client = $this->resolveClient($dto);
        $invoice->setClient($client);

        // Map DTO fields to entity
        $invoice->setInvoiceId($dto->invoiceId);
        $invoice->setInvoiceDate($dto->invoiceDate ?? new DateTimeImmutable());
        $invoice->setDue($dto->due);
        $invoice->setDiscount($dto->discount);
        $invoice->setTerms($dto->terms);
        $invoice->setNotes($dto->notes);
        $invoice->setTotal($dto->total);
        $invoice->setBaseTotal($dto->baseTotal);
        $invoice->setTax($dto->tax);

        // Sync lines collection
        foreach ($dto->lines as $line) {
            $invoice->addLine($line);
        }

        // Sync users collection
        // If new client mode, add the newly created contact from the client
        if ($dto->clientMode === InvoiceClientMode::NewClient) {
            foreach ($client->getContacts() as $contact) {
                $invoice->addUser($contact);
            }
        } else {
            // Existing client mode - use selected contacts from DTO
            foreach ($dto->users as $user) {
                $invoice->addUser($user);
            }
        }

        return $invoice;
    }

    /**
     * Updates an existing Invoice from DTO
     * Does NOT change client (edit mode keeps existing client)
     */
    public function updateInvoiceFromDTO(Invoice $invoice, InvoiceFormDTO $dto): void
    {
        $invoice->setInvoiceId($dto->invoiceId);
        $invoice->setInvoiceDate($dto->invoiceDate ?? new DateTimeImmutable());
        $invoice->setDue($dto->due);
        $invoice->setDiscount($dto->discount);
        $invoice->setTerms($dto->terms);
        $invoice->setNotes($dto->notes);
        $invoice->setTotal($dto->total);
        $invoice->setBaseTotal($dto->baseTotal);
        $invoice->setTax($dto->tax);

        // Sync lines collection
        $invoice->getLines()->clear();
        foreach ($dto->lines as $line) {
            $invoice->addLine($line);
        }

        // Sync users collection
        $invoice->getUsers()->clear();
        foreach ($dto->users as $user) {
            $invoice->addUser($user);
        }
    }

    /**
     * Creates DTO from Invoice entity for editing
     */
    public function createDTOFromInvoice(Invoice $invoice): InvoiceFormDTO
    {
        $dto = new InvoiceFormDTO();

        // Always use Existing mode when editing
        $dto->clientMode = InvoiceClientMode::Existing;
        $dto->client = $invoice->getClient();

        // Map entity fields to DTO
        $dto->invoiceId = $invoice->getInvoiceId();
        $dto->invoiceDate = $invoice->getInvoiceDate();
        $dto->due = $invoice->getDue();
        $dto->discount = $invoice->getDiscount();
        $dto->terms = $invoice->getTerms();
        $dto->notes = $invoice->getNotes();
        $dto->total = (string) $invoice->getTotal();
        $dto->baseTotal = (string) $invoice->getBaseTotal();
        $dto->tax = (string) $invoice->getTax();

        // Copy collections
        foreach ($invoice->getLines() as $line) {
            $dto->lines->add($line);
        }

        foreach ($invoice->getUsers() as $user) {
            $dto->users->add($user);
        }

        return $dto;
    }

    /**
     * Resolves client from DTO - returns existing client or creates new unpersisted one
     */
    private function resolveClient(InvoiceFormDTO $dto): Client
    {
        if ($dto->clientMode === InvoiceClientMode::Existing) {
            if ($dto->client === null) {
                throw new \InvalidArgumentException('Client is required when clientMode is Existing');
            }

            return $dto->client;
        }

        // Mode is New - create unpersisted client
        return $this->createClientFromDTO($dto);
    }

    /**
     * Creates an unpersisted Client entity from inline DTO fields
     * Client will be persisted later via cascade when invoice is saved
     */
    private function createClientFromDTO(InvoiceFormDTO $dto): Client
    {
        if (! $dto->hasInlineClientData()) {
            throw new \InvalidArgumentException('Inline client data is incomplete');
        }

        $client = new Client();
        $client->setName($dto->newClientName);

        // Set currency from system config
        $currency = $this->systemConfig->getCurrency();
        $client->setCurrency($currency);

        // Create contact and associate with client
        $contact = new Contact();
        $contact->setFirstName($dto->newContactFirstName);
        $contact->setLastName($dto->newContactLastName);
        $contact->setEmail($dto->newContactEmail);
        $contact->setClient($client);

        // Add contact to client (cascade will persist)
        $client->addContact($contact);

        // Return unpersisted client - will be saved via cascade
        return $client;
    }
}
