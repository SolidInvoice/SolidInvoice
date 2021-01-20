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

namespace SolidInvoice\CoreBundle\Listener;

use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class EmailFromListener implements EventSubscriberInterface
{
    /**
     * @var SystemConfig
     */
    private $config;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(SystemConfig $config, TokenStorageInterface $tokenStorage)
    {
        $this->config = $config;
        $this->tokenStorage = $tokenStorage;
    }

    public function __invoke(MessageEvent $event): void
    {
        /** @var TemplatedEmail $message */
        $message = $event->getMessage();

        $fromAddress = (string) $this->config->get('email/from_address');

        if ('' !== $fromAddress) {
            $fromName = (string) $this->config->get('email/from_name');

            $message->from(new Address($fromAddress, $fromName));
        } else {
            // If a from address is not specified in the config, then we use the currently logged-in user's address
            $token = $this->tokenStorage->getToken();

            if ($token instanceof TokenInterface) {
                /** @var User $user */
                $user = $token->getUser();

                $message->from($user->getEmail());
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MessageEvent::class => '__invoke',
        ];
    }
}
