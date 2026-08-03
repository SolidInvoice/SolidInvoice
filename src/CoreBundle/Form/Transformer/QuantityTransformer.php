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

namespace SolidInvoice\CoreBundle\Form\Transformer;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;
use Locale;
use NumberFormatter;
use SolidInvoice\CoreBundle\Doctrine\Type\QuantityType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use function is_string;
use function str_replace;
use function trim;

/**
 * View transformer for line quantities, replacing the float-based one that
 * {@see \Symfony\Component\Form\Extension\Core\Type\NumberType} installs by default.
 *
 * A quantity is stored as an exact decimal ({@see QuantityType}), and routing it through
 * `NumberToLocalizedStringTransformer` would push it through a float on every render and
 * every submit — which is exactly what this whole column change exists to remove. Going
 * straight between the string in the input and the {@see BigNumber} on the entity keeps
 * every digit the user typed.
 *
 * Quantities are never grouped, so both `.` and `,` are accepted as the decimal separator
 * regardless of locale (matching what `NumberToLocalizedStringTransformer` does when
 * `grouping` is off), and only the locale's separator is used when rendering.
 *
 * @implements DataTransformerInterface<BigNumber, string>
 * @see \SolidInvoice\CoreBundle\Tests\Form\Transformer\QuantityTransformerTest
 */
final readonly class QuantityTransformer implements DataTransformerInterface
{
    public function __construct(
        private int $scale = QuantityType::SCALE,
    ) {
    }

    /**
     * @param mixed $value the form hands over whatever the model holds, unchecked
     */
    public function transform(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (! $value instanceof BigNumber) {
            throw new TransformationFailedException('Expected a BigNumber.');
        }

        try {
            $decimal = $value->toBigDecimal()
                ->toScale($this->scale, RoundingMode::HalfEven)
                ->strippedOfTrailingZeros();
        } catch (MathException $e) {
            throw new TransformationFailedException($e->getMessage(), $e->getCode(), $e);
        }

        return str_replace('.', $this->decimalSeparator(), (string) $decimal);
    }

    /**
     * @param mixed $value submitted data is a string for a scalar field, but an array when
     *                     the field is submitted as one, so it has to be checked
     */
    public function reverseTransform(mixed $value): ?BigNumber
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        // Non-breaking and narrow non-breaking spaces come back from browsers that
        // auto-format the field; plain spaces are used as a group separator in some locales.
        $value = trim(str_replace(["\xc2\xa0", "\xe2\x80\xaf", ' '], '', $value));

        if ($value === '') {
            return null;
        }

        $value = str_replace(',', '.', $value);

        try {
            return BigDecimal::of($value)
                ->toScale($this->scale, RoundingMode::HalfUp)
                ->strippedOfTrailingZeros();
        } catch (MathException $e) {
            throw new TransformationFailedException($e->getMessage(), $e->getCode(), $e);
        }
    }

    private function decimalSeparator(): string
    {
        $separator = new NumberFormatter(Locale::getDefault(), NumberFormatter::DECIMAL)
            ->getSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);

        return $separator === false ? '.' : $separator;
    }
}
