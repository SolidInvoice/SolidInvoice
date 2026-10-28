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

namespace SolidInvoice\ApiBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use SolidInvoice\ApiBundle\State\Processor\DocumentSettingsProcessor;
use SolidInvoice\ApiBundle\State\Provider\DocumentSettingsProvider;
use SolidInvoice\InvoiceBundle\Enum\PaymentTerms;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The company's invoice and quote settings: what new documents start with, how they are numbered
 * and emailed. The same values as Settings → Invoice and Settings → Quote in the web app.
 */
#[ApiResource(
    operations: [
        new Get(uriTemplate: '/settings/documents', uriVariables: []),
        new Patch(uriTemplate: '/settings/documents', uriVariables: [], processor: DocumentSettingsProcessor::class),
    ],
    normalizationContext: ['skip_null_values' => false],
    graphQlOperations: [],
    provider: DocumentSettingsProvider::class,
)]
final class DocumentSettings
{
    public PaymentTerms $paymentTerms = PaymentTerms::Net30;

    public ?string $invoiceTerms = null;

    public ?string $invoiceNotes = null;

    public ?string $quoteTerms = null;

    public ?string $quoteNotes = null;

    #[Assert\Range(min: 1, max: 365)]
    public int $quoteValidityDays = 30;

    #[Assert\Length(max: 255)]
    public ?string $invoiceEmailSubject = null;

    #[Assert\Length(max: 255)]
    public ?string $quoteEmailSubject = null;

    #[Assert\Length(max: 50)]
    public ?string $invoiceNumberPrefix = null;

    #[Assert\Length(max: 50)]
    public ?string $quoteNumberPrefix = null;

    #[Assert\Email]
    public ?string $invoiceBccAddress = null;

    /**
     * Only changes on a plan with automated reminders.
     */
    public bool $remindersEnabled = false;
}
