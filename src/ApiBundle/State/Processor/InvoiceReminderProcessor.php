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

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use Carbon\Carbon;
use Psr\Log\LoggerInterface;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\InvoiceBundle\Email\ManualInvoiceReminderEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidWorx\Platform\PlatformBundle\Security\EmailVerification\EmailVerificationGateInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Contracts\Translation\TranslatorInterface;
use function array_values;
use function in_array;
use function max;
use function sprintf;

/**
 * The manual "remind the client" email, as the web action and the MCP tool send it, limited to
 * one per invoice per hour.
 *
 * @implements ProcessorInterface<Invoice, Invoice>
 * @see \SolidInvoice\InvoiceBundle\Tests\Functional\Api\InvoiceSendTest
 */
final readonly class InvoiceReminderProcessor implements ProcessorInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private EmailVerificationGateInterface $emailVerificationGate,
        private RateLimiterFactoryInterface $invoiceManualReminderLimiter,
        private TranslatorInterface $translator,
        private LoggerInterface $logger,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Invoice
    {
        assert($data instanceof Invoice);

        if (! in_array($data->getStatus(), [InvoiceStatus::Pending, InvoiceStatus::Overdue], true)) {
            throw new ConflictHttpException(sprintf('A reminder cannot be sent for an invoice in status "%s".', $data->getStatus()->value ?? 'unknown'));
        }

        if ($this->emailVerificationGate->isGated()) {
            throw new AccessDeniedHttpException($this->emailVerificationGate->reason('send a reminder'));
        }

        if ($data->getUsers()->isEmpty()) {
            throw new ValidationException(new ConstraintViolationList([
                new ConstraintViolation($this->translator->trans('document.send.no_recipients', [], 'validators'), null, [], null, 'users', null, null, 'no_recipients'),
            ]));
        }

        $limit = $this->invoiceManualReminderLimiter->create((string) $data->getId())->consume();

        if (! $limit->isAccepted()) {
            throw new TooManyRequestsHttpException(max(1, $limit->getRetryAfter()->getTimestamp() - Carbon::now()->getTimestamp()), 'A reminder was already sent for this invoice in the last hour.');
        }

        try {
            $this->mailer->send(new ManualInvoiceReminderEmail($data));
        } catch (TransportExceptionInterface $exception) {
            $this->logger->error('Failed to send manual reminder', ['invoice_id' => $data->getInvoiceId(), 'exception' => $exception]);

            throw new HttpException(Response::HTTP_BAD_GATEWAY, 'The email could not be sent.', $exception);
        }

        $data->setSentTo(array_values($data->getUsers()->map(static fn (Contact $contact): string => (string) $contact->getEmail())->toArray()));

        return $data;
    }
}
