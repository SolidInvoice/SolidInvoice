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

namespace SolidInvoice\CoreBundle\Tests\Fixtures\Menu;

use Knp\Menu\ItemInterface;
use SolidWorx\Platform\PlatformBundle\Attributes\Menu\MenuBuilder;

/**
 * Fixture used by MenuLabelExtractorTest. It exercises the menu-builder patterns
 * the extractor must understand: a plain key, a key with options, a literal "label"
 * option (which overrides the name), and a dynamic "label" option (which must be skipped).
 */
final class SampleMenu
{
    #[MenuBuilder(name: 'sidebar')]
    public function sidebar(ItemInterface $menu): void
    {
        $section = $menu->addChild('sample.menu.main', ['extras' => ['icon' => 'device-laptop']]);

        $section->addChild('sample.menu.list', ['route' => '_sample_list']);

        $section->addChild(
            'sample.menu.add',
            ['label' => 'sample.menu.add.label', 'route' => '_sample_add'],
        );

        // Named arguments, in and out of declaration order.
        $section->addChild(child: 'sample.menu.named');
        $section->addChild(child: 'sample.menu.reordered', options: ['route' => '_reordered']);

        // Class constant resolved to its string value.
        $section->addChild(SampleMenuLabels::DASHBOARD, ['route' => '_sample_dashboard']);

        // Dynamic label (e.g. a username) — neither the name nor the label is a static
        // string, so nothing translatable should be extracted from this call.
        $username = 'jane';
        $section->addChild(
            'dynamic',
            ['label' => $username, 'allow_safe_labels' => true],
        );
    }
}
