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

namespace SolidInvoice\TaxBundle\Calculator;

use SolidInvoice\TaxBundle\Enum\RoundingStrategy;

/**
 * Options that govern how a calculation pass is performed.
 *
 * Keep these on a separate object so the calculator's signature stays stable as new
 * knobs (per-currency scale, rounding step, etc.) are added in later stories.
 */
final readonly class CalculationOptions
{
    public function __construct(
        public RoundingStrategy $rounding = RoundingStrategy::HalfEven,
        public int $scale = 0,
        public bool $persistAmounts = false,
    ) {
    }

    public static function defaults(): self
    {
        return new self();
    }

    public function withRounding(RoundingStrategy $rounding): self
    {
        return new self($rounding, $this->scale, $this->persistAmounts);
    }

    public function withScale(int $scale): self
    {
        return new self($this->rounding, $scale, $this->persistAmounts);
    }

    public function withPersistAmounts(bool $persist): self
    {
        return new self($this->rounding, $this->scale, $persist);
    }
}
