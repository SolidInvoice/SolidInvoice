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

namespace SolidInvoice\SettingsBundle\Twig\Components;

use Generator;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\SaasBundle\Service\SubscriptionService;
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
use Symfony\UX\TwigComponent\Attribute\PreMount;
use Throwable;
use function array_key_first;
use function str_replace;

/**
 * @see \SolidInvoice\SettingsBundle\Tests\Twig\Components\SettingsTest
 */
#[AsLiveComponent]
final class Settings extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    /**
     * @var array<string, string>
     */
    public array $settingIconMap = [
        'company' => 'building',
        'invoice' => 'file-invoice',
        'quote' => 'file-text-o',
        'email' => 'envelope',
        'payment' => 'credit-card',
        'tax' => 'balance-scale',
        'system' => 'cog',
    ];

    #[LiveProp(writable: true, onUpdated: 'onSectionChange', url: true)]
    public string $section = '';

    public function __construct(
        private readonly SettingsRepository $settingsRepository,
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly ?SubscriptionService $subscriptionService = null,
        private readonly string $customDomainDnsRecord = '',
    ) {
    }

    #[ExposeInTemplate('custom_domain_dns_record')]
    public function getCustomDomainDnsRecord(): string
    {
        return $this->customDomainDnsRecord;
    }

    #[PreMount()]
    public function preMount(): void
    {
        $this->section = key($this->getAppSettings());
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

    /**
     * @return FormInterface<mixed>
     */
    protected function instantiateForm(): FormInterface
    {
        $isTrialSubscription = $this->subscriptionService?->isTrialSubscription() ?? false;
        $appSettings = $this->getAppSettings(false);

        if (! isset($appSettings[$this->section])) {
            $this->section = (string) array_key_first($appSettings);
        }

        return $this->createForm(
            SettingsType::class,
            $appSettings[$this->section],
            [
                'settings' => $this->getAppSettings(true)[$this->section],
                'subscription_in_trial' => $isTrialSubscription,
            ]
        );
    }

    public function onSectionChange(): void
    {
        $this->resetForm();
    }

    /**
     * @phpstan-ignore-next-line Overriding method from trait
     */
    private function getDataModelValue(): string
    {
        return 'norender|*';
    }

    /**
     * @throws Throwable
     */
    #[LiveAction]
    public function save(Request $request): RedirectResponse
    {
        $files = $request->files->all();

        if (isset($files['settings']['company']['logo'])) {
            $this->formValues['company']['logo'] = $files['settings']['company']['logo'];
        }

        $this->submitForm();

        $this->settingsRepository->store([$this->section => $this->getForm()->getData()]);

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
