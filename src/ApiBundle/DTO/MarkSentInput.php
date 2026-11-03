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

namespace SolidInvoice\ApiBundle\DTO;

use SolidInvoice\CoreBundle\Enum\SendChannel;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Body of POST /invoices/{id}/mark-sent: how the invoice was shared outside the app.
 */
final class MarkSentInput
{
    #[Assert\NotIdenticalTo(SendChannel::Email, message: 'document.mark_sent.channel_email')]
    #[Groups(['billing_api:send'])]
    public SendChannel $channel = SendChannel::Link;
}
