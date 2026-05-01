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

use Mcp\Server\Session\Session;

/**
 * Workaround for an upstream bug in mcp/sdk v0.4.0 where {@see Session::save()}
 * dereferences the private `$data` property without going through the lazy
 * `readData()` initializer. When a request handler bails before any
 * get/set/hydrate touches the session — which happens, for example, when the
 * registry has zero tools — the subsequent `$session->save()` triggers a fatal
 * "Typed property must not be accessed before initialization" error.
 *
 * Tracked upstream; remove this class once mcp/sdk ships a fix and the
 * dependency constraint is bumped.
 */
final class SafeSession extends Session
{
    public function save(): bool
    {
        // Force the private `$data` property to materialise via the public
        // accessor before parent::save() reads it directly.
        $this->all();

        return parent::save();
    }
}
