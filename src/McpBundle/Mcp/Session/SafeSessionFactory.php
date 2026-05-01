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

namespace SolidInvoice\McpBundle\Mcp\Session;

use Mcp\Server\Session\SessionFactoryInterface;
use Mcp\Server\Session\SessionInterface;
use Mcp\Server\Session\SessionStoreInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV4;

final class SafeSessionFactory implements SessionFactoryInterface
{
    public function create(SessionStoreInterface $store): SessionInterface
    {
        return new SafeSession($store, new UuidV4());
    }

    public function createWithId(Uuid $id, SessionStoreInterface $store): SessionInterface
    {
        return new SafeSession($store, $id);
    }
}
