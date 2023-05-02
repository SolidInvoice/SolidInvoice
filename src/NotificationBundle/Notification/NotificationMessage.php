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

namespace SolidInvoice\NotificationBundle\Notification;

abstract class NotificationMessage implements NotificationMessageInterface
{
    private array $users = [];

    public function __construct(private array $parameters = [])
    {
    }

    public function setUsers(array $users)
    {
        $this->users = $users;

        return $this;
    }

    public function getUsers(): array
    {
        return $this->users;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }
}
