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

namespace SolidInvoice\McpBundle\Mcp\Tool;

use BackedEnum;
use Doctrine\ORM\EntityManagerInterface;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\McpBundle\Mcp\Attribute\McpScopeRequired;
use SolidInvoice\McpBundle\Mcp\McpScopeGuard;
use SolidInvoice\McpBundle\Security\McpScope;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Workflow\WorkflowInterface;

final readonly class WorkflowTools
{
    public function __construct(
        private ResourceRegistry $registry,
        private EntityManagerInterface $entityManager,
        #[Autowire(service: 'state_machine.invoice')]
        private WorkflowInterface $invoiceWorkflow,
        #[Autowire(service: 'state_machine.quote')]
        private WorkflowInterface $quoteWorkflow,
        #[Autowire(service: 'state_machine.recurring_invoice')]
        private WorkflowInterface $recurringInvoiceWorkflow,
        private McpScopeGuard $scopeGuard,
    ) {
    }

    /**
     * List workflow transitions currently enabled for a specific invoice, quote, or recurring invoice.
     *
     * @param string $resource One of: invoice, quote, recurring_invoice
     * @param string $id       ULID of the record
     *
     * @return array{current_status: string|null, enabled_transitions: list<string>}
     */
    #[McpTool(name: 'list_workflow_transitions', description: 'List transitions currently enabled on a record (invoice, quote, or recurring_invoice).')]
    #[McpScopeRequired(McpScope::Read)]
    public function listWorkflowTransitions(string $resource, string $id): array
    {
        $this->scopeGuard->require(McpScope::Read);

        $ulid = UlidParser::parse($id);
        $class = $this->registry->resolve($resource);

        $allowed = [Invoice::class, Quote::class, RecurringInvoice::class];

        if (! \in_array($class, $allowed, true)) {
            throw new ToolCallException(sprintf('Resource "%s" has no workflow attached.', $resource));
        }

        $entity = $this->entityManager->getRepository($class)->find($ulid);

        if ($entity === null) {
            throw new ToolCallException(sprintf('%s with id %s not found.', $resource, $id));
        }

        $workflow = match (true) {
            $entity instanceof Invoice => $this->invoiceWorkflow,
            $entity instanceof Quote => $this->quoteWorkflow,
            $entity instanceof RecurringInvoice => $this->recurringInvoiceWorkflow,
            default => throw new ToolCallException('No workflow for this resource type.'),
        };

        $transitions = [];

        foreach ($workflow->getEnabledTransitions($entity) as $transition) {
            $transitions[] = $transition->getName();
        }

        $status = null;

        if (method_exists($entity, 'getStatus')) {
            $current = $entity->getStatus();
            $status = $current instanceof BackedEnum ? $current->value : (\is_string($current) ? $current : null);
        }

        return [
            'current_status' => $status,
            'enabled_transitions' => $transitions,
        ];
    }
}
