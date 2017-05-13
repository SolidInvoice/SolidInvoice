<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DataGridBundle\Routing\Loader;

use CSBill\CoreBundle\Routing\Loader\AbstractDirectoryLoader;

class GridRouteLoader extends AbstractDirectoryLoader
{
    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null): bool
    {
        return is_string($resource) && 'grid' === $type && '@' === $resource[0];
    }
}
