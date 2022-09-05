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

use SolidInvoice\TaxBundle\Repository\TaxRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TaxExtension extends AbstractExtension
{
    /**
     * @var TaxRepository
     */
    private $repository;

    public function __construct(TaxRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('taxRatesConfigured', function (): bool {
                return $this->taxRatesConfigured();
            }),
        ];
    }

    /**
     * @return true
     */
    public function taxRatesConfigured(): bool
    {
        static $taxConfigured;

        if (null !== $taxConfigured) {
            return $taxConfigured;
        }

        return $this->repository->taxRatesConfigured();
    }

    public function getName()
    {
        return 'tax_extension';
    }
}
