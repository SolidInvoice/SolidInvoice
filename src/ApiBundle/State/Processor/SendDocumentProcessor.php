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

namespace SolidInvoice\ApiBundle\State\Processor;

use ApiPlatform\Metadata\Exception\ExceptionInterface as ApiPlatformException;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use Carbon\Carbon;
use Doctrine\Persistence\ManagerRegistry;
use JsonException;
use Psr\Log\LoggerInterface;
use SolidInvoice\ApiBundle\DTO\SendDocumentInput;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Enum\SendChannel;
use SolidInvoice\CoreBundle\Event\DocumentSentEvent;
use SolidInvoice\CoreBundle\Mailer\DocumentSendOptions;
use SolidInvoice\InvoiceBundle\Email\InvoiceEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Model\Graph as InvoiceGraph;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\QuoteBundle\Exception\InvalidTransitionException;
use SolidInvoice\QuoteBundle\Mailer\QuoteMailer;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\PlatformBundle\Security\EmailVerification\EmailVerificationGateInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use function array_map;
use function array_unique;
use function array_values;
use function count;
use function max;
use function sprintf;
use function strtolower;
use function trim;

/**
 * Emails an invoice or quote to the client: the first send moves it to pending (invoice `accept`,
 * quote `send`), a send while pending (or overdue) re-sends without a transition.
 *
 * @implements ProcessorInterface<SendDocumentInput, Invoice|Quote>
 * @see \SolidInvoice\InvoiceBundle\Tests\Functional\Api\InvoiceSendTest
 * @see \SolidInvoice\QuoteBundle\Tests\Functional\Api\QuoteSendTest
 */
final readonly class SendDocumentProcessor implements ProcessorInterface
{
    public function __construct(
        private ManagerRegistry $registry,
        private WorkflowInterface $invoiceStateMachine,
        private MailerInterface $mailer,
        private QuoteMailer $quoteMailer,
        private IriConverterInterface $iriConverter,
        private EmailVerificationGateInterface $emailVerificationGate,
        private Security $security,
        private EventDispatcherInterface $eventDispatcher,
        private TranslatorInterface $translator,
        private LoggerInterface $logger,
        private RateLimiterFactoryInterface $documentSendLimiter,
        private RateLimiterFactoryInterface $documentSendRecipientsLimiter,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Invoice | Quote
    {
        assert($data instanceof SendDocumentInput);

        $class = $operation->getClass();
        assert(Invoice::class === $class || Quote::class === $class);

        $document = $this->registry->getRepository($class)->findOneBy(['id' => $uriVariables['id'] ?? null]);

        if (! $document instanceof Invoice && ! $document instanceof Quote) {
            throw new NotFoundHttpException(sprintf('"%s" not found.', $uriVariables['id'] ?? ''));
        }

        $firstSend = $this->isFirstSend($document);

        $contacts = null === $data->to ? $document->getUsers()->toArray() : $this->resolveContacts($data->to, $document);

        // Naming recipients on the first send makes them the document's contacts; a re-send only targets that send.
        if ($firstSend && null !== $data->to) {
            foreach ($document->getUsers()->toArray() as $user) {
                $document->removeUser($user);
            }

            // Restart the keys, or the contacts serialise as a JSON object.
            $document->getUsers()->clear();

            foreach ($contacts as $contact) {
                $document->addUser($contact);
            }
        }

        $to = array_values(array_map(
            static fn (Contact $contact): Address => new Address((string) $contact->getEmail(), trim(sprintf('%s %s', $contact->getFirstName(), $contact->getLastName()))),
            $contacts,
        ));

        if ([] === $to) {
            throw new ValidationException(new ConstraintViolationList([
                new ConstraintViolation($this->translator->trans('document.send.no_recipients', [], 'validators'), null, [], null, 'to', $data->to, null, 'no_recipients'),
            ]));
        }

        $recipients = array_values(array_unique([...array_map(static fn (Address $address): string => $address->getAddress(), $to), ...$data->cc]));

        $user = $this->security->getUser();
        $userEmail = $user instanceof User ? $user->getEmail() : null;

        $this->denyUnverifiedSend($recipients, $userEmail, $document);

        $recipientCount = count($recipients) + ($data->copyToMe && null !== $userEmail ? 1 : 0);

        $this->consume($this->documentSendLimiter->create(sprintf('%s:%s', $class, $document->getId())), 1);
        $this->consume($this->documentSendRecipientsLimiter->create($document->getCompany()->getId()->toBase32()), $recipientCount);

        $options = new DocumentSendOptions(
            to: $to,
            cc: $data->cc,
            subject: $data->subject,
            personalNote: $data->message,
            attachPdf: $data->attachPdf,
            replyTo: $userEmail,
            copyTo: $data->copyToMe ? $userEmail : null,
        );

        try {
            if ($document instanceof Invoice) {
                if ($firstSend) {
                    $this->invoiceStateMachine->apply($document, InvoiceGraph::TRANSITION_ACCEPT);
                }

                // Mailed before the flush, so an invoice whose email failed stays unsent.
                $this->mailer->send(new InvoiceEmail($document, $options));
            } else {
                // From draft this applies `send`, whose workflow listener sends the one email.
                $this->quoteMailer->send($document, $options);
            }
        } catch (TransportExceptionInterface | InvalidTransitionException | JsonException $exception) {
            $this->logger->error('Failed to send document email: ' . $exception->getMessage(), ['exception' => $exception]);

            throw new HttpException(Response::HTTP_BAD_GATEWAY, 'The email could not be sent.', $exception);
        }

        $this->registry->getManager()->flush();

        $this->eventDispatcher->dispatch(new DocumentSentEvent($document, SendChannel::Email, $recipients));

        $document->setSentTo($recipients);

        return $document;
    }

    private function isFirstSend(Invoice | Quote $document): bool
    {
        $status = $document->getStatus();

        $first = $document instanceof Invoice
            ? match ($status) {
                InvoiceStatus::New, InvoiceStatus::Draft => true,
                InvoiceStatus::Pending, InvoiceStatus::Overdue => false,
                default => null,
            }
        : match ($status) {
            QuoteStatus::Draft => true,
            QuoteStatus::Pending => false,
            default => null,
        };

        if (null === $first) {
            throw new ConflictHttpException(sprintf('A document in status "%s" cannot be sent.', $status->value ?? 'unknown'));
        }

        return $first;
    }

    /**
     * @param list<string> $iris
     *
     * @return list<Contact>
     */
    private function resolveContacts(array $iris, Invoice | Quote $document): array
    {
        $contacts = [];

        foreach ($iris as $i => $iri) {
            try {
                $contact = $this->iriConverter->getResourceFromIri($iri);
            } catch (ApiPlatformException) {
                $contact = null;
            }

            // The provider runs through the company filter, so another company's contact is not found either.
            if (! $contact instanceof Contact || $contact->getClient() !== $document->getClient()) {
                throw new ValidationException(new ConstraintViolationList([
                    new ConstraintViolation($this->translator->trans('document.send.unknown_contact', [], 'validators'), null, [], null, sprintf('to[%d]', $i), $iri, null, 'unknown_contact'),
                ]));
            }

            $contacts[] = $contact;
        }

        return $contacts;
    }

    private function consume(LimiterInterface $limiter, int $tokens): void
    {
        $limit = $limiter->consume($tokens);

        if (! $limit->isAccepted()) {
            throw new TooManyRequestsHttpException(max(1, $limit->getRetryAfter()->getTimestamp() - Carbon::now()->getTimestamp()));
        }
    }

    /**
     * Behind the API firewall's verified-user check. The one send allowed before verification is
     * one that only reaches the user's own address.
     *
     * @param list<string> $recipients
     */
    private function denyUnverifiedSend(array $recipients, ?string $userEmail, Invoice | Quote $document): void
    {
        if (! $this->emailVerificationGate->isGated()) {
            return;
        }

        if (null !== $userEmail && [strtolower($userEmail)] === array_values(array_unique(array_map(strtolower(...), $recipients)))) {
            return;
        }

        throw new AccessDeniedHttpException($this->emailVerificationGate->reason($document instanceof Invoice ? 'send this invoice' : 'send this quote'));
    }
}
