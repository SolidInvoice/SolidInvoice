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

namespace SolidInvoice\SettingsBundle\Action\CustomField;

use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Repository\CustomFieldRepository;
use SolidInvoice\CoreBundle\Repository\CustomFieldValueRepository;
use Symfony\Bridge\Twig\Attribute\Template;

final class IndexAction
{
    public function __construct(
        private readonly CustomFieldRepository $fields,
        private readonly CustomFieldValueRepository $values,
    ) {
    }

    /**
     * @return array{client: list<array{field: CustomField, count: int}>, contact: list<array{field: CustomField, count: int}>}
     */
    #[Template('@SolidInvoiceSettings/CustomField/index.html.twig')]
    public function __invoke(): array
    {
        return [
            'client' => $this->buildRows(CustomFieldTarget::CLIENT),
            'contact' => $this->buildRows(CustomFieldTarget::CONTACT),
        ];
    }

    /**
     * @return list<array{field: CustomField, count: int}>
     */
    private function buildRows(CustomFieldTarget $target): array
    {
        $rows = [];
        foreach ($this->fields->findByTargetOrdered($target) as $field) {
            $rows[] = ['field' => $field, 'count' => $this->values->countByField($field)];
        }
        return $rows;
    }
}
