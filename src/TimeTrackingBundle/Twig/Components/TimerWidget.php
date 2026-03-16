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

namespace SolidInvoice\TimeTrackingBundle\Twig\Components;

use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Enum\ClientStatus;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\TimeTrackingBundle\Entity\Timer;
use SolidInvoice\TimeTrackingBundle\Enum\TimerStatus;
use SolidInvoice\TimeTrackingBundle\Manager\TimerManager;
use SolidInvoice\TimeTrackingBundle\Repository\TimerRepository;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class TimerWidget extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?Timer $timer = null;

    #[LiveProp(writable: true)]
    public ?string $description = null;

    #[LiveProp(writable: true)]
    public ?string $clientId = null;

    public function __construct(
        private readonly TimerRepository $timerRepository,
        private readonly TimerManager $timerManager,
        private readonly ClientRepository $clientRepository,
    ) {
    }

    public function mount(): void
    {
        $user = $this->getUser();
        if ($user instanceof User) {
            $this->timer = $this->timerRepository->findActiveForUser($user);

            if ($this->timer instanceof Timer) {
                $this->description = $this->timer->getDescription();
                $client = $this->timer->getClient();
                $this->clientId = $client instanceof Client ? (string) $client->getId() : null;
            }
        }
    }

    public function isRunning(): bool
    {
        return $this->timer instanceof Timer && $this->timer->getStatus() === TimerStatus::Running;
    }

    public function isPaused(): bool
    {
        return $this->timer instanceof Timer && $this->timer->getStatus() === TimerStatus::Paused;
    }

    public function getElapsedSeconds(): int
    {
        if (! $this->timer instanceof Timer) {
            return 0;
        }

        return $this->timerManager->getCurrentElapsed($this->timer);
    }

    /**
     * @return array<array{id: string, name: string}>
     */
    public function getClients(): array
    {
        return array_map(
            static fn (Client $client): array => ['id' => (string) $client->getId(), 'name' => $client->getName()],
            $this->clientRepository->findBy(['status' => ClientStatus::Active], ['name' => 'ASC']),
        );
    }

    #[LiveAction]
    public function start(): void
    {
        $user = $this->getUser();
        if (! $user instanceof User) {
            return;
        }

        $this->timer = $this->timerManager->start($user);
        $this->description = null;
        $this->clientId = null;
    }

    #[LiveAction]
    public function pause(): void
    {
        if (! $this->timer instanceof Timer) {
            return;
        }

        $this->timerManager->pause($this->timer);
    }

    #[LiveAction]
    public function resume(): void
    {
        if (! $this->timer instanceof Timer) {
            return;
        }

        $this->timerManager->resume($this->timer);
    }

    #[LiveAction]
    public function stop(): void
    {
        if (! $this->timer instanceof Timer) {
            return;
        }

        $this->timerManager->stop($this->timer);
        $this->timer = null;
        $this->description = null;
        $this->clientId = null;
    }

    #[LiveAction]
    public function updateDetails(): void
    {
        if (! $this->timer instanceof Timer) {
            return;
        }

        $this->timer->setDescription(($this->description !== null && $this->description !== '') ? $this->description : null);

        $client = null;
        if ($this->clientId !== null && $this->clientId !== '') {
            $client = $this->clientRepository->find($this->clientId);
        }

        $this->timer->setClient($client);

        $this->timerManager->saveTimer($this->timer);
    }
}
