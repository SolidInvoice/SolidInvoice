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

namespace SolidInvoice\ApiBundle\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use SolidInvoice\ApiBundle\ApiResource\DocumentSettings;
use SolidInvoice\InvoiceBundle\Config\ConfigProvider as InvoiceConfig;
use SolidInvoice\InvoiceBundle\Enum\PaymentTerms;
use SolidInvoice\QuoteBundle\Config\ConfigProvider as QuoteConfig;
use SolidInvoice\SettingsBundle\SystemConfig;

/**
 * @implements ProviderInterface<DocumentSettings>
 */
final readonly class DocumentSettingsProvider implements ProviderInterface
{
    /**
     * The text settings, by property.
     */
    public const array TEXT_SETTINGS = [
        'invoiceTerms' => InvoiceConfig::DEFAULT_TERMS,
        'invoiceNotes' => InvoiceConfig::DEFAULT_NOTES,
        'quoteTerms' => QuoteConfig::DEFAULT_TERMS,
        'quoteNotes' => QuoteConfig::DEFAULT_NOTES,
        'invoiceEmailSubject' => 'invoice/email_subject',
        'quoteEmailSubject' => 'quote/email_subject',
        'invoiceNumberPrefix' => 'invoice/id_generation/id_prefix',
        'quoteNumberPrefix' => 'quote/id_generation/id_prefix',
        'invoiceBccAddress' => 'invoice/bcc_address',
    ];

    public const string REMINDERS_ENABLED = 'invoice/reminder/enabled';

    public function __construct(
        private SystemConfig $config,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): DocumentSettings
    {
        $settings = new DocumentSettings();

        foreach (self::TEXT_SETTINGS as $property => $key) {
            $value = $this->config->get($key);
            $settings->{$property} = $value === '' ? null : $value;
        }

        $settings->paymentTerms = PaymentTerms::tryFrom((string) $this->config->get(PaymentTerms::CONFIG_PATH)) ?? PaymentTerms::Net30;
        $settings->quoteValidityDays = (int) ($this->config->get(QuoteConfig::VALIDITY_DAYS) ?? QuoteConfig::DEFAULT_VALIDITY_DAYS);
        $settings->remindersEnabled = (bool) $this->config->get(self::REMINDERS_ENABLED);

        return $settings;
    }
}
