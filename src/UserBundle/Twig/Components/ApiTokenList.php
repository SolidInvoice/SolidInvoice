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

namespace SolidInvoice\UserBundle\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class ApiTokenList
{
    use DefaultActionTrait;

    public int $refreshKey = 0;

    #[LiveListener(CreateApiToken::API_TOKEN_CREATED_EVENT)]
    #[LiveListener('api.token.revoked')]
    public function refresh(): void
    {
        // Increment the refresh key to force the DataGrid to re-render
        ++$this->refreshKey;
    }
}
