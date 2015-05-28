<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace CSBill\NotificationBundle\Notification;

use CSBill\UserBundle\Entity\User;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;

interface NotificationMessageInterface
{
    /**
     * @param EngineInterface $templating
     *
     * @return string
     */
    public function getHtmlContent(EngineInterface $templating = null);

    /**
     * @param EngineInterface $templating
     *
     * @return string
     */
    public function getTextContent(EngineInterface $templating = null);

    /**
     * @param TranslatorInterface $translator
     *
     * @return string
     */
    public function getSubject(TranslatorInterface $translator = null);

    /**
     * @param array $users
     */
    public function setUsers(array $users);

    /**
     * @return User[]
     */
    public function getUsers();

    /**
     * @return array
     */
    public function getParameters();

    /**
     * @param array $parameters
     */
    public function setParameters(array $parameters);
}
