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

namespace SolidInvoice\InvoiceBundle\Twig\Components;

use Brick\Math\Exception\MathException;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Form\Type\InvoiceType;
use SolidInvoice\TaxBundle\Repository\TaxRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PreReRender;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsLiveComponent()]
final class CreateInvoice extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    #[LiveProp(writable: true, fieldName: 'formData')]
    public Invoice $invoice;

    #[LiveProp(writable: true)]
    public bool $isEdit = false;

    #[LiveProp(writable: true)]
    public ?string $previousClientId = null;

    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly TotalCalculator $totalCalculator,
        private readonly TaxRepository $taxRepository,
    ) {
    }

    /**
     * Auto-select contacts on initial mount when a client is pre-selected.
     * Priority 10 ensures this runs BEFORE initializeForm() (priority 0) so
     * the entity has contacts set before the form is created.
     */
    #[PostMount(priority: 10)]
    public function initializeContacts(): void
    {
        $client = $this->invoice->getClient();

        // Auto-select all contacts if client is set but no users are selected
        if ($client instanceof Client && $this->invoice->getUsers()->isEmpty()) {
            foreach ($client->getContacts() as $contact) {
                $this->invoice->addUser($contact);
            }
            // Track the client so we don't re-select on subsequent renders
            $this->previousClientId = (string) $client->getId();
        }
    }

    /**
     * Auto-select contacts when client changes during re-render.
     * Priority 10 ensures this runs BEFORE submitFormOnRender() (priority 0)
     * so the contacts are included in the form submission.
     */
    #[PreReRender(priority: 10)]
    public function autoSelectContactsOnClientChange(): void
    {
        $this->maybeAutoSelectContacts();
    }

    /**
     * Calculate totals after form submission.
     * Priority -10 ensures this runs AFTER submitFormOnRender() (priority 0)
     * so the entity has been updated with the new form values.
     *
     * @throws MathException
     */
    #[PreReRender(priority: -10)]
    public function calculateTotals(): void
    {
        $this->totalCalculator->calculateTotals($this->invoice);
    }

    protected function instantiateForm(): FormInterface
    {
        $options = [];

        if (($this->formValues['client'] ?? '') !== '') {
            $client = $this->clientRepository->find($this->formValues['client']);
            $options['currency'] = $client?->getCurrency();
        }

        return $this->createForm(InvoiceType::class, $this->invoice, $options);
    }

    #[LiveAction]
    public function clearClient(): void
    {
        $this->formValues['client'] = null;
        $this->formValues['users'] = [];
        $this->previousClientId = null;
    }

    #[ExposeInTemplate]
    public function hasTax(): bool
    {
        return $this->taxRepository->taxRatesConfigured();
    }

    #[ExposeInTemplate]
    public function hasTermsOrNotes(): bool
    {
        return ($this->invoice->getTerms() !== null && $this->invoice->getTerms() !== '')
            || ($this->invoice->getNotes() !== null && $this->invoice->getNotes() !== '');
    }

    /**
     * Auto-select all contacts when client changes during re-render.
     */
    private function maybeAutoSelectContacts(): void
    {
        $currentClientId = $this->formValues['client'] ?? null;

        // Skip if no client selected
        if ($currentClientId === null || $currentClientId === '') {
            return;
        }

        // Skip if client hasn't changed (already processed)
        if ($this->previousClientId === $currentClientId) {
            return;
        }

        // Update tracking and auto-select contacts
        $this->previousClientId = $currentClientId;
        $this->autoSelectContacts($currentClientId);
    }

    /**
     * Auto-select all contacts for the given client.
     */
    private function autoSelectContacts(string $clientId): void
    {
        $client = $this->clientRepository->find($clientId);
        if (! $client instanceof Client) {
            return;
        }

        // Auto-select all contacts
        $contactIds = [];
        foreach ($client->getContacts() as $contact) {
            $contactIds[] = (string) $contact->getId();
        }

        $this->formValues['users'] = $contactIds;
    }
}
