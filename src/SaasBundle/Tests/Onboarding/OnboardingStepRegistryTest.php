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

namespace SolidInvoice\SaasBundle\Tests\Onboarding;

use PHPUnit\Framework\TestCase;
use SolidInvoice\SaasBundle\Onboarding\OnboardingStepRegistry;
use SolidInvoice\SaasBundle\Tests\Onboarding\Fixtures\StepA;
use SolidInvoice\SaasBundle\Tests\Onboarding\Fixtures\StepB;
use SolidInvoice\SaasBundle\Tests\Onboarding\Fixtures\StepC;
use SolidInvoice\SaasBundle\Tests\Onboarding\Fixtures\StepFirst;
use SolidInvoice\SaasBundle\Tests\Onboarding\Fixtures\StepSecond;

final class OnboardingStepRegistryTest extends TestCase
{
    public function testStepsAreSortedByPriorityDescending(): void
    {
        $registry = new OnboardingStepRegistry([new StepB(), new StepA(), new StepC()]);

        self::assertSame(['a', 100], [$registry->all()[0]::key(), $registry->all()[0]::priority()]);
        self::assertSame('c', $registry->all()[1]::key());
        self::assertSame('b', $registry->all()[2]::key());
    }

    public function testNextAfterReturnsFirstStepWhenNoneDispatched(): void
    {
        $registry = new OnboardingStepRegistry([new StepFirst(), new StepSecond()]);

        self::assertSame('first', $registry->nextAfter(null)?->key());
    }

    public function testNextAfterReturnsFollowingStep(): void
    {
        $registry = new OnboardingStepRegistry([new StepFirst(), new StepSecond()]);

        self::assertSame('second', $registry->nextAfter('first')?->key());
    }

    public function testNextAfterReturnsNullWhenLastStepAlreadyDispatched(): void
    {
        $registry = new OnboardingStepRegistry([new StepFirst(), new StepSecond()]);

        self::assertNull($registry->nextAfter('second'));
    }

    public function testUnknownLastKeyRestartsFromBeginning(): void
    {
        $registry = new OnboardingStepRegistry([new StepFirst(), new StepSecond()]);

        self::assertSame('first', $registry->nextAfter('removed_step')?->key());
    }
}
