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

namespace SolidInvoice\SaasBundle\EventSubscriber;

use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use SolidInvoice\CoreBundle\Contracts\ClientEmailInterface;
use SolidInvoice\InvoiceBundle\Email\InvoiceEmail;
use SolidInvoice\InvoiceBundle\Email\InvoiceReminderEmail;
use SolidInvoice\InvoiceBundle\Email\ManualInvoiceReminderEmail;
use SolidInvoice\QuoteBundle\Email\QuoteEmail;
use SolidInvoice\SaasBundle\Entity\HeldEmail;
use SolidInvoice\SaasBundle\Service\ClientEmailRisk;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use function array_keys;
use function array_map;
use function function_exists;
use function implode;
use function Sentry\captureMessage;
use function sprintf;

/**
 * Holds a new account's invoice and quote emails for the operator instead of delivering them,
 * when they look like a callback scam or the account goes past its daily send cap. The harm is
 * our domain delivering the email, so this is where it is stopped: one place every sender (web,
 * API, MCP, reminders, recurring) goes through. Held emails are released or rejected with
 * `solidinvoice:saas:held-emails`.
 *
 * Nothing tells the sender. The invoice reads as sent either way, so a scammer learns nothing,
 * and a real business's email goes out once it is released.
 *
 * @see \SolidInvoice\SaasBundle\Tests\EventSubscriber\HoldNewAccountClientEmailListenerTest
 */
final readonly class HoldNewAccountClientEmailListener
{
    // Scammers sign up and send the same day. Waiting a week out costs them the throwaway account.
    private const string NEW_ACCOUNT = '-7 days';

    public function __construct(
        private ClientEmailRisk $risk,
        private RateLimiterFactoryInterface $newAccountClientEmailLimiter,
        private ManagerRegistry $registry,
        private ClockInterface $clock,
        private LoggerInterface $logger,
    ) {
    }

    // After every listener that fills the message in (template, PDF, subject, From), so what is
    // held is exactly what would have been sent.
    #[AsEventListener(MessageEvent::class, priority: -1024)]
    public function __invoke(MessageEvent $event): void
    {
        $message = $event->getMessage();

        // A queued message gets this event again from the transport that delivers it; decide there.
        if ($event->isQueued() || ! $message instanceof Email || ! $message instanceof ClientEmailInterface) {
            return;
        }

        $document = match (true) {
            $message instanceof InvoiceEmail, $message instanceof InvoiceReminderEmail, $message instanceof ManualInvoiceReminderEmail => $message->getInvoice(),
            $message instanceof QuoteEmail => $message->getQuote(),
            default => null,
        };

        if (null === $document) {
            return;
        }

        $company = $document->getCompany();
        $created = $company->getCreated();

        if (! $created instanceof DateTimeImmutable || $created < $this->clock->now()->modify(self::NEW_ACCOUNT)) {
            return;
        }

        $signals = $this->risk->signals($document, (string) $message->getSubject());

        if (! ClientEmailRisk::isRisky($signals)) {
            if ($this->newAccountClientEmailLimiter->create($company->getId()->toBase58())->consume()->isAccepted()) {
                return;
            }

            $signals['daily_limit'] = 0;
        }

        $envelope = $event->getEnvelope();

        $held = new HeldEmail(
            $company,
            $envelope->getSender()->toString(),
            array_map(static fn (Address $address): string => $address->toString(), $envelope->getRecipients()),
            (string) $message->getSubject(),
            $message->toString(),
            $signals,
            $this->clock->now(),
        );

        $entityManager = $this->registry->getManagerForClass(HeldEmail::class);
        $entityManager->persist($held);
        $entityManager->flush();

        $event->reject();

        $summary = sprintf('Client email held for review: %s (%s)', $company->getName(), implode(', ', array_keys($signals)));

        // Sentry is how the operator hears about it; the log keeps the ids.
        if (function_exists('Sentry\\captureMessage')) {
            captureMessage($summary);
        }

        $this->logger->warning($summary, [
            'held_email_id' => $held->getId()->toBase58(),
            'company_id' => $company->getId()->toBase58(),
            'signals' => $signals,
        ]);
    }
}
