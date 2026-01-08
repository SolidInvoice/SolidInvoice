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

namespace SolidInvoice\PaymentBundle\Twig\Components;

use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\PaymentBundle\Factory\PaymentFactories;
use SolidInvoice\PaymentBundle\Repository\PaymentMethodRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use function array_keys;
use function in_array;
use function str_contains;
use function str_replace;
use function strtolower;
use function ucwords;

#[AsLiveComponent]
final class PaymentMarketplace extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp(writable: true, url: true)]
    public string $view = 'marketplace';

    #[LiveProp(writable: true, url: true)]
    public string $selectedGateway = '';

    #[LiveProp(writable: true)]
    public string $searchQuery = '';

    public function __construct(
        private readonly PaymentFactories $factories,
        private readonly PaymentMethodRepository $repository
    ) {
    }

    /**
     * @return list<array{name: string, displayName: string, factory: string, category: string, description: string, icon: string, isPopular: bool, isConfigured: bool}>
     */
    #[ExposeInTemplate]
    public function availableGateways(): array
    {
        $factories = $this->factories->getFactories();
        unset($factories['credit']); // Exclude internal credit system

        $gateways = [];
        foreach (array_keys($factories) as $name) {
            $gateways[] = [
                'name' => $name,
                'displayName' => $this->humanizeGatewayName($name),
                'factory' => $factories[$name],
                'category' => $this->categorizeGateway($name),
                'description' => $this->getGatewayDescription($name),
                'icon' => $this->getGatewayIcon($name),
                'isPopular' => in_array($name, ['stripe_checkout', 'paypal_express_checkout', 'offline'], true),
                'isConfigured' => $this->isGatewayConfigured($name),
            ];
        }

        // Sort: custom first, then popular, then alphabetical
        usort(
            $gateways,
            static fn ($a, $b) =>
            // Custom gateway always first
            ($b['name'] === 'custom' ? 1 : ($a['name'] === 'custom' ? -1 : 0)) ?:
            // Then popular gateways
            ($b['isPopular'] <=> $a['isPopular']) ?:
            // Then alphabetical
            ($a['displayName'] <=> $b['displayName'])
        );

        return $gateways;
    }

    /**
     * @return list<PaymentMethod>
     */
    #[ExposeInTemplate]
    public function activeMethods(): array
    {
        return $this->repository->findBy([], ['name' => 'ASC']);
    }

    /**
     * @return list<array{name: string, displayName: string, factory: string, category: string, description: string, icon: string, isPopular: bool, isConfigured: bool}>
     */
    #[ExposeInTemplate]
    public function filteredGateways(): array
    {
        if ($this->searchQuery === '') {
            return $this->availableGateways();
        }

        $query = strtolower($this->searchQuery);

        return array_values(array_filter(
            $this->availableGateways(),
            static fn ($gateway) => str_contains(strtolower($gateway['displayName']), $query) ||
                           str_contains(strtolower($gateway['description']), $query)
        ));
    }

    private function humanizeGatewayName(string $name): string
    {
        // Sanitize gateway name to prevent XSS - only keep alphanumeric, underscores, and hyphens
        $sanitized = preg_replace('/[^a-z0-9_-]/i', '', $name);

        return ucwords(str_replace(['_', '-'], ' ', $sanitized));
    }

    private function categorizeGateway(string $name): string
    {
        if ($this->factories->isOffline($name)) {
            return 'offline';
        }

        if (str_contains($name, 'stripe') || str_contains($name, 'paypal')) {
            return 'online';
        }

        return 'other';
    }

    private function getGatewayDescription(string $name): string
    {
        return match ($name) {
            'stripe_checkout' => 'Accept credit cards with Stripe Checkout',
            'stripe_js' => 'Accept credit cards with Stripe.js',
            'paypal_express_checkout' => 'Accept PayPal and credit cards',
            'paypal_pro_checkout' => 'PayPal Pro payment processing',
            'authorize_net_aim' => 'Authorize.Net AIM integration',
            'offline' => 'Cash, check, or bank transfer',
            'cash' => 'Accept cash payments',
            'bank_transfer' => 'Accept bank transfer payments',
            'custom' => 'Custom payment method',
            default => sprintf('Accept payments via %s', $this->humanizeGatewayName($name)),
        };
    }

    public function getGatewayIcon(string $name): string
    {
        return match (true) {
            str_contains($name, 'stripe') => 'tabler:brand-stripe',
            str_contains($name, 'paypal') => 'tabler:brand-paypal',
            $name === 'cash' => 'tabler:cash',
            $name === 'bank_transfer' => 'tabler:building-bank',
            $name === 'offline' => 'tabler:wallet',
            $name === 'custom' => 'tabler:settings',
            default => 'tabler:credit-card',
        };
    }

    private function isGatewayConfigured(string $name): bool
    {
        return $this->repository->findOneBy(['gatewayName' => $name]) instanceof PaymentMethod;
    }

    #[LiveAction]
    public function clearSearch(): void
    {
        $this->searchQuery = '';
    }

    #[LiveAction]
    public function closeModal(): void
    {
        $this->selectedGateway = '';
    }
}
