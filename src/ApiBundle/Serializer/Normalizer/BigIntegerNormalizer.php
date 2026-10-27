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

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Money\Currency;
use SolidInvoice\ApiBundle\Versioning\ApiVersion;
use SolidInvoice\MoneyBundle\Currency\CurrencyScale;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use function is_a;
use function sprintf;

/**
 * @see \SolidInvoice\ApiBundle\Tests\Serializer\Normalizer\BigIntegerNormalizerTest
 */
#[AutoconfigureTag('serializer.normalizer')]
final readonly class BigIntegerNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * Context flag marking whether the {@see BigNumber} being (de)normalized is a monetary
     * amount held in the minor unit, and therefore needs scaling by 100 across the API
     * boundary. Defaults to true, since most of them are.
     *
     * Set it to false with {@see \Symfony\Component\Serializer\Attribute\Context} on any
     * `BigNumber` property that is a plain number — a line quantity, for instance, which
     * is exposed exactly as it is stored.
     */
    public const string MONETARY = 'monetary_amount';

    public function __construct(
        private CurrencyScale $currencyScale = new CurrencyScale(),
    ) {
    }

    /**
     * @throws MathException
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): BigNumber
    {
        // JSON numbers arrive as floats. `(string)` would serialise them at PHP's `precision`
        // ini setting, so the same payload could denormalize differently from one host to
        // the next; %.14G pins that to the historical default and makes it deterministic.
        $data = is_float($data) ? sprintf('%.14G', $data) : $data;

        if ($context[ApiVersion::DECIMAL_MONEY] ?? false) {
            return $this->denormalizeDecimal($data, $context);
        }

        if (($context['api_denormalize'] ?? false) && ($context[self::MONETARY] ?? true)) {
            return BigNumber::of($data)->toBigDecimal()->multipliedBy(100);
        }

        return BigNumber::of($data);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_a($type, BigNumber::class, true);
    }

    /**
     * @param BigNumber $object
     * @param array<string, mixed> $context
     * @throws MathException
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): float | string
    {
        if ($context[ApiVersion::DECIMAL_MONEY] ?? false) {
            if ($context[self::MONETARY] ?? true) {
                $subunit = $this->subunit($context);

                return (string) $object->toBigDecimal()->dividedBy(10 ** $subunit, $subunit, RoundingMode::HalfEven);
            }

            return (string) $object->toBigDecimal();
        }

        if (isset($context['api_attribute']) && ($context[self::MONETARY] ?? true)) {
            return $object->toBigDecimal()->dividedBy(100, 2, RoundingMode::HalfEven)->toFloat();
        }

        return $object->toBigDecimal()->toFloat();
    }

    /**
     * Decimal money arrives in major units, as a number or a numeric string, and is stored in the
     * currency's minor unit. More precision than the currency has is rejected rather than rounded.
     * UnexpectedValueException, not NotNormalizableValueException: the serializer would treat the
     * latter as "try the next type" and fall through to the setters' plain `string` type.
     *
     * @param array<string, mixed> $context
     */
    private function denormalizeDecimal(mixed $data, array $context): BigNumber
    {
        $currency = $context[MoneyCurrencyNormalizer::CURRENCY] ?? null;

        try {
            $value = BigDecimal::of($data);

            if (! ($context['api_denormalize'] ?? false) || ! ($context[self::MONETARY] ?? true)) {
                return $value;
            }

            return $value->multipliedBy(10 ** $this->subunit($context))->toScale(0)->toBigInteger();
        } catch (RoundingNecessaryException) {
            throw new UnexpectedValueException(sprintf('The amount "%s" has more decimal places than %s allows.', $data, $currency ?? 'the currency'));
        } catch (MathException) {
            throw new UnexpectedValueException(sprintf('"%s" is not a decimal number.', is_scalar($data) ? $data : get_debug_type($data)));
        }
    }

    /**
     * The currency's own number of decimals. A record without a resolvable currency falls back
     * to two, as {@see CurrencyScale} does for an unrecognised code.
     *
     * @param array<string, mixed> $context
     */
    private function subunit(array $context): int
    {
        $code = $context[MoneyCurrencyNormalizer::CURRENCY] ?? null;

        if (! is_string($code) || $code === '') {
            return CurrencyScale::DEFAULT_SUBUNIT;
        }

        return $this->currencyScale->subunitFor(new Currency($code));
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof BigNumber;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            BigNumber::class => true,
        ];
    }
}
