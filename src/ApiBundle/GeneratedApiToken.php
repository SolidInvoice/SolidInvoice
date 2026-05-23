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

namespace SolidInvoice\ApiBundle;

use SolidInvoice\UserBundle\Entity\ApiToken;

/**
 * The plaintext token is only available at creation time — it is never
 * persisted nor recoverable from the database afterwards.
 */
final readonly class GeneratedApiToken
{
    public function __construct(
        public ApiToken $token,
        public string $plaintext,
    ) {
    }
}
