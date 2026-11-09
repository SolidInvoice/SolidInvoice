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
 * Every tool the server registers must describe itself: a title, annotations with a read-only
 * hint, and an output schema. Read from the live registry, so a tool added later without them
 * fails here.
 */
#[CoversNothing]
#[Group('functional')]
final class ToolMetadataTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

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

            if ($tool->outputSchema === null) {
                $problems[] = sprintf('%s: no outputSchema', $name);
            }
        }

        self::assertSame([], $problems);
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
