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

namespace SolidInvoice\McpBundle\Tests\Output\Fixtures;

use SolidInvoice\McpBundle\Mcp\Output\ListOutput;
use SolidInvoice\McpBundle\Mcp\Output\McpOutput;

/**
 * Tool methods for checking that #[McpOutput] matches the declared return type.
 */
final class ReturnTypeTools
{
    #[McpOutput(LineOutput::class)]
    public function matching(): LineOutput
    {
        return new LineOutput('x');
    }

    #[McpOutput(LineOutput::class, list: true)]
    public function matchingList(): ListOutput
    {
        return new ListOutput([], 1, 10, 0);
    }

    #[McpOutput(NestedOutput::class)]
    public function otherClass(): LineOutput
    {
        return new LineOutput('x');
    }

    #[McpOutput(LineOutput::class)]
    public function nullable(bool $none): ?LineOutput
    {
        return $none ? null : new LineOutput('x');
    }

    /**
     * @return LineOutput
     */
    #[McpOutput(LineOutput::class)]
    public function untyped()
    {
        return new LineOutput('x');
    }

    public function unmarked(): LineOutput
    {
        return new LineOutput('x');
    }

    #[McpOutput(LineOutput::class, list: true)]
    public function listReturningItem(): LineOutput
    {
        return new LineOutput('x');
    }

    #[McpOutput(LineOutput::class)]
    public function itemReturningList(): ListOutput
    {
        return new ListOutput([], 1, 10, 0);
    }
}
