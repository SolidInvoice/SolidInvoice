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

namespace SolidInvoice\EInvoiceBundle\Tests\Conformance\Corpus;

use RuntimeException;
use function sprintf;

final class CorpusNotFetchedException extends RuntimeException
{
    public static function missing(CorpusEntry $entry): self
    {
        return new self(sprintf(
            'The "%s" conformance corpus is not fetched. Run "composer conformance:fetch" to download it.',
            $entry->id,
        ));
    }

    public static function stale(CorpusEntry $entry, ?string $fetchedPin): self
    {
        return new self(sprintf(
            'The "%s" conformance corpus is stale: it holds %s but corpus.lock.json pins %s. Run "composer conformance:fetch" to update it.',
            $entry->id,
            null === $fetchedPin ? 'an unreadable pin' : sprintf('"%s"', $fetchedPin),
            $entry->pin,
        ));
    }
}
