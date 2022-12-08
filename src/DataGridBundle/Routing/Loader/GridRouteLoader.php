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

namespace SolidInvoice\DataGridBundle\Routing\Loader;

use SolidInvoice\CoreBundle\Routing\Loader\AbstractDirectoryLoader;

class GridRouteLoader extends AbstractDirectoryLoader
{
    /**
     * @param mixed $resource
     */
    public function supports($resource, string $type = null): bool
    {
        return is_string($resource) && 'grid' === $type && '@' === $resource[0];
    }
}
