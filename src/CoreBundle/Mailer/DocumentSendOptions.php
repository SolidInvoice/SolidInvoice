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

namespace SolidInvoice\CoreBundle\Mailer;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

/**
 * Per-send overrides for an invoice or quote email. Anything left null falls back to the
 * listeners' defaults (the document's contacts, the configured subject, the company Reply-To).
 */
final readonly class DocumentSendOptions
{
    /**
     * @param list<Address>|null $to
     * @param list<string>       $cc
     */
    public function __construct(
        public ?array $to = null,
        public array $cc = [],
        public ?string $subject = null,
        public ?string $personalNote = null,
        public bool $attachPdf = true,
        public ?string $replyTo = null,
        public ?string $copyTo = null,
    ) {
    }

    public function applyTo(TemplatedEmail $email): void
    {
        if (null !== $this->to) {
            $email->to(...$this->to);
        }

        if ([] !== $this->cc) {
            $email->cc(...$this->cc);
        }

        if (null !== $this->subject) {
            $email->subject($this->subject);
        }

        if (null !== $this->replyTo) {
            $email->replyTo($this->replyTo);
        }

        if (null !== $this->copyTo) {
            $email->addBcc($this->copyTo);
        }

        $email->context([...$email->getContext(), 'personal_note' => $this->personalNote]);
    }
}
