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

use CSBill\CoreBundle\Templating\Template;
use CSBill\QuoteBundle\Entity\Quote;
use Symfony\Component\HttpFoundation\Request;

final class View
{
    public function __invoke(Request $request, Quote $quote)
    {
        return new Template('@CSBillQuote/Default/view.html.twig', ['quote' => $quote]);
    }
}
