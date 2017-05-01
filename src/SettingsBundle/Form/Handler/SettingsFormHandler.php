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

namespace CSBill\SettingsBundle\Form\Handler;

use CSBill\CoreBundle\Templating\Template;
use CSBill\SettingsBundle\Form\Type\SettingSectionType;
use CSBill\SettingsBundle\Manager\SettingsManager;
use CSBill\SettingsBundle\Model\Setting;
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormHandlerResponseInterface;
use SolidWorx\FormHandler\FormHandlerSuccessInterface;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;

class SettingsFormHandler implements FormHandlerInterface, FormHandlerSuccessInterface, FormHandlerResponseInterface
{
    /**
     * @var SettingsManager
     */
    private $settingsManager;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(SettingsManager $settingsManager, Session $session, RouterInterface $router)
    {
        $this->settingsManager = $settingsManager;
        $this->session = $session;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function getForm(FormFactoryInterface $factory = null, ...$options)
    {
        $settings = $this->settingsManager->getSettings();

        array_walk_recursive($settings, function (Setting &$setting): void {
            $setting = $setting->getValue();
        });

        return $factory->create(SettingSectionType::class, $settings, ['manager' => $this->settingsManager]);
    }

    /**
     * {@inheritdoc}
     */
    public function onSuccess($data, FormRequest $form): ?Response
    {
        $this->settingsManager->set($form->getForm()->getData());

        $route = $this->router->generate($form->getRequest()->attributes->get('_route'));

        return new class($route) extends RedirectResponse implements FlashResponse {
            public function getFlash(): iterable
            {
                yield self::FLASH_SUCCESS => 'settings.saved.success';
            }
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse(FormRequest $formRequest)
    {
        $form = $formRequest->getForm();

        return new Template(
            'CSBillSettingsBundle:Settings:index.html.twig',
            [
                'appSettings' => $form->getData(),
                'form' => $form->createView(),
            ]
        );
    }
}
