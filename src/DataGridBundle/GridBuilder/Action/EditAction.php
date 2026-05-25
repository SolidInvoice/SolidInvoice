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

namespace SolidInvoice\DataGridBundle\GridBuilder\Action;

use Override;

/**
 * @see \SolidInvoice\DataGridBundle\Tests\GridBuilder\Action\EditActionTest
 */
final class EditAction extends Action
{
    /**
     * @param array<string, mixed> $parameters
     */
    #[Override]
    public static function new(string $route, array $parameters = []): static
    {
        return new self()
            ->route($route, $parameters)
            ->label('Edit')
            ->icon('pencil');
    }
}
