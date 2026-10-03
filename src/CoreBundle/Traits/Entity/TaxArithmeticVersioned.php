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

namespace SolidInvoice\CoreBundle\Traits\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\CoreBundle\Enum\TaxArithmeticVersion;

trait TaxArithmeticVersioned
{
    #[ORM\Column(name: 'tax_arithmetic_version', type: Types::SMALLINT, nullable: true, enumType: TaxArithmeticVersion::class)]
    private ?TaxArithmeticVersion $taxArithmeticVersion = null;

    public function getTaxArithmeticVersion(): ?TaxArithmeticVersion
    {
        return $this->taxArithmeticVersion;
    }

    public function setTaxArithmeticVersion(?TaxArithmeticVersion $taxArithmeticVersion): self
    {
        $this->taxArithmeticVersion = $taxArithmeticVersion;

        return $this;
    }

    public function hasPinnedTaxArithmetic(): bool
    {
        return $this->taxArithmeticVersion !== null;
    }
}
