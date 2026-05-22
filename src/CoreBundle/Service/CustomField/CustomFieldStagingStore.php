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

namespace SolidInvoice\CoreBundle\Service\CustomField;

use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use WeakMap;

/**
 * Carries denormalized custom-field values from CustomFieldsDenormalizer to
 * CustomFieldsStateProcessor, keyed by the parent entity instance.
 *
 * Uses WeakMap so entries are released automatically when the entity is
 * garbage-collected, which keeps the service safe under long-running
 * processes (FrankenPHP / Swoole / worker mode) where DI services live
 * across requests.
 */
final class CustomFieldStagingStore
{
    /**
     * @var WeakMap<object, array<string, array{field: CustomField, value: ?string}>>
     */
    private WeakMap $entries;

    public function __construct()
    {
        $this->entries = new WeakMap();
    }

    /**
     * @param array<string, array{field: CustomField, value: ?string}> $staged
     */
    public function stage(object $owner, array $staged): void
    {
        $this->entries[$owner] = $staged;
    }

    /**
     * @return array<string, array{field: CustomField, value: ?string}>|null
     */
    public function pull(object $owner): ?array
    {
        $staged = $this->entries[$owner] ?? null;
        if ($staged !== null) {
            unset($this->entries[$owner]);
        }

        return $staged;
    }
}
