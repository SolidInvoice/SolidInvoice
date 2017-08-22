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

namespace SolidInvoice\SettingsBundle\Form\Handler;

use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\SettingsBundle\Entity\Setting;
use SolidInvoice\SettingsBundle\Form\Type\SettingsType;
use SolidInvoice\SettingsBundle\Repository\SettingsRepository;
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormHandlerResponseInterface;
use SolidWorx\FormHandler\FormHandlerSuccessInterface;
use SolidWorx\FormHandler\FormRequest;
use SolidWorx\FormHandler\Options;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\RouterInterface;

class SettingsFormHandler implements FormHandlerInterface, FormHandlerSuccessInterface, FormHandlerResponseInterface
{
    /**
     * @var SettingsRepository
     */
    private $settingsRepository;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(SettingsRepository $settingsRepository, Session $session, RouterInterface $router)
    {
        $this->settingsRepository = $settingsRepository;
        $this->session = $session;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function getForm(FormFactoryInterface $factory = null, Options $options)
    {
        return $factory->create(SettingsType::class, $this->getSettings(false), ['settings' => $this->getSettings()]);
    }

    /**
     * {@inheritdoc}
     */
    public function onSuccess($data, FormRequest $form): ?Response
    {
        $this->settingsRepository->save($this->flatten($data));

        $route = $this->router->generate($form->getRequest()->attributes->get('_route'));

        return new class($route) extends RedirectResponse implements FlashResponse {
            public function getFlash(): iterable
            {
                yield self::FLASH_SUCCESS => 'settings.saved.success';
            }
        };
    }

    private function flatten(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, $this->flatten($value, $prefix.$key.'/'));
            } else {
                $result[$prefix.$key] = $value;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse(FormRequest $formRequest)
    {
        $form = $formRequest->getForm();

        return new Template(
            'SolidInvoiceSettingsBundle:Settings:index.html.twig',
            [
                'appSettings' => $form->getData(),
                'form' => $form->createView(),
            ]
        );
    }

    private function getSettings(bool $keepObject = true): array
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $settings = [];

        /** @var Setting $setting */
        foreach ($this->settingsRepository->findAll() as $setting) {
            $path = '['.str_replace('/', '][', $setting->getKey()).']';

            $propertyAccessor->setValue($settings, $path, $keepObject ? $setting : $setting->getValue());
        }

        return $settings;
    }
}
