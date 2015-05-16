<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Notification;

use CSBill\NotificationBundle\Notification\NotificationMessage;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ClientUpdateNotification extends NotificationMessage
{
    /**
     * {@inheritdoc}
     */
    public function getHtmlContent(EngineInterface $templating = null)
    {
        // TODO: Implement getHtmlContent() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getTextContent(EngineInterface $templating = null)
    {
        // TODO: Implement getTextContent() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject(TranslatorInterface $translator = null)
    {
        return $translator->trans('client.notification.update.subject');
    }
}