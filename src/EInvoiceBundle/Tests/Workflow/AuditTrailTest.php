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

use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\EInvoiceBundle\Entity\EInvoiceDocument;
use SolidInvoice\EInvoiceBundle\Enum\EInvoiceStatus;
use SolidInvoice\EInvoiceBundle\Enum\EInvoiceTransition;
use SolidInvoice\EInvoiceBundle\Enum\SyntaxFormat;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use Stringable;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\Workflow\EventListener\AuditTrailListener;
use Symfony\Component\Workflow\MarkingStore\MethodMarkingStore;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\Transition;

/**
 * Proves `audit_trail: enabled` on the `einvoice_document` workflow (config/packages/workflow.php,
 * SOL-208) writes through the `workflow` monolog channel when a transition is applied. Reading the
 * config key back would prove nothing: this asserts against a real
 * {@see AuditTrailListener} wired to a test logger.
 */
final class AuditTrailTest extends TestCase
{
    public function testApplyingATransitionWritesAnAuditTrailRecord(): void
    {
        $logger = new class() extends AbstractLogger {
            /**
             * @var list<string>
             */
            public array $messages = [];

            public function log($level, string | Stringable $message, array $context = []): void
            {
                $this->messages[] = (string) $message;
            }
        };

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new AuditTrailListener($logger));

        $definition = new DefinitionBuilder()
            ->addPlaces([EInvoiceStatus::Pending->value, EInvoiceStatus::Queued->value])
            ->addTransition(new Transition(EInvoiceTransition::Queue->value, EInvoiceStatus::Pending->value, EInvoiceStatus::Queued->value))
            ->build();

        $workflow = new StateMachine(
            $definition,
            new MethodMarkingStore(true, 'statusValue'),
            $dispatcher,
            'einvoice_document',
        );

        $document = $this->createDocument();

        self::assertSame([], $logger->messages);

        $workflow->apply($document, EInvoiceTransition::Queue->value);

        self::assertNotSame([], $logger->messages);

        foreach ($logger->messages as $message) {
            self::assertStringContainsString('einvoice_document', $message);
        }
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
