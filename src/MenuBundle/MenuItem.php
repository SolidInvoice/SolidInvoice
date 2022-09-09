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

namespace SolidInvoice\MenuBundle;

use InvalidArgumentException;
use Knp\Menu\MenuItem as BaseItem;

/**
 * @see \SolidInvoice\MenuBundle\Tests\MenuItemTest
 */
class MenuItem extends BaseItem implements ItemInterface
{
    private const DIVIDER_KEY = 'divider';

    /**
     * @param ItemInterface|string|array<int|string, mixed> $child
     *
     * @return ItemInterface
     *
     * @throws InvalidArgumentException
     */
    public function addChild($child, array $options = []): \Knp\Menu\ItemInterface
    {
        if (\is_array($child) && [] === $options) {
            [$child, $options] = $child;
        }

        $options['attributes'] = $options['attributes'] ?? [];
        $options['attributes']['class'] = ($options['attributes']['class'] ?? '') . ' nav-item';
        $options['linkAttributes'] = $options['linkAttributes'] ?? [];
        $options['linkAttributes']['class'] = ($options['linkAttributes']['class'] ?? '') . ' nav-link';

        return parent::addChild($child, $options);
    }

    public function addDivider(string $type = ''): ItemInterface
    {
        $name = uniqid('', true);

        if ('' !== $type) {
            $type = '-' . $type;
        }

        return $this->addChild($name, ['extras' => [self::DIVIDER_KEY => $type]]);
    }

    public function addHeader(string $header): ItemInterface
    {
        return $this->addChild($header, ['attributes' => ['class' => 'nav-header']]);
    }

    public function isDivider(): bool
    {
        return null !== $this->getExtra(self::DIVIDER_KEY);
    }
}
