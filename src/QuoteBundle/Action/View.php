<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\QuoteBundle\Action;

use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\MailerBundle\Mailer;
use SolidInvoice\QuoteBundle\Email\QuoteEmail;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Symfony\Component\HttpFoundation\Request;

final class View
{
    public function __invoke(Request $request, Mailer $mailer, Quote $quote)
    {
        $mailer->send(new QuoteEmail($quote));

        return new Template('@SolidInvoiceQuote/Default/view.html.twig', ['quote' => $quote]);
    }
}
