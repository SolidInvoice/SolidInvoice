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

namespace SolidInvoice\QuoteBundle\Action\Transition;

use Generator;
use JsonException;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Exception\InvalidTransitionException;
use SolidInvoice\QuoteBundle\Mailer\QuoteMailer;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\RouterInterface;

final class Send
{
    private QuoteMailer $mailer;

    private RouterInterface $router;

    public function __construct(QuoteMailer $mailer, RouterInterface $router)
    {
        $this->mailer = $mailer;
        $this->router = $router;
    }

    public function __invoke(Request $request, Quote $quote): RedirectResponse
    {
        $route = $this->router->generate('_quotes_view', ['id' => $quote->getId()]);

        try {
            $this->mailer->send($quote);
        } catch (JsonException | InvalidTransitionException | TransportExceptionInterface $e) {
            return new class($route, $e->getMessage()) extends RedirectResponse implements FlashResponse {
                private string $message;

                public function __construct(string $route, string $message)
                {
                    parent::__construct($route);
                    $this->message = $message;
                }

                public function getFlash(): Generator
                {
                    yield self::FLASH_ERROR => $this->message;
                }
            };
        }

        return new class($route) extends RedirectResponse implements FlashResponse {
            public function getFlash(): Generator
            {
                yield self::FLASH_SUCCESS => 'quote.transition.action.sent';
            }
        };
    }
}
