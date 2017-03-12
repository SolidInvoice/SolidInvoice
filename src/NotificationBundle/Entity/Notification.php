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

namespace CSBill\NotificationBundle\Entity;

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
     * @var string
     *
     * @ORM\Column(name="email", type="string")
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="hipchat", type="string")
     */
    private $hipchat;

    /**
     * @var string
     *
     * @ORM\Column(name="sms", type="string")
     */
    private $sms;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @param string $event
     *
     * @return Notification
     */
    public function setEvent($event)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return (bool) $this->email;
    }

    /**
     * @param string $email
     *
     * @return Notification
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getHipchat()
    {
        return (bool) $this->hipchat;
    }

    /**
     * @param string $hipchat
     *
     * @return Notification
     */
    public function setHipchat($hipchat)
    {
        $this->hipchat = $hipchat;

        return $this;
    }

    /**
     * @return string
     */
    public function getSms()
    {
        return (bool) $this->sms;
    }

    /**
     * @param string $sms
     *
     * @return Notification
     */
    public function setSms($sms)
    {
        $this->sms = $sms;

        return $this;
    }
}
