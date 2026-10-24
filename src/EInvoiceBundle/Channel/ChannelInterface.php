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

namespace SolidInvoice\EInvoiceBundle\Channel;

use SolidInvoice\EInvoiceBundle\Entity\EInvoiceDocument;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * A provider that sends and tracks an {@see EInvoiceDocument} on one platform.
 *
 * Four rules a new adapter must follow, not expressible in the signatures below:
 *
 * 1. `poll()` never throws "unsupported". A channel with nothing to poll (for example a
 *    Peppol access point that reports by webhook) returns
 *    `new TransmissionResult(TransmissionState::InProgress, pollAfter: ...)` — no change yet.
 * 2. Transient vs. permanent is expressed by the return/throw choice, not by an error code.
 *    Return {@see \SolidInvoice\EInvoiceBundle\Enum\TransmissionState::Failed} for a permanent
 *    platform refusal. Throw for a transient condition you want retried (timeout, 5xx, rate
 *    limit).
 * 3. Adapters own their own idempotency key. At-least-once delivery means `send()` can run
 *    twice for the same document. Use `$document->getId()` or `$document->getPayloadHash()`
 *    to dedupe provider-side.
 * 4. Credentials are never parameters. Read them from the vault inside the adapter itself.
 */
#[AutoconfigureTag(ChannelInterface::DI_TAG)]
interface ChannelInterface
{
    public const string DI_TAG = 'solidinvoice.einvoice.channel';

    public function getIdentifier(): string;

    public function getLabel(): TranslatableInterface;

    public function supports(EInvoiceDocument $document): bool;

    public function send(EInvoiceDocument $document): TransmissionResult;

    public function poll(EInvoiceDocument $document): TransmissionResult;
}
