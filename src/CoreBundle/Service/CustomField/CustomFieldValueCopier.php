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

use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomFieldValue;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Repository\CustomFieldRepository;
use SolidInvoice\CoreBundle\Repository\CustomFieldValueRepository;
use Symfony\Component\Uid\Ulid;

/**
 * Copies custom field values between records.
 *
 * Used to propagate values from recurring invoices to generated invoices (same
 * target) and from quotes to invoices (field-key matched, different target).
 *
 * Caller is responsible for flushing the EntityManager.
 */
final readonly class CustomFieldValueCopier
{
    public function __construct(
        private CustomFieldRepository $fields,
        private CustomFieldValueRepository $values,
        private EntityManagerInterface $em,
    ) {
    }

    public function copy(
        CustomFieldTarget $sourceTarget,
        Ulid $sourceId,
        CustomFieldTarget $destTarget,
        Ulid $destId,
    ): void {
        $sourceValues = $this->values->findForRecord($sourceTarget, $sourceId);
        if ($sourceValues === []) {
            return;
        }

        $destFieldsByKey = [];
        if ($sourceTarget !== $destTarget) {
            foreach ($this->fields->findByTargetOrdered($destTarget) as $def) {
                $destFieldsByKey[(string) $def->getFieldKey()] = $def;
            }
        }

        foreach ($sourceValues as $sourceValue) {
            $sourceField = $sourceValue->getField();
            if ($sourceField === null) {
                continue;
            }

            if ($sourceTarget === $destTarget) {
                $destField = $sourceField;
            } else {
                $destField = $destFieldsByKey[(string) $sourceField->getFieldKey()] ?? null;
                if ($destField === null) {
                    continue;
                }
            }

            $copy = new CustomFieldValue()
                ->setField($destField)
                ->setTarget($destTarget)
                ->setTargetId($destId)
                ->setValue($sourceValue->getValue());

            $this->em->persist($copy);
        }
    }
}
