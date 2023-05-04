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

namespace SolidInvoice\ClientBundle\Notification;

use SolidInvoice\NotificationBundle\Notification\NotificationMessage;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class ClientCreateNotification extends NotificationMessage
{
    final public const HTML_TEMPLATE = '@SolidInvoiceClient/Email/client_create.html.twig';

    final public const TEXT_TEMPLATE = '@SolidInvoiceClient/Email/client_create.text.twig';

    public function getHtmlContent(Environment $twig): string
    {
        return $twig->render(self::HTML_TEMPLATE, $this->getParameters());
    }

    public function getTextContent(Environment $twig): string
    {
        return $twig->render(self::TEXT_TEMPLATE, $this->getParameters());
    }

    public function getSubject(TranslatorInterface $translator): string
    {
        return $translator->trans('client.create.subject', [], 'email');
    }
}
