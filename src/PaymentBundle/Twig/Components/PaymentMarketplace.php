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
use SolidInvoice\PaymentBundle\Gateway\GatewayCategory;
use SolidInvoice\PaymentBundle\Gateway\GatewayMetadataProvider;
use SolidInvoice\PaymentBundle\Repository\PaymentMethodRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use function array_filter;
use function array_keys;
use function array_values;
use function str_contains;
use function strtolower;
use function usort;

/**
 * @phpstan-type GatewayRow array{name: string, displayName: string, tagline: string, category: string, icon: string, recommended: bool, advanced: bool, legacy: bool, isConfigured: bool}
 */
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
        private readonly PaymentMethodRepository $repository,
        private readonly GatewayMetadataProvider $metadata,
        private readonly TranslatorInterface $translator
    ) {
    }

    /**
     * Memoised gateway rows. availableGateways() is reached several times per
     * render (directly, and via filteredGateways()/gatewayGroups()), so it is
     * computed once with a single configured-gateway lookup.
     *
     * @var list<GatewayRow>|null
     */
    private ?array $gatewaysCache = null;

    /**
     * @return list<GatewayRow>
     */
    #[ExposeInTemplate]
    public function availableGateways(): array
    {
        if ($this->gatewaysCache !== null) {
            return $this->gatewaysCache;
        }

        $factories = $this->factories->getFactories() ?? [];
        unset($factories['credit']); // Exclude internal credit system

        // Preload every configured gateway name in a single query rather than
        // querying once per gateway below.
        $configuredNames = [];
        foreach ($this->repository->findAll() as $method) {
            $gatewayName = $method->getGatewayName();
            if ($gatewayName !== null) {
                $configuredNames[$gatewayName] = true;
            }
        }

        $gateways = [];
        foreach (array_keys($factories) as $name) {
            $info = $this->metadata->get($name);

            $gateways[] = [
                'name' => $name,
                'displayName' => $this->translator->trans($info->displayName),
                'tagline' => $this->translator->trans($info->tagline),
                'category' => $info->category->value,
                'icon' => $info->icon,
                'recommended' => $info->recommended,
                'advanced' => $info->isAdvanced(),
                'legacy' => $info->legacy,
                'isConfigured' => isset($configuredNames[$name]),
            ];
        }

        // Within a group: recommended first, then alphabetical by display name.
        usort(
            $gateways,
            static fn (array $a, array $b): int =>
                ($b['recommended'] <=> $a['recommended'])
                    ?: ($a['displayName'] <=> $b['displayName'])
        );

        return $this->gatewaysCache = $gateways;
    }

    /**
     * @return list<GatewayRow>
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
            static fn (array $gateway): bool => str_contains(strtolower((string) $gateway['displayName']), $query)
                || str_contains(strtolower((string) $gateway['tagline']), $query)
        ));
    }

    /**
     * Gateways split into the three marketplace sections shown to the user.
     *
     * @return array{online: list<GatewayRow>, offline: list<GatewayRow>, more: list<GatewayRow>}
     */
    #[ExposeInTemplate]
    public function gatewayGroups(): array
    {
        $groups = ['online' => [], 'offline' => [], 'more' => []];

        foreach ($this->filteredGateways() as $gateway) {
            if ($gateway['legacy']) {
                $groups['more'][] = $gateway;
            } elseif ($gateway['category'] === GatewayCategory::Online->value) {
                $groups['online'][] = $gateway;
            } else {
                $groups['offline'][] = $gateway;
            }
        }

        return $groups;
    }

    /**
     * @return list<PaymentMethod>
     */
    #[ExposeInTemplate]
    public function activeMethods(): array
    {
        return $this->repository->findBy([], ['name' => 'ASC']);
    }

    public function iconFor(string $name): string
    {
        return $this->metadata->icon($name);
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
