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

namespace SolidInvoice\CoreBundle\State;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomFieldValue;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Repository\CustomFieldValueRepository;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use function is_array;

/**
 * Reads `__customFieldsStaged` from Client/Contact (set by CustomFieldsDenormalizer)
 * and persists `CustomFieldValue` rows after the parent is saved.
 *
 * @implements ProcessorInterface<object, object>
 */
#[AsDecorator(decorates: PersistProcessor::class)]
final class CustomFieldsStateProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<object, object> $inner
     */
    public function __construct(
        private readonly ProcessorInterface $inner,
        private readonly EntityManagerInterface $em,
        private readonly CustomFieldValueRepository $values,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $result = $this->inner->process($data, $operation, $uriVariables, $context);

        if (! ($data instanceof Client || $data instanceof Contact)) {
            return $result;
        }

        $staged = $data->__customFieldsStaged ?? null;
        if (! is_array($staged) || $data->getId() === null) {
            return $result;
        }

        $target = $data instanceof Client ? CustomFieldTarget::CLIENT : CustomFieldTarget::CONTACT;
        $existing = [];
        foreach ($this->values->findForRecord($target, $data->getId()) as $v) {
            $existing[(string) $v->getField()->getId()] = $v;
        }

        foreach ($staged as $fieldIdStr => $entry) {
            /** @var CustomField $def */
            $def = $entry['field'];
            $value = $entry['value']; // string|null
            $existingValue = $existing[$fieldIdStr] ?? null;

            if ($value === null) {
                if ($existingValue !== null) {
                    $this->em->remove($existingValue);
                }
                continue;
            }

            if ($existingValue === null) {
                $newValue = (new CustomFieldValue())
                    ->setField($def)
                    ->setTarget($target)
                    ->setTargetId($data->getId())
                    ->setValue($value)
                    ->setCompany($data->getCompany());
                $this->em->persist($newValue);
            } else {
                $existingValue->setValue($value);
            }
        }

        $this->em->flush();
        return $result;
    }
}
