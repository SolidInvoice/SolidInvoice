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

namespace SolidInvoice\McpBundle\Tests\Functional;

use Mcp\Capability\Registry;
use Mcp\Schema\Tool;
use Mcp\Schema\ToolAnnotations;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function str_starts_with;

/**
 * Every tool the server registers must describe itself: a title, all four annotation hints, and
 * an output schema. Read from the live registry, so a tool added later without an entry in
 * {@see self::HINTS} fails here.
 */
#[CoversNothing]
#[Group('functional')]
final class ToolMetadataTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    /**
     * [readOnly, destructive, idempotent, openWorld] per tool. openWorld is true when the handler
     * sends email, directly or through a NotificationManager notification (which emails company
     * users by default), or calls an external party.
     *
     * @var array<string, array{bool, ?bool, ?bool, bool}>
     */
    private const array HINTS = [
        'add_client_tax_identifier' => [false, false, false, false],
        'apply_invoice_transition' => [false, true, false, true],
        'apply_quote_transition' => [false, true, false, true],
        'apply_recurring_transition' => [false, true, false, true],
        'clone_invoice' => [false, false, false, true],
        'clone_quote' => [false, false, false, true],
        'convert_quote_to_invoice' => [false, false, false, true],
        'create_client' => [false, false, false, true],
        'create_contact' => [false, false, false, false],
        'create_invoice' => [false, false, false, true],
        'create_quote' => [false, false, false, true],
        'create_product' => [false, false, false, false],
        'create_recurring_invoice' => [false, false, false, true],
        'create_tax_rate' => [false, false, false, false],
        'delete_client' => [false, true, true, false],
        'delete_contact' => [false, true, true, false],
        'delete_product' => [false, true, true, false],
        'delete_tax_rate' => [false, true, true, false],
        'get_client' => [true, null, null, false],
        'get_client_summary' => [true, null, null, false],
        'get_company_info' => [true, null, null, false],
        'get_contact' => [true, null, null, false],
        'get_dashboard_stats' => [true, null, null, false],
        'get_invoice' => [true, null, null, false],
        'get_invoice_status_distribution' => [true, null, null, false],
        'get_payment' => [true, null, null, false],
        'get_payment_method' => [true, null, null, false],
        'get_product' => [true, null, null, false],
        'get_quote' => [true, null, null, false],
        'get_recurring_invoice' => [true, null, null, false],
        'get_revenue_by_period' => [true, null, null, false],
        'get_tax_rate' => [true, null, null, false],
        'get_total_outstanding' => [true, null, null, false],
        'get_total_overdue' => [true, null, null, false],
        'list_client_tax_identifiers' => [true, null, null, false],
        'list_clients' => [true, null, null, false],
        'list_contacts' => [true, null, null, false],
        'list_invoices' => [true, null, null, false],
        'list_payment_methods' => [true, null, null, false],
        'list_payments' => [true, null, null, false],
        'list_products' => [true, null, null, false],
        'list_quotes' => [true, null, null, false],
        'list_recurring_invoices' => [true, null, null, false],
        'list_tax_rates' => [true, null, null, false],
        'list_workflow_transitions' => [true, null, null, false],
        'record_payment' => [false, false, false, true],
        'remove_client_tax_identifier' => [false, true, true, false],
        'send_invoice_reminder' => [false, false, false, true],
        'update_client' => [false, true, true, false],
        'update_contact' => [false, true, true, false],
        'update_product' => [false, true, true, false],
        'update_tax_rate' => [false, true, true, false],
    ];

    public function testEveryToolHasTitleAnnotationsAndOutputSchema(): void
    {
        $tools = $this->tools();
        self::assertNotEmpty($tools);

        $problems = [];

        foreach ($tools as $name => $tool) {
            if ($tool->title === null || $tool->title === '') {
                $problems[] = sprintf('%s: no title', $name);
            }

            if (! $tool->annotations instanceof ToolAnnotations || $tool->annotations->readOnlyHint === null) {
                $problems[] = sprintf('%s: no annotations with readOnlyHint', $name);
            } elseif ($tool->annotations->readOnlyHint !== (str_starts_with($name, 'get_') || str_starts_with($name, 'list_'))) {
                $problems[] = sprintf('%s: readOnlyHint must be true exactly for get_*/list_* tools', $name);
            }

            $hints = $tool->annotations instanceof ToolAnnotations
                ? [$tool->annotations->readOnlyHint, $tool->annotations->destructiveHint, $tool->annotations->idempotentHint, $tool->annotations->openWorldHint]
                : null;

            if (isset(self::HINTS[$name]) && $hints !== self::HINTS[$name]) {
                $problems[] = sprintf('%s: hints %s, expected %s', $name, json_encode($hints), json_encode(self::HINTS[$name]));
            }

            if ($tool->outputSchema === null) {
                $problems[] = sprintf('%s: no outputSchema', $name);
            }
        }

        self::assertSame([], $problems);
    }

    public function testEveryRegisteredToolHasExpectedHints(): void
    {
        $registered = array_keys($this->tools());
        $expected = array_keys(self::HINTS);
        sort($registered);
        sort($expected);

        self::assertSame($expected, $registered, 'Add every new tool to ToolMetadataTest::HINTS, and drop removed ones.');
    }

    /**
     * @return array<string, Tool>
     */
    private function tools(): array
    {
        // Building the server is what loads the builder's addTool() calls into the registry.
        self::getContainer()->get('mcp.server.solidinvoice');
        $registry = self::getContainer()->get('mcp.server.solidinvoice.registry');
        self::assertInstanceOf(Registry::class, $registry);

        return $registry->getTools()->references;
    }
}
