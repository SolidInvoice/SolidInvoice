<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\TaxBundle\Action;

use CSBill\CoreBundle\Response\AjaxResponse;
use CSBill\CoreBundle\Traits\JsonTrait;
use Mpociot\VatCalculator\Exceptions\VATCheckUnavailableException;
use Mpociot\VatCalculator\VatCalculator;
use Symfony\Component\HttpFoundation\Request;

final class Validate implements AjaxResponse
{
    use JsonTrait;

    /**
     * @var VatCalculator
     */
    private $calculator;

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
