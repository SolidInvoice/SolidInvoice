<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\NotificationBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\NotificationBundle\Repository\UserNotificationRepository;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: UserNotificationRepository::class)]
#[ORM\Table(name: UserNotification::TABLE_NAME)]
class UserNotification
{
    public const TABLE_NAME = 'notification_user_setting';

    use CompanyAware;

    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    private Ulid $id;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $event;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $email;

    /**
     * @var Collection<int, TransportSetting>
     */
    #[ORM\ManyToMany(targetEntity: TransportSetting::class)]
    private Collection $transports;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private User $user;

    public function __construct()
    {
        $this->transports = new ArrayCollection();
    }

    public function getId(): Ulid
    {
        return $this->id;
    }

    public function getEvent(): string
    {
        return $this->event;
    }

    public function setEvent(string $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, TransportSetting>
     */
    public function getTransports(): Collection
    {
        return $this->transports;
    }

    public function addTransport(TransportSetting $transport): self
    {
        if (! $this->transports->contains($transport)) {
            $this->transports[] = $transport;
        }

        return $this;
    }

    public function removeTransport(TransportSetting $transport): self
    {
        $this->transports->removeElement($transport);

        return $this;
    }

    public function __toString(): string
    {
        return $this->event;
    }

    public function isEmail(): bool
    {
        return $this->email;
    }

    public function setEmail(bool $email): self
    {
        $this->email = $email;

        return $this;
    }
}
