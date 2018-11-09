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

namespace SolidInvoice\CoreBundle\Email\Decorator;

use SolidInvoice\MailerBundle\Decorator\MessageDecorator;
use SolidInvoice\MailerBundle\Event\MessageEvent;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class EmailFromDecorator implements MessageDecorator
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

    public function decorate(MessageEvent $event): void
    {
        $message = $event->getMessage();

        $fromAddress = (string) $this->config->get('email/from_address');

        if ($fromAddress) {
            $fromName = (string) $this->config->get('email/from_name');

            $message->setFrom($fromAddress, $fromName);
        } else {
            // If a from address is not specified in the config, then we use the currently logged-in user's address
            $token = $this->tokenStorage->getToken();

            /** @var User $user */
            $user = $token->getUser();

            $message->setFrom($user->getEmail());
        }
    }
}
