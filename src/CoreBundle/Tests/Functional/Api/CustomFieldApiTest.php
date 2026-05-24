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

namespace SolidInvoice\CoreBundle\Tests\Functional\Api;

use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use Zenstruck\Foundry\Test\Factories;

#[Group('functional')]
final class CustomFieldApiTest extends ApiTestCase
{
    use Factories;

    protected function getResourceClass(): string
    {
        return CustomField::class;
    }

    public function testCreateAndList(): void
    {
        $payload = [
            'target' => 'CLIENT',
            'label' => 'Department',
            'fieldKey' => 'department',
            'type' => 'text',
            'required' => false,
        ];

        $created = $this->requestPost('/api/custom-fields', $payload);

        self::assertSame('CLIENT', $created['target']);
        self::assertSame('Department', $created['label']);
        self::assertSame('department', $created['fieldKey']);
        self::assertSame('text', $created['type']);
        self::assertFalse($created['required']);
        self::assertSame(0, $created['position']);

        $list = $this->requestGetCollection('/api/custom-fields');
        self::assertGreaterThanOrEqual(1, $list['hydra:totalItems'] ?? $list['totalItems'] ?? 1);
    }

    public function testPatchAndDelete(): void
    {
        $created = $this->requestPost('/api/custom-fields', [
            'target' => 'CONTACT',
            'label' => 'Job Title',
            'fieldKey' => 'job_title',
            'type' => 'text',
        ]);

        $iri = $created['@id'] ?? '/api/custom-fields/' . $created['id'];

        $updated = $this->requestPatch($iri, ['label' => 'Title']);
        self::assertSame('Title', $updated['label']);

        $this->requestDelete($iri);
    }

    public function testFilterByTarget(): void
    {
        $this->requestPost('/api/custom-fields', [
            'target' => 'CLIENT',
            'label' => 'Tier',
            'fieldKey' => 'tier',
            'type' => 'text',
        ]);
        $this->requestPost('/api/custom-fields', [
            'target' => 'CONTACT',
            'label' => 'Department',
            'fieldKey' => 'department',
            'type' => 'text',
        ]);

        $clientFields = $this->requestGetCollection('/api/custom-fields?target=CLIENT');
        self::assertNotEmpty($clientFields['hydra:member'] ?? $clientFields['member'] ?? []);
        $items = $clientFields['hydra:member'] ?? $clientFields['member'] ?? [];
        foreach ($items as $row) {
            self::assertSame('CLIENT', $row['target']);
        }
    }
}
