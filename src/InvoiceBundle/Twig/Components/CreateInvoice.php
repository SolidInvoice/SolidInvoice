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
use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\CoreBundle\Contracts\EmailVerificationGateInterface;
use SolidInvoice\InvoiceBundle\DTO\InvoiceFormDTO;
use SolidInvoice\InvoiceBundle\Email\InvoiceEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceClientMode;
use SolidInvoice\InvoiceBundle\Form\Type\InvoiceType;
use SolidInvoice\InvoiceBundle\Manager\InvoiceFormManager;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\MoneyBundle\Calculator;
use SolidInvoice\TaxBundle\Repository\TaxRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PreReRender;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsLiveComponent]
final class CreateInvoice extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    public InvoiceFormDTO $dto;

    #[LiveProp(writable: false)]
    public bool $isEdit = false;

    #[LiveProp(writable: false, fieldName: 'invoiceEntity')]
    public ?Invoice $invoice = null;

    #[LiveProp(writable: true)]
    public ?string $previousClientId = null;

    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly TotalCalculator $totalCalculator,
        private readonly TaxRepository $taxRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly WorkflowInterface $invoiceStateMachine,
        private readonly MailerInterface $mailer,
        private readonly RouterInterface $router,
        private readonly InvoiceFormManager $formManager,
        private readonly Calculator $calculator,
        private readonly EmailVerificationGateInterface $emailVerificationGate,
    ) {
        $this->dto = new InvoiceFormDTO();
    }

    /**
     * Auto-select contacts on initial mount when a client is pre-selected.
     * Priority 10 ensures this runs BEFORE initializeForm() (priority 0) so
     * the DTO has contacts set before the form is created.
     */
    #[PostMount(priority: 10)]
    public function initializeContacts(): void
    {
        $client = $this->dto->client;

        // Auto-select all contacts if client is set but no users are selected
        if ($client instanceof Client && $this->dto->users->isEmpty()) {
            foreach ($client->getContacts() as $contact) {
                $this->dto->users->add($contact);
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
     * so the DTO has been updated with the new form values.
     *
     * @throws MathException
     */
    #[PreReRender(priority: -10)]
    public function calculateTotals(): void
    {
        // Skip calculation if data is incomplete
        if (! $this->canCalculateTotals()) {
            $this->dto->total = '0';
            $this->dto->baseTotal = '0';
            $this->dto->tax = '0';
            return;
        }

        // Create temporary invoice from DTO to calculate totals
        $tempInvoice = $this->formManager->createInvoiceFromDTO($this->dto);
        $this->totalCalculator->calculateTotals($tempInvoice);

        // Copy calculated totals back to DTO (convert BigNumber to string)
        $this->dto->total = (string) $tempInvoice->getTotal();
        $this->dto->baseTotal = (string) $tempInvoice->getBaseTotal();
        $this->dto->tax = (string) $tempInvoice->getTax();
    }

    protected function instantiateForm(): FormInterface
    {
        $options = [];

        // Set currency based on client (existing or from DTO) or use system default
        if ($this->dto->client instanceof Client) {
            $options['currency'] = $this->dto->client->getCurrency();
        } elseif (($this->formValues['client'] ?? '') !== '') {
            $client = $this->clientRepository->find($this->formValues['client']);
            $options['currency'] = $client?->getCurrency();
        }

        return $this->createForm(InvoiceType::class, $this->dto, $options);
    }

    #[LiveAction]
    public function clearClient(): void
    {
        $this->formValues['client'] = null;
        $this->formValues['users'] = [];
        $this->previousClientId = null;
    }

    #[LiveAction]
    public function saveDraft(): ?Response
    {
        return $this->saveInvoice('draft');
    }

    #[LiveAction]
    public function saveUpdate(): ?Response
    {
        return $this->saveInvoice('save');
    }

    #[LiveAction]
    public function savePublish(): ?Response
    {
        return $this->saveInvoice('publish');
    }

    #[LiveAction]
    public function saveSend(): ?Response
    {
        if ($this->emailVerificationGate->isGated()) {
            $this->addFlash('error', 'email_verification.flash.send_invoice');
            return null;
        }

        return $this->saveInvoice('send');
    }

    private function saveInvoice(string $action): ?Response
    {
        $this->submitForm();

        $form = $this->getForm();

        if (! $form->isValid()) {
            // Totals already recalculated by PreReRender hook
            return null;
        }

        /** @var InvoiceFormDTO $dto */
        $dto = $form->getData();

        // Convert DTO to Invoice entity
        // If clientMode=New, manager creates unpersisted client
        // Client will be persisted via cascade when invoice is saved
        if ($this->isEdit) {
            assert($this->invoice instanceof Invoice);

            $this->formManager->updateInvoiceFromDTO($this->invoice, $dto);

            if ('send' === $action || 'publish' === $action) {
                $this->invoiceStateMachine->apply($this->invoice, Graph::TRANSITION_ACCEPT);
            }

            $this->entityManager->flush();

            if ('send' === $action) {
                $this->mailer->send(new InvoiceEmail($this->invoice));
            }

            $this->addFlash('success', 'invoice.edit.success');

            $url = $this->router->generate('_invoices_view', ['id' => $this->invoice->getId()]);
            return $this->redirect($url);
        }

        $invoice = $this->formManager->createInvoiceFromDTO($dto);

        // Apply state transitions
        if (! $invoice->getId() instanceof Ulid) {
            $this->invoiceStateMachine->apply($invoice, Graph::TRANSITION_NEW);
        }

        // Publish the invoice if the action is 'send' or 'publish'
        if ('send' === $action || 'publish' === $action) {
            $this->invoiceStateMachine->apply($invoice, Graph::TRANSITION_ACCEPT);
        }

        // Persist invoice (client cascades if new)
        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        // Send the invoice only if the action is 'send'
        if ('send' === $action) {
            $this->mailer->send(new InvoiceEmail($invoice));
        }

        // Add flash message and redirect
        $this->addFlash('success', 'invoice.create.success');

        $url = $this->router->generate('_invoices_view', ['id' => $invoice->getId()]);
        return $this->redirect($url);
    }

    #[ExposeInTemplate]
    public function hasTax(): bool
    {
        return $this->taxRepository->taxRatesConfigured();
    }

    #[ExposeInTemplate]
    public function hasTermsOrNotes(): bool
    {
        return ($this->dto->terms !== null && $this->dto->terms !== '')
            || ($this->dto->notes !== null && $this->dto->notes !== '');
    }

    #[ExposeInTemplate]
    public function hasClients(): bool
    {
        return $this->clientRepository->getTotalClients() > 0;
    }

    /**
     * Calculate discount amount from DTO for template display
     */
    #[ExposeInTemplate]
    public function getDiscountAmount(): string
    {
        if ($this->dto->discount === null) {
            return '0';
        }

        // Skip calculation if we can't create an invoice (incomplete data)
        if (! $this->canCalculateTotals()) {
            return '0';
        }

        // Create temporary invoice to leverage Calculator
        try {
            $tempInvoice = $this->formManager->createInvoiceFromDTO($this->dto);
            return (string) $this->calculator->calculateDiscount($tempInvoice);
        } catch (\InvalidArgumentException) {
            // Client data incomplete during mode switching
            return '0';
        }
    }

    /**
     * Check if we have enough data to calculate totals
     */
    private function canCalculateTotals(): bool
    {
        // Need at least one line item
        if ($this->dto->lines->isEmpty()) {
            return false;
        }

        // Check client data based on mode
        if ($this->dto->clientMode === InvoiceClientMode::Existing) {
            return $this->dto->client instanceof Client;
        }

        // NewClient mode - need inline data
        return $this->dto->hasInlineClientData();
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
