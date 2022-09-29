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

namespace SolidInvoice\TaxBundle\Twig\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;
use SolidInvoice\TaxBundle\Entity\Tax;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TaxExtension extends AbstractExtension
{
    private ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('taxRatesConfigured', function (): bool {
                return $this->taxRatesConfigured();
            }),
        ];
    }

    public function taxRatesConfigured(): bool
    {
        static $taxConfigured;

        return $taxConfigured ?? ($taxConfigured = $this->registry->getRepository(Tax::class)->taxRatesConfigured());
    }
}
