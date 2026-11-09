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

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Body of POST /invoices/{id}/send and /quotes/{id}/send. Every field is optional.
 */
final class SendDocumentInput
{
    /**
     * Contact IRIs of the document's client. Defaults to the document's contacts.
     *
     * @var list<string>|null
     */
    #[Assert\All([new Assert\Type('string'), new Assert\NotBlank()])]
    #[Groups(['billing_api:send'])]
    public ?array $to = null;

    /**
     * @var list<string>
     */
    #[Assert\Count(max: 10)]
    #[Assert\All([new Assert\NotBlank(), new Assert\Email()])]
    #[Groups(['billing_api:send'])]
    public array $cc = [];

    /**
     * Replaces the configured subject for this send only.
     */
    #[Assert\Length(max: 255)]
    #[Groups(['billing_api:send'])]
    public ?string $subject = null;

    /**
     * A personal note shown above the standard email body.
     */
    #[Assert\Length(max: 2000)]
    #[Groups(['billing_api:send'])]
    public ?string $message = null;

    #[Groups(['billing_api:send'])]
    public bool $attachPdf = true;

    /**
     * Also send a copy to the signed-in user.
     */
    #[Groups(['billing_api:send'])]
    public bool $copyToMe = false;
}
