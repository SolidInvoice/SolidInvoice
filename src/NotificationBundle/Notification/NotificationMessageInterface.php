<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
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
    public function getHtmlContent(EngineInterface $templating): string;

    /**
     * @param EngineInterface $templating
     *
     * @return string
     */
    public function getTextContent(EngineInterface $templating): string;

    /**
     * @param TranslatorInterface $translator
     *
     * @return string
     */
    public function getSubject(TranslatorInterface $translator): string;

    /**
     * @param array $users
     */
    public function setUsers(array $users);

    /**
     * @return User[]
     */
    public function getUsers(): array;

    /**
     * @return array
     */
    public function getParameters(): array;

    /**
     * @param array $parameters
     */
    public function setParameters(array $parameters);
}
