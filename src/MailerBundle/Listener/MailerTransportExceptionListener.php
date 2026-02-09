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

namespace SolidInvoice\MailerBundle\Listener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Catches unhandled mailer transport exceptions (e.g. SMTP server unreachable)
 * and converts them into a user-friendly flash error message with a redirect,
 * instead of showing a raw 500 error page.
 *
 * This handles failures for both custom SMTP servers configured via settings
 * and pre-configured transports via the SOLIDINVOICE_MAILER_DSN environment variable.
 */
final class MailerTransportExceptionListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 10],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (! $this->isTransportException($exception)) {
            return;
        }

        $this->logger->error('Failed to send email: ' . $exception->getMessage(), [
            'exception' => $exception,
        ]);

        $request = $event->getRequest();
        $session = $request->getSession();
        assert($session instanceof Session);

        $session->getFlashBag()->add(
            'error',
            'Failed to send email. Please verify your email configuration in Settings and try again.'
        );

        $referer = $request->headers->get('referer');
        $redirectUrl = $referer ?? $request->getUri();

        $event->setResponse(new RedirectResponse($redirectUrl));
        $event->stopPropagation();
    }

    private function isTransportException(\Throwable $exception): bool
    {
        if ($exception instanceof TransportExceptionInterface) {
            return true;
        }

        $previous = $exception->getPrevious();

        if ($previous !== null) {
            return $this->isTransportException($previous);
        }

        return false;
    }
}
