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

namespace SolidInvoice\CoreBundle\Export\Serializer\Normalizer;

use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Money;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizes Money and BigNumber values to decimal strings (major units) for export.
 *
 * Money values are divided by the currency's actual subunit exponent (e.g. /100 for
 * USD, /1 for JPY, /1000 for KWD). BigNumber values lack currency context and assume
 * the project's default 2-decimal subunit — this matches how the rest of the app
 * stores amounts as integer cents.
 * @see \SolidInvoice\CoreBundle\Tests\Export\Serializer\Normalizer\ExportMoneyNormalizerTest
 */
final readonly class ExportMoneyNormalizer implements NormalizerInterface
{
    private const int DEFAULT_SUBUNIT_EXPONENT = 2;

    private ISOCurrencies $currencies;

    public function __construct()
    {
        $this->currencies = new ISOCurrencies();
    }

    /**
     * @return array{amount: string, currency: string}|string
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string
    {
        if ($object instanceof Money) {
            return [
                'amount' => self::amountToDecimalString(
                    BigNumber::of($object->getAmount()),
                    $this->subunitExponent($object->getCurrency()),
                ),
                'currency' => $object->getCurrency()->getCode(),
            ];
        }

        assert($object instanceof BigNumber);

        return self::amountToDecimalString($object, self::DEFAULT_SUBUNIT_EXPONENT);
    }

    /**
     * Shared helper for converting a minor-unit BigNumber to a decimal string.
     * Used by GridRowExtractor to keep the conversion arithmetic in one place.
     */
    public static function amountToDecimalString(BigNumber $amount, int $subunitExponent): string
    {
        $divisor = 10 ** $subunitExponent;

        return $amount->toBigDecimal()
            ->dividedBy($divisor, $subunitExponent, RoundingMode::HalfEven)
            ->__toString();
    }

    private function subunitExponent(Currency $currency): int
    {
        if (! $this->currencies->contains($currency)) {
            return self::DEFAULT_SUBUNIT_EXPONENT;
        }

        return $this->currencies->subunitFor($currency);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Money || $data instanceof BigNumber;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Money::class => true,
            BigNumber::class => true,
        ];
    }
}
