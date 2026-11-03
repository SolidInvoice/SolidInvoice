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

namespace SolidInvoice\ApiBundle\Serializer\Normalizer;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\IriConverterInterface;
use ArrayObject;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\ApiBundle\Versioning\ApiVersion;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Contracts\HasFrozenCurrencyInterface;
use SolidInvoice\InvoiceBundle\Entity\Line as InvoiceLine;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoiceLine;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\QuoteBundle\Entity\Line as QuoteLine;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Throwable;
use function is_a;
use function is_array;
use function is_string;

/**
 * Puts the currency of the record being (de)normalized into the serializer context, so the money
 * normalizers below it can scale by that currency's own precision. Only active when the request
 * asked for decimal money ({@see ApiVersion::V2026_10}); the original format scales by a fixed 100
 * and needs no currency.
 *
 * @see \SolidInvoice\ApiBundle\Tests\Functional\Api\MoneyFormatTest
 */
#[AutoconfigureTag('serializer.normalizer', ['priority' => 64])]
final class MoneyCurrencyNormalizer implements NormalizerInterface, NormalizerAwareInterface, DenormalizerInterface, DenormalizerAwareInterface
{
    use NormalizerAwareTrait;
    use DenormalizerAwareTrait;

    /**
     * Context key holding the ISO 4217 code that money in the current record is in.
     */
    public const string CURRENCY = 'api_money_currency';

    private const string ALREADY_CALLED = 'api_money_currency_resolved';

    private const array CURRENCY_CLASSES = [
        HasFrozenCurrencyInterface::class,
        InvoiceLine::class,
        QuoteLine::class,
        RecurringInvoiceLine::class,
        Payment::class,
        Client::class,
    ];

    public function __construct(
        private readonly IriConverterInterface $iriConverter,
        private readonly ManagerRegistry $registry,
    ) {
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): ArrayObject | array | string | int | float | bool | null
    {
        $context[self::ALREADY_CALLED][spl_object_id($data)] = true;
        $context[self::CURRENCY] = $this->currencyOf($data) ?? $context[self::CURRENCY] ?? null;

        return $this->normalizer->normalize($data, $format, $context);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return ($context[ApiVersion::DECIMAL_MONEY] ?? false)
            && is_object($data)
            && ! isset($context[self::ALREADY_CALLED][spl_object_id($data)])
            && $this->isCurrencyClass($data::class);
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $context[self::ALREADY_CALLED][$type] = true;

        $existing = $context[AbstractNormalizer::OBJECT_TO_POPULATE] ?? null;
        $context[self::CURRENCY] = (is_object($existing) ? $this->currencyOf($existing) : null)
            ?? $this->currencyFromPayload($data, $type)
            ?? $this->currencyFromParent($context)
            ?? $context[self::CURRENCY]
            ?? null;

        return $this->denormalizer->denormalize($data, $type, $format, $context);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return ($context[ApiVersion::DECIMAL_MONEY] ?? false)
            && ! isset($context[self::ALREADY_CALLED][$type])
            && $this->isCurrencyClass($type);
    }

    public function getSupportedTypes(?string $format): array
    {
        return ['object' => false];
    }

    private function isCurrencyClass(string $class): bool
    {
        return array_any(self::CURRENCY_CLASSES, fn ($currencyClass) => is_a($class, $currencyClass, true));
    }

    private function currencyOf(object $record): ?string
    {
        try {
            return match (true) {
                $record instanceof HasFrozenCurrencyInterface => $record->resolveCurrencyCode(),
                // Before InvoiceLine, which RecurringInvoiceLine extends.
                $record instanceof RecurringInvoiceLine => $record->getRecurringInvoice()?->resolveCurrencyCode(),
                $record instanceof InvoiceLine => $record->getInvoice()?->resolveCurrencyCode(),
                $record instanceof QuoteLine => $record->getQuote()->resolveCurrencyCode(),
                $record instanceof Payment => $record->getCurrencyCode(),
                $record instanceof Client => $record->getCurrencyCode(),
                default => null,
            };
        } catch (Throwable) {
            // A line not yet attached to its document (typed property still uninitialised).
            return null;
        }
    }

    /**
     * A new client states its currency; a new document names its client.
     */
    private function currencyFromPayload(mixed $data, string $type): ?string
    {
        if (! is_array($data)) {
            return null;
        }

        if (is_a($type, Client::class, true)) {
            return is_string($data['currencyCode'] ?? null) ? $data['currencyCode'] : null;
        }

        if (! is_a($type, HasFrozenCurrencyInterface::class, true) || ! is_string($data['client'] ?? null)) {
            return null;
        }

        try {
            $client = $this->iriConverter->getResourceFromIri($data['client']);
        } catch (Throwable) {
            // The client reference is reported by the normal denormalization.
            return null;
        }

        return $client instanceof Client ? $client->getCurrencyCode() : null;
    }

    /**
     * A record posted under its document (`/invoices/{invoiceId}/lines`) takes that document's currency.
     *
     * @param array<string, mixed> $context
     */
    private function currencyFromParent(array $context): ?string
    {
        $operation = $context['operation'] ?? null;

        if (! $operation instanceof HttpOperation) {
            return null;
        }

        foreach ($operation->getUriVariables() ?? [] as $name => $link) {
            $class = $link->getFromClass();
            $id = $context['uri_variables'][$name] ?? null;

            if ($class === null || $id === null || ! is_a($class, HasFrozenCurrencyInterface::class, true)) {
                continue;
            }

            $parent = $this->registry->getManagerForClass($class)?->find($class, $id);

            if ($parent instanceof HasFrozenCurrencyInterface) {
                return $parent->resolveCurrencyCode();
            }
        }

        return null;
    }
}
