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

namespace SolidInvoice\SaasBundle\Onboarding;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use function array_values;
use function iterator_to_array;

/**
 * @see \SolidInvoice\SaasBundle\Tests\Onboarding\OnboardingStepRegistryTest
 */
final readonly class OnboardingStepRegistry
{
    /**
     * @var list<OnboardingEmailStepInterface>
     */
    private array $steps;

    /**
     * @param iterable<OnboardingEmailStepInterface> $steps
     */
    public function __construct(
        #[AutowireIterator(OnboardingEmailStepInterface::DI_TAG)]
        iterable $steps,
    ) {
        $this->steps = array_values(iterator_to_array($steps, false));
    }

    /**
     * @return list<OnboardingEmailStepInterface>
     */
    public function all(): array
    {
        return $this->steps;
    }

    public function get(string $key): ?OnboardingEmailStepInterface
    {
        foreach ($this->steps as $step) {
            if ($step::key() === $key) {
                return $step;
            }
        }

        return null;
    }
}
