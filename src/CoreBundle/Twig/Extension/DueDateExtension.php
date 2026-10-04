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

namespace SolidInvoice\CoreBundle\Twig\Extension;

use DateTimeInterface;
use SolidInvoice\CoreBundle\Date\DueDateCalculator;
use Twig\Attribute\AsTwigFunction;

/**
 * @see \SolidInvoice\CoreBundle\Tests\Twig\Extension\DueDateExtensionTest
 */
final readonly class DueDateExtension
{
    public function __construct(
        private DueDateCalculator $calculator,
    ) {
    }

    #[AsTwigFunction(name: 'days_until_due')]
    public function daysUntilDue(?DateTimeInterface $due): ?int
    {
        return $this->calculator->daysUntilDue($due);
    }
}
