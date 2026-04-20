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
use function count;
use function iterator_to_array;

final class OnboardingStepRegistry
{
    /**
     * @var list<OnboardingEmailStepInterface>
     */
    private readonly array $steps;

    /**
     * @param iterable<OnboardingEmailStepInterface> $steps
     */
    public function __construct(
        #[AutowireIterator(OnboardingEmailStepInterface::DI_TAG, defaultPriorityMethod: 'priority')]
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

    public function count(): int
    {
        return count($this->steps);
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

    public function indexOf(string $key): ?int
    {
        foreach ($this->steps as $index => $step) {
            if ($step::key() === $key) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Resolve the next step to run given the key of the last one that was
     * dispatched (or null if none yet).
     */
    public function nextAfter(?string $lastKey): ?OnboardingEmailStepInterface
    {
        if ($lastKey === null) {
            return $this->steps[0] ?? null;
        }

        $index = $this->indexOf($lastKey);

        if ($index === null) {
            // Unknown key (e.g. a step was removed between releases) — restart
            // from the beginning so the user still receives the remainder of
            // the sequence rather than getting stuck.
            return $this->steps[0] ?? null;
        }

        return $this->steps[$index + 1] ?? null;
    }
}
