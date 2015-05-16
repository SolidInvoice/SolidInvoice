<?php
/**
 * This file is part of the CSBill project.
 *
 * @author      MiWay Development Team
 * @copyright   Copyright (c) 2014 MiWay Insurance Ltd
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
     * @var integer
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