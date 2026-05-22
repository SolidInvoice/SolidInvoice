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

namespace SolidInvoice\CoreBundle\Templating;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use DateTime;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use InvalidArgumentException;
use Money\Currency as MoneyCurrency;
use Money\Money;
use SolidInvoice\ClientBundle\Entity\Address;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Entity\Credit;
use SolidInvoice\CoreBundle\Entity\BillingTemplate;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Repository\BillingTemplateRepository;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line as InvoiceLine;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\QuoteBundle\Entity\Line as QuoteLine;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\TaxBundle\Entity\Tax;
use Twig\Environment;
use Twig\Extension\SandboxExtension;
use Twig\Sandbox\SecurityPolicy;
use Twig\Sandbox\SecurityPolicyInterface;
use Twig\Sandbox\SourcePolicyInterface;
use Twig\Source;
use Twig\TemplateWrapper;
use function sha1;
use function sprintf;
use function str_starts_with;
use function strtolower;
use function trim;

/**
 * Resolves the active billing template (HTML / PDF / email) for invoices and
 * quotes from the BillingTemplate repository, rendering through a sandboxed
 * Twig environment.
 *
 * Not marked final so Mockery (which doesn't mock final classes by default)
 * can still produce test doubles in listener/action tests.
 *
 * @see \SolidInvoice\CoreBundle\Tests\Templating\BillingTemplateResolverTest
 */
class BillingTemplateResolver
{
    /**
     * @var array<string, array<string, string>>
     */
    private const DEFAULT_TEMPLATES = [
        BillingTemplate::TYPE_INVOICE => [
            BillingTemplate::VARIANT_HTML => '@SolidInvoiceInvoice/invoice_template.html.twig',
            BillingTemplate::VARIANT_PDF => '@SolidInvoiceInvoice/Pdf/invoice.html.twig',
            BillingTemplate::VARIANT_EMAIL => '@SolidInvoiceInvoice/Email/invoice.html.twig',
        ],
        BillingTemplate::TYPE_QUOTE => [
            BillingTemplate::VARIANT_HTML => '@SolidInvoiceQuote/quote_template.html.twig',
            BillingTemplate::VARIANT_PDF => '@SolidInvoiceQuote/Pdf/quote.html.twig',
            BillingTemplate::VARIANT_EMAIL => '@SolidInvoiceQuote/Email/quote.html.twig',
        ],
    ];

    /**
     * Marker used to identify sandboxed in-memory templates produced by this
     * resolver. The source policy checks for this prefix when deciding whether
     * to enforce the sandbox.
     */
    public const SANDBOX_NAME_PREFIX = 'solidinvoice_billing/';

    public function __construct(
        private readonly BillingTemplateRepository $repository,
    ) {
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function getDefaultTemplatePaths(): array
    {
        return self::DEFAULT_TEMPLATES;
    }

    public function getDefaultTemplatePath(string $type, string $variant): string
    {
        $type = strtolower($type);
        $variant = strtolower($variant);

        if (! isset(self::DEFAULT_TEMPLATES[$type][$variant])) {
            throw new InvalidArgumentException(
                sprintf('Unsupported billing template type "%s" with variant "%s"', $type, $variant)
            );
        }

        return self::DEFAULT_TEMPLATES[$type][$variant];
    }

    public function resolveTemplate(Environment $environment, string $type, string $variant): string|TemplateWrapper
    {
        $type = strtolower($type);
        $variant = strtolower($variant);

        if (! isset(self::DEFAULT_TEMPLATES[$type][$variant])) {
            throw new InvalidArgumentException(
                sprintf('Unsupported billing template type "%s" with variant "%s"', $type, $variant)
            );
        }

        $active = $this->repository->findActive($type, $variant);

        if ($active instanceof BillingTemplate && '' !== trim($active->getContent())) {
            return $this->wrap($environment, $type, $variant, $active->getContent(), (string) $active->getId());
        }

        return self::DEFAULT_TEMPLATES[$type][$variant];
    }

    /**
     * @param array<string, mixed> $context
     */
    public function render(Environment $environment, string $type, string $variant, array $context): string
    {
        $template = $this->resolveTemplate($environment, $type, $variant);

        if ($template instanceof TemplateWrapper) {
            return $template->render($context);
        }

        return $environment->render($template, $context);
    }

    /**
     * Render an ad-hoc template body (for previews) using the sandbox.
     *
     * @param array<string, mixed> $context
     */
    public function renderPreview(Environment $environment, string $type, string $variant, string $content, array $context): string
    {
        $template = $this->wrap($environment, $type, $variant, $content, 'preview-' . sha1($content));

        return $template->render($context);
    }

    public function createSandboxExtension(): SandboxExtension
    {
        return new SandboxExtension(
            self::createSecurityPolicy(),
            false,
            new class() implements SourcePolicyInterface {
                public function enableSandbox(Source $source): bool
                {
                    return str_starts_with($source->getName(), BillingTemplateResolver::SANDBOX_NAME_PREFIX);
                }
            }
        );
    }

    public static function createSecurityPolicy(): SecurityPolicyInterface
    {
        $allowedTags = [
            'apply',
            'autoescape',
            'block',
            'embed',
            'extends',
            'for',
            'if',
            'set',
            'use',
            'with',
        ];

        $allowedFilters = [
            'capitalize',
            'date',
            'default',
            'e',
            'escape',
            'filter',
            'first',
            'format',
            'formatCurrency',
            'inky_to_html',
            'join',
            'json_encode',
            'keys',
            'last',
            'length',
            'lower',
            'merge',
            'nl2br',
            'number_format',
            'replace',
            'reverse',
            'slice',
            'sort',
            'split',
            'striptags',
            'title',
            'trans',
            'trim',
            'upper',
            'url_encode',
        ];

        $allowedFunctions = [
            'app_logo',
            'asset',
            'billing_template',
            'company_name',
            'date',
            'discount',
            'file',
            'icon',
            'invoice_label',
            'max',
            'min',
            'path',
            'payable_amount',
            'payments_configured',
            'quote_label',
            'range',
            'tax_breakdown',
            'tax_identifiers',
            'template_address',
            'template_setting',
            'url',
        ];

        $allowedMethods = [
            Invoice::class => [
                'getInvoiceId', 'getStatus', 'getStatusValue', 'getInvoiceDate',
                'getDue', 'getCreated', 'getUpdated', 'getCreatedTimestamp',
                'getTotal', 'getBaseTotal', 'getBalance', 'getDiscount',
                'getTax', 'getNotes', 'getTerms', 'getClient', 'getLines',
                'getCompany', 'getUuid', 'getId', 'getPayments', 'getUsers',
                'getQuote', 'getInvoiceTaxes', 'getLineDescriptions',
                'getPayableAmount', 'getWithholdingAmount', 'getPaidDate',
                'getRecurringInvoice',
                'hasDiscount', 'isPaid', 'isCancelled', 'isDraft', 'isPending',
                'isOverdue', 'isNew', 'isArchived', '__toString',
            ],
            RecurringInvoice::class => [
                'getInvoiceId', 'getStatus', 'getStatusValue', 'getCreated',
                'getUpdated', 'getTotal', 'getBaseTotal', 'getDiscount', 'getTax',
                'getNotes', 'getTerms', 'getClient', 'getLines', 'getCompany',
                'getId', 'getUsers', 'getRecurringOptions', 'getPayableAmount',
                'getWithholdingAmount', 'hasDiscount', '__toString',
            ],
            Quote::class => [
                'getQuoteId', 'getStatus', 'getStatusValue', 'getCreated',
                'getUpdated', 'getCreatedTimestamp', 'getTotal', 'getBaseTotal',
                'getDue', 'getDiscount', 'getTax', 'getNotes', 'getTerms',
                'getClient', 'getLines', 'getCompany', 'getUuid', 'getId',
                'getUsers', 'getInvoice', 'getInvoiceTaxes', 'getLineDescriptions',
                'getPayableAmount', 'getWithholdingAmount', 'hasDiscount',
                'isValid', '__toString',
            ],
            InvoiceLine::class => [
                'getDescription', 'getPrice', 'getQty', 'getTotal', 'getTax',
                'getTaxes', 'getId',
            ],
            QuoteLine::class => [
                'getDescription', 'getPrice', 'getQty', 'getTotal', 'getTax',
                'getTaxes', 'getId',
            ],
            Client::class => [
                'getName', 'getWebsite', 'getVatNumber', 'getCurrency', 'getCurrencyCode',
                'getStatus', 'getContacts', 'getAddresses', 'getCredit', 'getId',
                'getCompany', '__toString',
            ],
            Contact::class => [
                'getFirstName', 'getLastName', 'getEmail', 'getId', '__toString',
            ],
            Address::class => [
                'getStreet1', 'getStreet2', 'getCity', 'getState', 'getZip',
                'getCountry', 'getCountryName', 'isEmpty', '__toString',
            ],
            Credit::class => [
                'getValue', 'getId',
            ],
            Payment::class => [
                'getMethod', 'getStatus', 'getAmount', 'getTotalAmount', 'getCompleted',
                'getMessage', 'getNotes', 'getReference', 'getCreated', 'getId',
                'getCurrencyCode',
            ],
            PaymentMethod::class => [
                'getName', 'getGatewayName', 'isEnabled', 'getId', '__toString',
            ],
            Company::class => [
                'getName', 'getCurrency', 'getId', '__toString',
            ],
            Tax::class => [
                'getName', 'getRate', 'getType', 'getId', '__toString',
            ],
            Discount::class => [
                'getType', 'getValue', 'getValueMoney', 'getValuePercentage',
            ],
            Money::class => [
                'getAmount', 'getCurrency', 'isPositive', 'isZero', 'isNegative',
                'equals', 'lessThan', 'greaterThan',
            ],
            MoneyCurrency::class => [
                'getCode', '__toString', 'equals',
            ],
            BigNumber::class => [
                'isZero', 'isPositive', 'isPositiveOrZero', 'isNegative',
                'isNegativeOrZero', 'toFloat', 'toInt', '__toString',
            ],
            BigDecimal::class => [
                'isZero', 'isPositive', 'isPositiveOrZero', 'isNegative',
                'isNegativeOrZero', 'toFloat', 'toInt', '__toString',
            ],
            BigInteger::class => [
                'isZero', 'isPositive', 'isPositiveOrZero', 'isNegative',
                'isNegativeOrZero', 'toFloat', 'toInt', '__toString',
            ],
            DateTime::class => ['format', 'getTimestamp', 'getTimezone'],
            DateTimeImmutable::class => ['format', 'getTimestamp', 'getTimezone'],
            \DateTimeInterface::class => ['format', 'getTimestamp', 'getTimezone'],
            Collection::class => [
                'count', 'toArray', 'getValues', 'isEmpty', 'first', 'last',
                'filter', 'map', 'contains', 'containsKey', 'getKeys',
            ],
            ArrayCollection::class => [
                'count', 'toArray', 'getValues', 'isEmpty', 'first', 'last',
                'filter', 'map', 'contains', 'containsKey', 'getKeys',
            ],

            // 3.0.x tax breakdown additions
            \SolidInvoice\TaxBundle\Entity\TaxIdentifier::class => [
                'getId', 'getLabel', 'getValue', 'isPrimary', '__toString',
            ],
            \SolidInvoice\TaxBundle\Entity\LineTax::class => [
                'getId', 'getTax', 'getNameSnapshot', 'getRateSnapshot',
                'getCategorySnapshot', 'getTypeSnapshot', 'isCompound',
                'getSequence', 'getAmount', 'getSnapshottedAt',
            ],
            \SolidInvoice\TaxBundle\Enum\TaxCategory::class => ['name', 'value'],
            \SolidInvoice\TaxBundle\Enum\TaxDirection::class => ['name', 'value'],
            \SolidInvoice\TaxBundle\Enum\TaxType::class => ['name', 'value'],
        ];

        $allowedProperties = [
            Invoice::class => ['users', 'payments', 'lines'],
            Quote::class => ['users', 'lines'],
            RecurringInvoice::class => ['users', 'lines'],
            Client::class => ['contacts', 'addresses'],
            Company::class => [
                'apiTokens', 'apiTokenHistories', 'taxes', 'additionalContactDetails',
                'addresses', 'clients', 'contacts', 'contactTypes', 'credit',
                'userInvitations', 'settings', 'quotes', 'quoteLines',
                'paymentMethods', 'payments', 'userNotifications',
                'transportSettings', 'invoices', 'recurringInvoices', 'invoiceLines',
            ],

            // TaxSummaryRow exposes everything through public readonly properties.
            \SolidInvoice\TaxBundle\Calculator\Result\TaxSummaryRow::class => [
                'name', 'rate', 'category', 'type', 'compound', 'amount',
                'sequence', 'direction', 'note',
            ],
        ];

        return new SecurityPolicy(
            $allowedTags,
            $allowedFilters,
            $allowedMethods,
            $allowedProperties,
            $allowedFunctions
        );
    }

    private function wrap(Environment $environment, string $type, string $variant, string $content, string $identifier): TemplateWrapper
    {
        return $environment->createTemplate(
            $content,
            self::SANDBOX_NAME_PREFIX . sprintf('%s_%s_%s', $type, $variant, $identifier)
        );
    }
}
