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

namespace SolidInvoice\SaasBundle\Tests\Checklist;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Company\CompanySelectorInterface;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\SaasBundle\Checklist\ActivateSubscriptionItem;
use SolidInvoice\SaasBundle\Service\BillingMode;
use SolidInvoice\SaasBundle\Tests\BillingModeFactory;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionProviderInterface;

#[CoversClass(ActivateSubscriptionItem::class)]
final class ActivateSubscriptionItemTest extends TestCase
{
    public function testTheItemIsOfferedInFreeTrialMode(): void
    {
        self::assertTrue($this->item(BillingModeFactory::freeTrial())->active());
    }

    /**
     * In paid-trial mode the company cannot reach the dashboard without an
     * active subscription, so the prompt would always be redundant.
     */
    public function testTheItemIsHiddenInPaidTrialMode(): void
    {
        self::assertFalse($this->item(BillingModeFactory::paidTrial())->active());
    }

    private function item(BillingMode $billingMode): ActivateSubscriptionItem
    {
        return new ActivateSubscriptionItem(
            $this->createStub(SubscriptionProviderInterface::class),
            $this->createStub(CompanySelectorInterface::class),
            $this->createStub(CompanyRepository::class),
            $billingMode,
        );
    }
}
