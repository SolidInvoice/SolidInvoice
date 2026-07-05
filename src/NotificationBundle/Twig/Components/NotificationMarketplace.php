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

namespace SolidInvoice\NotificationBundle\Twig\Components;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SolidInvoice\NotificationBundle\Configurator\ConfiguratorInterface;
use SolidInvoice\NotificationBundle\Entity\TransportSetting;
use SolidInvoice\NotificationBundle\Repository\TransportSettingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use function array_filter;
use function array_values;
use function in_array;
use function str_contains;
use function str_replace;
use function strtolower;
use function ucwords;
use function usort;

/**
 * @see \SolidInvoice\NotificationBundle\Tests\Twig\Components\NotificationMarketplaceTest
 */
#[AsLiveComponent]
final class NotificationMarketplace extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp(writable: true, url: true)]
    public string $view = 'marketplace';

    #[LiveProp(writable: true, url: true)]
    public string $selectedIntegration = '';

    #[LiveProp(writable: true, url: true)]
    public string $transport = '';

    #[LiveProp(writable: true, url: true)]
    public string $type = '';

    #[LiveProp(writable: true, url: true)]
    public string $activeTab = 'sms';

    #[LiveProp(writable: true)]
    public string $searchQuery = '';

    /**
     * @param ServiceLocator<ConfiguratorInterface> $transportConfigurations
     */
    public function __construct(
        #[AutowireLocator(ConfiguratorInterface::DI_TAG)]
        private readonly ServiceLocator $transportConfigurations,
        private readonly TransportSettingRepository $repository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @return list<array{name: string, displayName: string, type: string, typeLabel: string, description: string, icon: string, isPopular: bool, isConfigured: bool}>
     */
    #[ExposeInTemplate]
    public function availableIntegrations(): array
    {
        $integrations = [];

        foreach (array_keys($this->transportConfigurations->getProvidedServices()) as $name) {
            try {
                /** @var ConfiguratorInterface $configurator */
                $configurator = $this->transportConfigurations->get($name);
                $type = $configurator::getType();

                $integrations[] = [
                    'name' => $name,
                    'displayName' => $this->humanizeIntegrationName($name),
                    'type' => $type,
                    'typeLabel' => $type === 'texter' ? 'SMS' : 'Chat',
                    'description' => $this->getIntegrationDescription($name, $type),
                    'icon' => $this->getIntegrationIcon($name),
                    'isPopular' => $this->isPopularIntegration($name),
                    'isConfigured' => $this->isIntegrationConfigured($name),
                ];
            } catch (NotFoundExceptionInterface|ContainerExceptionInterface) {
                continue;
            }
        }

        // Sort: popular first, then alphabetical
        usort(
            $integrations,
            static fn ($a, $b) =>
            // Popular integrations first
            ($b['isPopular'] <=> $a['isPopular']) ?:
            // Then alphabetical
            ($a['displayName'] <=> $b['displayName'])
        );

        return $integrations;
    }

    /**
     * @return list<TransportSetting>
     */
    #[ExposeInTemplate]
    public function configuredIntegrations(): array
    {
        return $this->repository->findBy(['user' => $this->getUser()], ['name' => 'ASC']);
    }

    /**
     * @return list<array{name: string, displayName: string, type: string, typeLabel: string, description: string, icon: string, isPopular: bool, isConfigured: bool}>
     */
    #[ExposeInTemplate]
    public function filteredIntegrations(): array
    {
        if ($this->searchQuery === '') {
            return $this->availableIntegrations();
        }

        $query = strtolower($this->searchQuery);

        return array_values(array_filter(
            $this->availableIntegrations(),
            static fn ($integration) => str_contains(strtolower((string) $integration['displayName']), $query) ||
                           str_contains(strtolower((string) $integration['description']), $query) ||
                           str_contains(strtolower((string) $integration['typeLabel']), $query)
        ));
    }

    /**
     * @return list<array{name: string, displayName: string, type: string, typeLabel: string, description: string, icon: string, isPopular: bool, isConfigured: bool}>
     */
    #[ExposeInTemplate]
    public function tabFilteredIntegrations(): array
    {
        $filtered = $this->filteredIntegrations();

        $targetType = match ($this->activeTab) {
            'sms' => 'texter',
            'chat' => 'chatter',
            default => 'texter',
        };

        return array_values(array_filter($filtered, fn ($integration) => $integration['type'] === $targetType));
    }

    /**
     * @return list<array{name: string, displayName: string, type: string, typeLabel: string, description: string, icon: string, isPopular: bool, isConfigured: bool}>
     */
    #[ExposeInTemplate]
    public function featuredIntegrations(): array
    {
        return array_values(array_filter($this->tabFilteredIntegrations(), fn ($integration) => $integration['isPopular']));
    }

    /**
     * @return list<array{name: string, displayName: string, type: string, typeLabel: string, description: string, icon: string, isPopular: bool, isConfigured: bool}>
     */
    #[ExposeInTemplate]
    public function regularIntegrations(): array
    {
        return array_values(array_filter($this->tabFilteredIntegrations(), fn ($integration) => ! $integration['isPopular']));
    }

    public function getIntegrationIcon(string $name): string
    {
        return match ($name) {
            // SMS Providers
            'Twilio' => 'tabler:message-2',
            'Vonage' => 'tabler:phone',
            'AmazonSns' => 'tabler:brand-aws',
            'MessageBird', 'MicrosoftTeams' => 'tabler:message-circle',
            'Infobip' => 'tabler:message',
            'Telnyx' => 'tabler:phone-call',
            'Sinch' => 'tabler:device-mobile',
            'Clickatell', 'Zulip' => 'tabler:messages',
            'Brevo' => 'tabler:mail',
            'Mailjet' => 'tabler:mail-forward',
            'FakeSms', 'FakeChat' => 'tabler:code',

            // Chat Providers
            'Slack' => 'tabler:brand-slack',
            'Discord' => 'tabler:brand-discord',
            'Telegram' => 'tabler:brand-telegram',
            'GoogleChat' => 'tabler:brand-google',
            'Mattermost' => 'tabler:message-circle-2',
            'RocketChat' => 'tabler:rocket',
            'LinkedIn' => 'tabler:brand-linkedin',
            'Firebase' => 'tabler:brand-firebase',
            'Mercure' => 'tabler:speakerphone',

            // Generic fallbacks
            default => 'tabler:bell',
        };
    }

    public function getIntegrationTypeLabel(string $transportName): string
    {
        try {
            /** @var ConfiguratorInterface $configurator */
            $configurator = $this->transportConfigurations->get($transportName);
            $type = $configurator::getType();

            return $this->translator->trans($type === 'texter' ? 'notification.type.sms' : 'notification.type.chat');
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface) {
            return $this->translator->trans('notification.type.unknown');
        }
    }

    public function getIntegrationType(string $transportName): string
    {
        try {
            /** @var ConfiguratorInterface $configurator */
            $configurator = $this->transportConfigurations->get($transportName);

            return $configurator::getType();
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface) {
            return 'texter';
        }
    }

    private function humanizeIntegrationName(string $name): string
    {
        // Sanitize integration name to prevent XSS - only keep alphanumeric, underscores, and hyphens
        $sanitized = preg_replace('/[^a-z0-9_-]/i', '', $name);

        return ucwords(str_replace(['_', '-'], ' ', $sanitized));
    }

    private function getIntegrationDescription(string $name, string $type): string
    {
        $descriptionKey = match ($name) {
            'Twilio' => 'notification.provider.twilio',
            'Vonage' => 'notification.provider.vonage',
            'AmazonSns' => 'notification.provider.amazon_sns',
            'MessageBird' => 'notification.provider.message_bird',
            'Sinch' => 'notification.provider.sinch',
            'Slack' => 'notification.provider.slack',
            'Discord' => 'notification.provider.discord',
            'Telegram' => 'notification.provider.telegram',
            'MicrosoftTeams' => 'notification.provider.microsoft_teams',
            'GoogleChat' => 'notification.provider.google_chat',
            'Mattermost' => 'notification.provider.mattermost',
            'RocketChat' => 'notification.provider.rocket_chat',
            'FakeSms' => 'notification.provider.fake_sms',
            'FakeChat' => 'notification.provider.fake_chat',
            default => null,
        };

        if ($descriptionKey !== null) {
            return $this->translator->trans($descriptionKey);
        }

        $typeLabel = $type === 'texter'
            ? $this->translator->trans('notification.type.sms')
            : $this->translator->trans('notification.provider.type_chat');

        return $this->translator->trans('notification.provider.generic', [
            '%type%' => $typeLabel,
            '%name%' => $this->humanizeIntegrationName($name),
        ]);
    }

    private function isPopularIntegration(string $name): bool
    {
        return in_array($name, [
            'Twilio',
            'Vonage',
            'Slack',
            'Discord',
            'Telegram',
            'MicrosoftTeams',
        ], true);
    }

    private function isIntegrationConfigured(string $name): bool
    {
        return $this->repository->findOneBy([
            'transport' => $name,
            'user' => $this->getUser(),
        ]) instanceof TransportSetting;
    }

    #[LiveAction]
    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    #[LiveAction]
    public function clearSearch(): void
    {
        $this->searchQuery = '';
    }

    #[LiveAction]
    public function closeModal(): void
    {
        $this->selectedIntegration = '';
        $this->transport = '';
        $this->type = '';
    }
}
