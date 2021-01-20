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

class MenuItem extends BaseItem implements ItemInterface
{
    /**
     * @param \Knp\Menu\ItemInterface|string|array $child
     *
     * @return \Knp\Menu\ItemInterface|string
     *
     * @throws InvalidArgumentException
     */
    public function addChild($child, array $options = [])
    {
        if (\is_array($child) && [] === $options) {
            [$child, $options] = $child;
        }

        $options['attributes'] = $options['attributes'] ?? [];
        $options['attributes']['class'] = ($options['attributes']['class'] ?? '').' nav-item';
        $options['linkAttributes'] = $options['linkAttributes'] ?? [];
        $options['linkAttributes']['class'] = ($options['linkAttributes']['class'] ?? '').' nav-link';

        return parent::addChild($child, $options);
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    public function addDivider(string $type = '')
    {
        $name = uniqid();

        if (!empty($type)) {
            $type = '-'.$type;
        }

        return $this->addChild($name, ['extras' => ['divider' => $type]]);
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    public function addHeader(string $header)
    {
        return $this->addChild($header, ['attributes' => ['class' => 'nav-header']]);
    }

    /**
     * {@inheritdoc}
     */
    public function isDivider(): bool
    {
        return null !== $this->getExtra('divider');
    }
}
