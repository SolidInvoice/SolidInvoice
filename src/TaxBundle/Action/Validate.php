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
use SolidInvoice\CoreBundle\Response\AjaxResponse;
use SolidInvoice\CoreBundle\Traits\JsonTrait;
use Symfony\Component\HttpFoundation\Request;

final class Validate implements AjaxResponse
{
    use JsonTrait;

    private VatCalculator $calculator;

    public function __construct(VatCalculator $calculator)
    {
        $this->calculator = $calculator;
    }

    public function __invoke(Request $request)
    {
        try {
            $valid = $this->calculator->isValidVATNumber($request->request->get('vat_number'));
        } catch (VATCheckUnavailableException $e) {
            $valid = false;
        }

        return $this->json(['valid' => $valid]);
    }
}
