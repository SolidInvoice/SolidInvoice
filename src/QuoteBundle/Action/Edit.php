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

namespace CSBill\QuoteBundle\Action;

use CSBill\QuoteBundle\Entity\Quote;
use CSBill\QuoteBundle\Form\Handler\QuoteEditHandler;
use Money\Currency;
use SolidWorx\FormHandler\FormHandler;
use Symfony\Component\HttpFoundation\Request;

class Edit
{
    /**
     * @var FormHandler
     */
    private $handler;

    /**
     * @var Currency
     */
    private $currency;

    public function __construct(FormHandler $handler, Currency $currency)
    {
        $this->handler = $handler;
        $this->currency = $currency;
    }

    public function __invoke(Request $request, Quote $quote)
    {
        $currency = $quote->getClient()->getCurrency() ?: $this->currency;

        return $this->handler->handle(QuoteEditHandler::class, $quote, ['currency' => $currency]);
    }
}
