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

namespace SolidInvoice\CoreBundle\Traits\Entity;

use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Who the last send in this request reached. Not persisted: only the send endpoints expose it,
 * through the `billing_api:sent` group.
 */
trait SentTo
{
    /**
     * @var list<string>|null
     */
    #[Groups(['billing_api:sent'])]
    private ?array $sentTo = null;

    /**
     * @return list<string>|null
     */
    public function getSentTo(): ?array
    {
        return $this->sentTo;
    }

    /**
     * @param list<string> $sentTo
     */
    public function setSentTo(array $sentTo): void
    {
        $this->sentTo = $sentTo;
    }
}
