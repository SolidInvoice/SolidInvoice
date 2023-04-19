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

namespace SolidInvoice\QuoteBundle\Action;

use Generator;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\QuoteBundle\Cloner\QuoteCloner;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

final class CloneQuote
{
    private QuoteCloner $cloner;

    private RouterInterface $router;

    public function __construct(QuoteCloner $quoteClonercloner, RouterInterface $router)
    {
        $this->cloner = $quoteClonercloner;
        $this->router = $router;
    }

    public function __invoke(Request $request, Quote $quote)
    {
        $newQuote = $this->cloner->clone($quote);

        $route = $this->router->generate('_quotes_view', ['id' => $newQuote->getId()]);

        return new class($route) extends RedirectResponse implements FlashResponse {
            public function getFlash(): Generator
            {
                yield self::FLASH_SUCCESS => 'quote.clone.success';
            }
        };
    }
}
