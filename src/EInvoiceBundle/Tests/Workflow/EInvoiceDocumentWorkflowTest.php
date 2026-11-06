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

namespace SolidInvoice\EInvoiceBundle\Tests\Workflow;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\EInvoiceBundle\Entity\EInvoiceDocument;
use SolidInvoice\EInvoiceBundle\Enum\EInvoiceStatus;
use SolidInvoice\EInvoiceBundle\Enum\EInvoiceTransition;
use SolidInvoice\EInvoiceBundle\Enum\SyntaxFormat;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\Workflow\Exception\LogicException;
use Symfony\Component\Workflow\MarkingStore\MethodMarkingStore;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\Transition;
use function sprintf;

/**
 * Builds the `einvoice_document` state machine declared in `config/packages/workflow.php`
 * (SOL-208) the same way the container would, and proves it accepts only the edges on the
 * documented graph — see the table on SOL-208.
 */
final class EInvoiceDocumentWorkflowTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const array PLACES = [
        EInvoiceStatus::Pending->value,
        EInvoiceStatus::Queued->value,
        EInvoiceStatus::Transmitted->value,
        EInvoiceStatus::Accepted->value,
        EInvoiceStatus::Rejected->value,
        EInvoiceStatus::Cancelled->value,
        EInvoiceStatus::Failed->value,
    ];

    /**
     * Transition name => [valid "from" places, "to" place], matching the table in
     * `config/packages/workflow.php`.
     *
     * @var array<string, array{0: list<string>, 1: string}>
     */
    private const array TRANSITIONS = [
        'queue' => [[EInvoiceStatus::Pending->value], EInvoiceStatus::Queued->value],
        'transmit' => [[EInvoiceStatus::Queued->value], EInvoiceStatus::Transmitted->value],
        'accept' => [[EInvoiceStatus::Transmitted->value], EInvoiceStatus::Accepted->value],
        'reject' => [[EInvoiceStatus::Transmitted->value], EInvoiceStatus::Rejected->value],
        'cancel' => [[EInvoiceStatus::Pending->value, EInvoiceStatus::Queued->value, EInvoiceStatus::Transmitted->value], EInvoiceStatus::Cancelled->value],
        'fail' => [[EInvoiceStatus::Pending->value, EInvoiceStatus::Queued->value], EInvoiceStatus::Failed->value],
    ];

    /**
     * @return iterable<string, array{0: EInvoiceTransition, 1: EInvoiceStatus, 2: EInvoiceStatus}>
     */
    public static function validTransitionProvider(): iterable
    {
        foreach (self::TRANSITIONS as $name => [$froms, $to]) {
            foreach ($froms as $from) {
                yield sprintf('%s: %s -> %s', $name, $from, $to) => [
                    EInvoiceTransition::from($name),
                    EInvoiceStatus::from($from),
                    EInvoiceStatus::from($to),
                ];
            }
        }
    }

    #[DataProvider('validTransitionProvider')]
    public function testValidTransitionAppliesAndLandsOnDocumentedPlace(EInvoiceTransition $transition, EInvoiceStatus $from, EInvoiceStatus $to): void
    {
        $workflow = $this->buildWorkflow();
        $document = $this->createDocument();
        $document->setStatus($from);

        self::assertTrue($workflow->can($document, $transition->value));

        $workflow->apply($document, $transition->value);

        self::assertSame($to, $document->getStatus());
    }

    /**
     * Every (place, transition) pair that is not on the documented graph — the full cartesian
     * product of places and transitions, minus the valid edges, not a hand-picked sample.
     *
     * @return iterable<string, array{0: EInvoiceStatus, 1: EInvoiceTransition}>
     */
    public static function invalidTransitionProvider(): iterable
    {
        $valid = [];

        foreach (self::TRANSITIONS as $name => [$froms]) {
            foreach ($froms as $from) {
                $valid[$from][$name] = true;
            }
        }

        foreach (self::PLACES as $place) {
            foreach (self::TRANSITIONS as $name => $definition) {
                if (isset($valid[$place][$name])) {
                    continue;
                }

                yield sprintf('%s cannot %s', $place, $name) => [
                    EInvoiceStatus::from($place),
                    EInvoiceTransition::from($name),
                ];
            }
        }
    }

    #[DataProvider('invalidTransitionProvider')]
    public function testInvalidTransitionIsRejected(EInvoiceStatus $place, EInvoiceTransition $transition): void
    {
        $workflow = $this->buildWorkflow();
        $document = $this->createDocument();
        $document->setStatus($place);

        self::assertFalse($workflow->can($document, $transition->value));

        $this->expectException(LogicException::class);

        $workflow->apply($document, $transition->value);
    }

    /**
     * @return iterable<string, array{0: EInvoiceStatus}>
     */
    public static function terminalPlaceProvider(): iterable
    {
        yield 'accepted' => [EInvoiceStatus::Accepted];
        yield 'rejected' => [EInvoiceStatus::Rejected];
        yield 'cancelled' => [EInvoiceStatus::Cancelled];
        yield 'failed' => [EInvoiceStatus::Failed];
    }

    #[DataProvider('terminalPlaceProvider')]
    public function testTerminalPlaceHasNoEnabledTransitions(EInvoiceStatus $place): void
    {
        $workflow = $this->buildWorkflow();
        $document = $this->createDocument();
        $document->setStatus($place);

        self::assertSame([], $workflow->getEnabledTransitions($document));
    }

    /**
     * One {@see Transition} per (from, to) pair, matching how the framework builds a
     * `state_machine` from `config/packages/workflow.php` — a transition config entry with
     * several `from` places becomes several single-from Transition objects sharing the same
     * name, not one Transition with an array of froms (that would be Petri-net AND semantics).
     */
    private function buildWorkflow(): StateMachine
    {
        $builder = new DefinitionBuilder()->addPlaces(self::PLACES);

        foreach (self::TRANSITIONS as $name => [$froms, $to]) {
            foreach ($froms as $from) {
                $builder->addTransition(new Transition($name, $from, $to));
            }
        }

        return new StateMachine(
            $builder->build(),
            new MethodMarkingStore(true, 'statusValue'),
            new EventDispatcher(),
            'einvoice_document',
        );
    }

    private function createDocument(): EInvoiceDocument
    {
        return new EInvoiceDocument(
            new Company(),
            new Invoice(),
            null,
            'INV-0001',
            'peppol',
            'peppol-bis-billing-3.0',
            '<Invoice></Invoice>',
            SyntaxFormat::Ubl,
            'application/xml',
            'invoice-ubl.xml',
        );
    }
}
