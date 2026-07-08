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

namespace SolidInvoice\QuoteBundle\Listener\Mailer;

use SolidInvoice\CoreBundle\Templates\BillingTemplateChannel;
use SolidInvoice\CoreBundle\Templates\BillingTemplateResolver;
use SolidInvoice\QuoteBundle\Email\QuoteEmail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;

/**
 * Swaps the quote email body to the company's custom design template.
 * Runs at a positive priority so the template is set before the mailer's
 * body renderer (priority 0) turns it into HTML.
 *
 * @see \SolidInvoice\QuoteBundle\Tests\Listener\Mailer\QuoteEmailTemplateListenerTest
 */
readonly class QuoteEmailTemplateListener implements EventSubscriberInterface
{
    public function __construct(
        private BillingTemplateResolver $templateResolver,
    ) {
    }

    public function __invoke(MessageEvent $event): void
    {
        $message = $event->getMessage();

        if (! $message instanceof QuoteEmail) {
            return;
        }

        $template = $this->templateResolver->customTemplate($message->getQuote(), BillingTemplateChannel::Email);

        if (null !== $template) {
            $message->htmlTemplate($template);
        }
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            MessageEvent::class => ['__invoke', 100],
        ];
    }
}
