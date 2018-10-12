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

namespace SolidInvoice\NotificationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table("notifications")
 */
class Notification
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="notification_event", type="string", unique=true)
     */
    private $event;

    /**
     * @var bool
     *
     * @ORM\Column(name="email", type="boolean")
     */
    private $email;

    /**
     * @var bool
     *
     * @ORM\Column(name="sms", type="boolean")
     */
    private $sms;

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEvent(): ?string
    {
        return $this->event;
    }

    /**
     * @param string $event
     *
     * @return Notification
     */
    public function setEvent(string $event): self
    {
        $this->event = $event;

        return $this;
    }

    /**
     * @return bool
     */
    public function getEmail(): bool
    {
        return $this->email;
    }

    /**
     * @param bool $email
     *
     * @return Notification
     */
    public function setEmail(bool $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return bool
     */
    public function getSms(): bool
    {
        return $this->sms;
    }

    /**
     * @param bool $sms
     *
     * @return Notification
     */
    public function setSms(bool $sms): self
    {
        $this->sms = $sms;

        return $this;
    }
}
