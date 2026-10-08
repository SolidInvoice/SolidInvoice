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

use Brick\Math\BigNumber;
use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Converts a {@see Discount} with {@see Discount::TYPE_MONEY} between the major unit a
 * user types (`359.20`) and the minor unit the entity stores (`35920`). {@see DiscountType}
 * attaches this only for a money discount; a percentage passes through unscaled (SOL-342).
 *
 * The `100` is hard-coded rather than read from {@see \SolidInvoice\MoneyBundle\Currency\CurrencyScale},
 * so it is wrong for a currency with a different minor-unit scale, such as JPY (0) or BHD
 * (3) — the same defect class as SOL-331. That is deliberate for this fix: correcting it
 * would move stored values for those currencies with no migration to match, turning a
 * tight fix into a data-divergence problem. Left for a dedicated change.
 *
 * @implements DataTransformerInterface<BigNumber, float>
 * @see \SolidInvoice\CoreBundle\Tests\Form\Transformer\DiscountTransformerTest
 */
class DiscountTransformer implements DataTransformerInterface
{
    /**
     * @throws DivisionByZeroException
     * @throws RoundingNecessaryException
     * @throws MathException
     * @throws NumberFormatException
     */
    public function transform(mixed $value): float
    {
        if ($value === null) {
            return 0.0;
        }

        $value = is_float($value) ? (string) $value : $value;

        return BigNumber::of($value)->toBigDecimal()->dividedBy(100, 2, RoundingMode::HalfEven)->toFloat();
    }

    /**
     * @throws TransformationFailedException
     */
    public function reverseTransform(mixed $value): BigNumber
    {
        if ($value === null || $value === '') {
            return BigNumber::of('0.0');
        }

        $value = is_float($value) ? (string) $value : $value;

        try {
            return BigNumber::of($value)
                ->toBigDecimal()
                ->multipliedBy(100)
            ;
        } catch (MathException $e) {
            throw new TransformationFailedException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
