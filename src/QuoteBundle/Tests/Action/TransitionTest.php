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

namespace SolidInvoice\QuoteBundle\Tests\Action;

use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\QuoteBundle\Action\Transition;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\QuoteBundle\Model\Graph;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\WorkflowInterface;

final class TransitionTest extends TestCase
{
    public function testCancelFlashesInfoNotSuccess(): void
    {
        $this->assertFlash(Graph::TRANSITION_CANCEL, QuoteStatus::Cancelled, 'info');
    }

    public function testDeclineFlashesInfoNotSuccess(): void
    {
        $this->assertFlash(Graph::TRANSITION_DECLINE, QuoteStatus::Declined, 'info');
    }

    public function testArchiveFlashesSuccess(): void
    {
        $this->assertFlash(Graph::TRANSITION_ARCHIVE, QuoteStatus::Archived, 'success');
    }

    private function assertFlash(string $action, QuoteStatus $resultingStatus, string $expectedSeverity): void
    {
        $quote = new Quote();

        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->expects($this->once())->method('can')->with($quote, $action)->willReturn(true);
        $workflow->expects($this->once())->method('apply')->with($quote, $action)
            ->willReturn(new Marking([$resultingStatus->value => 1]));

        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn('/quotes/view/123');

        $response = new Transition($workflow, $router)(new Request(), $action, $quote);

        self::assertInstanceOf(FlashResponse::class, $response);
        $flashes = iterator_to_array($response->getFlash());
        self::assertSame([$expectedSeverity => 'quote.transition.action.' . $action], $flashes);
    }
}
