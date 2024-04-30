<?php

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
    }

    protected function instantiateForm(): FormInterface
    {
        $options = [];

        if (($this->formValues['client']['autocomplete'] ?? '') !== '') {
            $client = $this->clientRepository->find($this->formValues['client']['autocomplete']);
            $options['currency'] = $client?->getCurrency();
        }

        return $this->createForm(InvoiceType::class, $this->invoice, $options);
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
