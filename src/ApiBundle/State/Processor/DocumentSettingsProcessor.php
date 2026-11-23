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

namespace SolidInvoice\ApiBundle\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use SolidInvoice\ApiBundle\ApiResource\DocumentSettings;
use SolidInvoice\ApiBundle\State\Provider\DocumentSettingsProvider;
use SolidInvoice\InvoiceBundle\Enum\PaymentTerms;
use SolidInvoice\QuoteBundle\Config\ConfigProvider as QuoteConfig;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidInvoice\SettingsBundle\Repository\SettingsRepository;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

/**
 * @implements ProcessorInterface<DocumentSettings, DocumentSettings>
 */
final readonly class DocumentSettingsProcessor implements ProcessorInterface
{
    public function __construct(
        private SettingsRepository $settings,
        private FeatureGate $featureGate,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): DocumentSettings
    {
        $previous = $context['previous_data'] ?? null;

        if (
            $previous instanceof DocumentSettings
            && $previous->remindersEnabled !== $data->remindersEnabled
            && ! $this->featureGate->isEnabled(Feature::AutomatedReminders->value)
        ) {
            throw new ValidationException(new ConstraintViolationList([
                new ConstraintViolation($this->translator->trans('settings.feature_unavailable', [], 'validators'), null, [], $data, 'remindersEnabled', $data->remindersEnabled),
            ]));
        }

        $values = [
            PaymentTerms::CONFIG_PATH => $data->paymentTerms->value,
            QuoteConfig::VALIDITY_DAYS => (string) $data->quoteValidityDays,
            DocumentSettingsProvider::REMINDERS_ENABLED => $data->remindersEnabled ? '1' : null,
        ];

        foreach (DocumentSettingsProvider::TEXT_SETTINGS as $property => $key) {
            $values[$key] = $data->{$property};
        }

        $this->settings->store($values);

        return $data;
    }
}
