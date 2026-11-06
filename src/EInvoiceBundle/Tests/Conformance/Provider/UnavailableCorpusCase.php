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

namespace SolidInvoice\EInvoiceBundle\Tests\Conformance\Provider;

use SolidInvoice\EInvoiceBundle\Tests\Conformance\Case\ConformanceCase;
use SolidInvoice\EInvoiceBundle\Tests\Conformance\Corpus\CorpusEntry;
use function sprintf;

/**
 * The single synthetic row a provider yields when its corpus is not fetched. A provider must
 * never throw for this: PHPUnit treats a throwing data provider as an error, which would make
 * a clean checkout without any corpus fetched red instead of skipped.
 */
final readonly class UnavailableCorpusCase implements ConformanceCase
{
    private function __construct(
        private string $id,
        public string $message,
    ) {
    }

    public static function for(CorpusEntry $entry): self
    {
        return new self(
            sprintf('%s (corpus not fetched)', $entry->id),
            sprintf('The "%s" conformance corpus is not fetched. Run "composer conformance:fetch".', $entry->id),
        );
    }

    public function id(): string
    {
        return $this->id;
    }
}
