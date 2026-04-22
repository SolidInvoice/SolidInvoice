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

namespace SolidInvoice\DashboardBundle\Checklist\Items;

use SolidInvoice\DashboardBundle\Checklist\ChecklistItemInterface;
use SolidInvoice\PaymentBundle\Repository\PaymentMethodRepository;

final readonly class ConfigurePaymentGatewayItem implements ChecklistItemInterface
{
    public function __construct(
        private PaymentMethodRepository $paymentMethodRepository,
    ) {
    }

    public function getName(): string
    {
        return 'dashboard.checklist.configure_payment_gateway.name';
    }

    public function getDescription(): string
    {
        return 'dashboard.checklist.configure_payment_gateway.description';
    }

    public function getIcon(): string
    {
        return 'tabler:credit-card';
    }

    public function getRoute(): string
    {
        return '_payment_settings_index';
    }

    public function getPriority(): int
    {
        return -500;
    }

    public function active(): bool
    {
        return true;
    }

    public function isComplete(): bool
    {
        // Check if at least one payment method is enabled
        // Company filter ensures we only count payment methods for the current company
        $enabledMethods = $this->paymentMethodRepository->getTotalMethodsConfigured(includeInternal: false);

        return $enabledMethods > 0;
    }
}
