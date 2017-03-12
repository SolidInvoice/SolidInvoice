<?php

declare(strict_types=1);
/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\Notification;

use CSBill\NotificationBundle\Notification\NotificationMessage;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;

class QuoteStatusNotification extends NotificationMessage
{
    const HTML_TEMPLATE = 'CSBillQuoteBundle:Email:status_change.html.twig';

    const TEXT_TEMPLATE = 'CSBillQuoteBundle:Email:status_change.text.twig';

    /**
     * {@inheritdoc}
     */
    public function getHtmlContent(EngineInterface $templating)
    {
        return $templating->render(self::HTML_TEMPLATE, $this->getParameters());
    }

    /**
     * {@inheritdoc}
     */
    public function getTextContent(EngineInterface $templating)
    {
        return $templating->render(self::TEXT_TEMPLATE, $this->getParameters());
    }

    /**
     * @param TranslatorInterface $translator
     *
     * @return string
     */
    public function getSubject(TranslatorInterface $translator)
    {
        return $translator->trans('quote.status.subject', [], 'email');
    }
}
