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

namespace SolidInvoice\CoreBundle\Translation\Extractor;

use PhpParser\Node;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitor;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Translation\Extractor\Visitor\AbstractVisitor;

/**
 * Collects translatable labels from KnpMenu `addChild()` calls.
 *
 * KnpMenu uses the first argument as the item label unless an explicit `label` option is
 * provided. This visitor mirrors that contract:
 *
 *  - `addChild('client.menu.add', [...])`            → extracts `client.menu.add`
 *  - `addChild('add', ['label' => 'client.add'])`    → extracts `client.add` (label wins)
 *  - `addChild('user', ['label' => $username])`      → extracts nothing (dynamic label)
 *
 * @see MenuLabelExtractor
 */
final class MenuLabelVisitor extends AbstractVisitor implements NodeVisitor
{
    private const string DOMAIN = 'messages';

    public function beforeTraverse(array $nodes): ?Node
    {
        return null;
    }

    public function enterNode(Node $node): ?Node
    {
        return null;
    }

    public function leaveNode(Node $node): ?Node
    {
        if (! $node instanceof MethodCall || ! $node->name instanceof Identifier) {
            return null;
        }

        if ('addChild' !== $node->name->toString()) {
            return null;
        }

        $args = $node->getArgs();

        [$labelOptionPresent, $labelOptionValue] = $this->resolveLabelOption($args[1]->value ?? null);

        if ($labelOptionPresent) {
            // An explicit `label` option overrides the name. Only extract it when static.
            if (null !== $labelOptionValue) {
                $this->addMessageToCatalogue($labelOptionValue, self::DOMAIN, $node->getStartLine());
            }

            return null;
        }

        if (null !== $name = $this->getStringValue($args[0]->value ?? null)) {
            $this->addMessageToCatalogue($name, self::DOMAIN, $node->getStartLine());
        }

        return null;
    }

    public function afterTraverse(array $nodes): ?Node
    {
        return null;
    }

    /**
     * @return array{0: bool, 1: string|null} Whether a `label` option exists, and its
     *                                         static string value (null when dynamic)
     */
    private function resolveLabelOption(?Node $options): array
    {
        if (! $options instanceof Array_) {
            return [false, null];
        }

        foreach ($options->items as $item) {
            if ($item instanceof ArrayItem
                && $item->key instanceof String_
                && 'label' === $item->key->value
            ) {
                return [true, $this->getStringValue($item->value)];
            }
        }

        return [false, null];
    }

    private function getStringValue(?Node $node): ?string
    {
        if ($node instanceof String_) {
            return $node->value;
        }

        if ($node instanceof Concat) {
            $left = $this->getStringValue($node->left);
            $right = $this->getStringValue($node->right);

            return null === $left || null === $right ? null : $left . $right;
        }

        if ($node instanceof ClassConstFetch && $node->class instanceof Name && $node->name instanceof Identifier) {
            try {
                $constant = new ReflectionClass($node->class->toString())->getReflectionConstant($node->name->toString());
            } catch (ReflectionException) {
                return null;
            }

            if (false !== $constant && \is_string($value = $constant->getValue())) {
                return $value;
            }
        }

        return null;
    }
}
