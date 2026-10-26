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

namespace SolidInvoice\CoreBundle\Export\Email;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ExportReadyEmailSubjectListener
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    #[AsEventListener]
    public function __invoke(MessageEvent $event): void
    {
        $message = $event->getMessage();

        if ($message instanceof ExportReadyEmail && null === $message->getSubject()) {
            $message->subject($this->translator->trans('export.email.ready'));
        }
    }
}
