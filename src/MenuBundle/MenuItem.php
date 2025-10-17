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

use Knp\Menu\MenuItem as BaseItem;

/**
 * @see \SolidInvoice\MenuBundle\Tests\MenuItemTest
 */
class MenuItem extends BaseItem implements ItemInterface
{
    private const DIVIDER_KEY = 'divider';

    private const DROPDOWN_KEY = 'dropdown';

    /**
     * @param ItemInterface|string $child
     * @param array<string, mixed> $options
     */
    public function addChild($child, array $options = []): ItemInterface
    {
        $options['attributes'] ??= [];
        $options['attributes']['class'] = ($options['attributes']['class'] ?? '') . ' nav-item';
        $options['linkAttributes'] ??= [];
        $options['linkAttributes']['class'] = ($options['linkAttributes']['class'] ?? '') . ' nav-link';

        $result = parent::addChild($child, $options);

        assert($result instanceof ItemInterface);

        return $result;
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
        return $this->addChild($header, ['attributes' => ['class' => 'nav-item-header']]);
    }

    /**
     * Adds a dropdown section to the menu
     */
    public function addDropdownSection(string $label, ?string $icon = null): ItemInterface
    {
        $options = [
            'attributes' => ['class' => 'dropdown'],
            'extras' => [self::DROPDOWN_KEY => true],
            'childrenAttributes' => ['class' => 'dropdown-menu show'],
            'linkAttributes' => ['class' => 'nav-link dropdown-toggle show', 'data-bs-toggle' => 'dropdown', 'data-bs-auto-close' => 'false', 'aria-expanded' => 'true'],
        ];

        if ($icon !== null) {
            $options['extras']['icon'] = $icon;
        }

        return $this->addChild($label, $options);
    }

    public function isDivider(): bool
    {
        return null !== $this->getExtra(self::DIVIDER_KEY);
    }

    public function isDropdownSection(): bool
    {
        return $this->getExtra(self::DROPDOWN_KEY) === true;
    }
}
