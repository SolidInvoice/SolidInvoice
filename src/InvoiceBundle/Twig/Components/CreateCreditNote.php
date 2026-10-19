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
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\CoreBundle\Twig\Components\ReordersLines;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\InvoiceBundle\Form\Type\CreditNoteType;
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

/**
 * Reuses the invoice line-item editing mechanics ({@see LiveCollectionTrait}, the
 * `line-reorder`/`line-tax` Stimulus controllers) for the credit note create/edit forms —
 * see `design` §11 on SOL-69. Final submission is a normal POST back to
 * {@see \SolidInvoice\InvoiceBundle\Action\CreditNote\Create} or
 * {@see \SolidInvoice\InvoiceBundle\Action\CreditNote\Edit}, not a {@see LiveAction}; this
 * component only owns the reactive line editing and live total recalculation, mirroring
 * {@see CreateRecurringInvoice}.
 *
 * @see \SolidInvoice\InvoiceBundle\Tests\Twig\Components\CreateCreditNoteTest
 */
#[AsLiveComponent]
final class CreateCreditNote extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;
    use ReordersLines;

    #[LiveProp(writable: true, fieldName: 'formData')]
    public CreditNote $creditNote;

    #[LiveProp(writable: false)]
    public bool $isEdit = false;

    #[LiveProp(writable: false)]
    public bool $clientLocked = false;

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
        $this->totalCalculator->calculateTotals($this->creditNote);
    }

    /**
     * @return FormInterface<mixed>
     */
    protected function instantiateForm(): FormInterface
    {
        $options = ['client_locked' => $this->clientLocked];

        if (($this->formValues['client'] ?? '') !== '') {
            $client = $this->clientRepository->find($this->formValues['client']);
            $options['currency'] = $client?->getCurrency();
        } else {
            $options['currency'] = $this->creditNote->getClient()?->getCurrency();
        }

        return $this->createForm(CreditNoteType::class, $this->creditNote, array_filter($options, static fn (mixed $value): bool => $value !== null));
    }

    #[LiveAction]
    public function clearClient(): void
    {
        $this->formValues['client'] = null;
    }

    #[ExposeInTemplate]
    public function hasTax(): bool
    {
        return $this->taxRepository->taxRatesConfigured();
    }
}
