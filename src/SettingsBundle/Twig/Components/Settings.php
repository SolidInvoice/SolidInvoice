<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\SettingsBundle\Twig\Components;

use Generator;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\SettingsBundle\Entity\Setting;
use SolidInvoice\SettingsBundle\Form\Type\SettingsType;
use SolidInvoice\SettingsBundle\Repository\SettingsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Throwable;
use function str_replace;

#[AsLiveComponent(name: 'Settings')]
final class Settings extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp(writable: true, onUpdated: 'onSectionChange', url: true)]
    public string $section = 'system';

    public function __construct(
        private readonly SettingsRepository $settingsRepository,
        private readonly PropertyAccessorInterface $propertyAccessor,
    ) {
    }

    /**
     * @return array<string, string|bool>
     */
    #[ExposeInTemplate]
    public function getAppSettings(bool $useObject = false): array
    {
        $settings = [];

        /** @var Setting $setting */
        foreach ($this->settingsRepository->findAll() as $setting) {
            $path = '[' . str_replace('/', '][', $setting->getKey()) . ']';

            $value = $setting->getType() === CheckboxType::class ? $setting->getValue() === '1' : $setting->getValue();

            $this->propertyAccessor->setValue(
                $settings,
                $path,
                $useObject ? $setting : $value
            );
        }

        return $settings;
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(SettingsType::class, $this->getAppSettings(false)[$this->section], ['settings' => $this->getAppSettings(true)[$this->section]]);
    }

    public function onSectionChange(): void
    {
        $this->resetForm();
    }

    private function getDataModelValue(): string
    {
        return 'norender|*';
    }

    /**
     * @throws Throwable
     */
    #[LiveAction]
    public function save(SettingsRepository $settingsRepository, Request $request): RedirectResponse
    {
        $files = $request->files->all();

        if (isset($files['settings']['company']['logo'])) {
            $this->formValues['company']['logo'] = $files['settings']['company']['logo'];
        }

        $this->submitForm();

        $settingsRepository->save([$this->section => $this->getForm()->getData()]);

        $route = $this->generateUrl('_settings', ['section' => $this->section]);

        return new class($route) extends RedirectResponse implements FlashResponse {
            /**
             * @return Generator<string, string>
             */
            public function getFlash(): Generator
            {
                yield self::FLASH_SUCCESS => 'settings.saved.success';
            }
        };
    }
}
