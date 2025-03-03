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

namespace SolidInvoice\TaxBundle\Action;

use Mpociot\VatCalculator\Exceptions\VATCheckUnavailableException;
use Mpociot\VatCalculator\VatCalculator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class Validate extends AbstractController
{
    public function __construct(
        private readonly VatCalculator $calculator
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $valid = $this->calculator->isValidVATNumber($request->request->get('vat_number'));
        } catch (VATCheckUnavailableException) {
            $valid = false;
        }

        return $this->json(['valid' => $valid]);
    }
}
