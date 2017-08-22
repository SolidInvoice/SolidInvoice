<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Notification;

use SolidInvoice\NotificationBundle\Notification\NotificationMessage;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;

class InvoiceStatusNotification extends NotificationMessage
{
    const HTML_TEMPLATE = 'SolidInvoiceInvoiceBundle:Email:status_change.html.twig';

    const TEXT_TEMPLATE = 'SolidInvoiceInvoiceBundle:Email:status_change.text.twig';

    /**
     * {@inheritdoc}
     */
    public function getHtmlContent(EngineInterface $templating): string
    {
        return $templating->render(self::HTML_TEMPLATE, $this->getParameters());
    }

    /**
     * {@inheritdoc}
     */
    public function getTextContent(EngineInterface $templating): string
    {
        return $templating->render(self::TEXT_TEMPLATE, $this->getParameters());
    }

    /**
     * @param TranslatorInterface $translator
     *
     * @return string
     */
    public function getSubject(TranslatorInterface $translator): string
    {
        return $translator->trans('invoice.status.subject', [], 'email');
    }
}
