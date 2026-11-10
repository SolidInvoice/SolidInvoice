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

namespace SolidInvoice\CoreBundle\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use Symfony\Component\Uid\AbstractUid;

/**
 * @implements Rule<MethodCall>
 * @see \SolidInvoice\CoreBundle\Tests\PHPStan\NoRfc4122RuleTest
 */
final class NoRfc4122Rule implements Rule
{
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->name instanceof Identifier || $node->name->toLowerString() !== 'torfc4122') {
            return [];
        }

        if (! new ObjectType(AbstractUid::class)->isSuperTypeOf($scope->getType($node->var))->yes()) {
            return [];
        }

        return [
            RuleErrorBuilder::message('Use toBase58() instead of toRfc4122(): IDs are always base58 (AGENTS.md §9).')
                ->identifier('solidinvoice.noRfc4122')
                ->build(),
        ];
    }
}
