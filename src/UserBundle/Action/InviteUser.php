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

namespace SolidInvoice\UserBundle\Action;

use Exception;
use Generator;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\MailerBundle\Factory\MailerConfigFactory;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\UserBundle\Form\Handler\UserInviteFormHandler;
use SolidWorx\FormHandler\FormHandler;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;

final class InviteUser
{
    private FormHandler $formHandler;

    private SystemConfig $config;

    private RouterInterface $router;

    public function __construct(FormHandler $formHandler, SystemConfig $config, RouterInterface $router)
    {
        $this->formHandler = $formHandler;
        $this->config = $config;
        $this->router = $router;
    }

    /**
     * @return FormRequest|RedirectResponse
     * @throws Exception
     */
    public function __invoke()
    {
        $mailerTransport = $this->config->get(MailerConfigFactory::CONFIG_KEY);

        if ($mailerTransport === null) {
            $route = $this->router->generate('_users_list');

            return new class($route) extends RedirectResponse implements FlashResponse {
                public function getFlash(): Generator
                {
                    yield FlashResponse::FLASH_ERROR => 'Please configure mail settings before inviting users';
                }
            };
        }

        return $this->formHandler->handle(UserInviteFormHandler::class);
    }
}
