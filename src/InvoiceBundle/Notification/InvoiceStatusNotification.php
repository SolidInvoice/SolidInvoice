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

namespace SolidInvoice\InvoiceBundle\Notification;

use SolidInvoice\NotificationBundle\Notification\NotificationMessage;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class InvoiceStatusNotification extends NotificationMessage
{
    public const HTML_TEMPLATE = '@SolidInvoiceInvoice/Email/status_change.html.twig';

    public const TEXT_TEMPLATE = '@SolidInvoiceInvoice/Email/status_change.text.twig';

    /**
     * {@inheritdoc}
     */
    public function getHtmlContent(Environment $twig): string
    {
        return $twig->render(self::HTML_TEMPLATE, $this->getParameters());
    }

    /**
     * {@inheritdoc}
     */
    public function getTextContent(Environment $twig): string
    {
        return $twig->render(self::TEXT_TEMPLATE, $this->getParameters());
    }

    public function getSubject(TranslatorInterface $translator): string
    {
        return $translator->trans('invoice.status.subject', [], 'email');
    }
}
