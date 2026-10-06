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

namespace SolidInvoice\InvoiceBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Guards against a place being declared in the invoice workflow config without
 * ever being reachable, the defect fixed on SOL-194: `InvoiceStatus::Active`
 * was listed as a place but was never the `to` of any transition.
 */
final class InvoiceWorkflowReachabilityTest extends KernelTestCase
{
    public function testEveryPlaceIsInitialOrReachableByATransition(): void
    {
        self::bootKernel();

        $workflow = self::getContainer()->get('test.state_machine.invoice');
        self::assertInstanceOf(WorkflowInterface::class, $workflow);

        $definition = $workflow->getDefinition();
        $initialPlaces = $definition->getInitialPlaces();

        $reachablePlaces = [];
        foreach ($definition->getTransitions() as $transition) {
            foreach ($transition->getTos() as $to) {
                $reachablePlaces[$to] = true;
            }
        }

        foreach ($definition->getPlaces() as $place) {
            self::assertTrue(
                in_array($place, $initialPlaces, true) || isset($reachablePlaces[$place]),
                sprintf('Place "%s" is declared but is neither an initial place nor the target of any transition.', $place),
            );
        }
    }
}
