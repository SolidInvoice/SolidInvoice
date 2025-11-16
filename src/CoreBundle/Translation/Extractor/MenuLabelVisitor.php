<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Translation\Extractor;

use PhpParser\Node;
use PhpParser\NodeVisitor;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Translation\Extractor\Visitor\AbstractVisitor;

#[AutoconfigureTag('translation.extractor.visitor')]
final class MenuLabelVisitor extends AbstractVisitor implements NodeVisitor
{
    public function beforeTraverse(array $nodes)
    {
        return null;
    }

    public function enterNode(Node $node)
    {
        return null;
    }

    public function leaveNode(Node $node)
    {
        if (! $node instanceof Node\Expr\MethodCall) {
            return null;
        }

        // Check if addChild is called on MenuItemInterface, and get the first argument (which should be a string)
        if (! \is_string($node->name) && ! $node->name instanceof Node\Identifier) {
            return null;
        }

        $name = $node->name instanceof Node\Identifier ? $node->name->toString() : (string) $node->name;

        if ('addChild' === $name) {
            $firstNamedArgumentIndex = $this->nodeFirstNamedArgumentIndex($node);

            if (! $messages = $this->getStringArguments($node, 0 < $firstNamedArgumentIndex ? 0 : 'name')) {
                return null;
            }

            foreach ($messages as $message) {
                $this->addMessageToCatalogue($message, null, $node->getStartLine());
            }
        }
    }

    public function afterTraverse(array $nodes)
    {
        return null;
    }
}
