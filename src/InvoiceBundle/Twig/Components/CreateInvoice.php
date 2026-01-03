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

#[AsLiveComponent()]
final class CreateInvoice extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    #[LiveProp(writable: true, fieldName: 'formData')]
    public Invoice $invoice;

    #[LiveProp(writable: true)]
    public bool $isEdit = false;

    #[LiveProp]
    public ?string $previousClientId = null;

    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly TotalCalculator $totalCalculator,
        private readonly TaxRepository $taxRepository,
    ) {
    }

    /**
     * @throws MathException
     */
    #[PreReRender]
    public function preRender(): void
    {
        $this->totalCalculator->calculateTotals($this->invoice);

        // Auto-select all contacts when client is selected for the first time
        $currentClientId = $this->formValues['client'] ?? null;
        if ($currentClientId !== null && $currentClientId !== '' && $this->previousClientId !== $currentClientId) {
            $this->autoSelectContacts();
            $this->previousClientId = $currentClientId;
        }
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
        return $this->invoice->getTerms() !== null && $this->invoice->getTerms() !== ''
            || $this->invoice->getNotes() !== null && $this->invoice->getNotes() !== '';
    }

    /**
     * Auto-select all contacts for the selected client.
     */
    private function autoSelectContacts(): void
    {
        $clientId = $this->formValues['client'] ?? null;
        if ($clientId === null || $clientId === '') {
            return;
        }

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
