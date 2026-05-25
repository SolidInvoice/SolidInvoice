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
use SolidInvoice\CoreBundle\Service\CustomField\CustomFieldStagingStore;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\Uid\Ulid;

/**
 * Persists the custom-field values staged in {@see CustomFieldStagingStore} by
 * the denormalizer once the parent entity has been written.
 *
 * @implements ProcessorInterface<object, object>
 */
#[AsDecorator(decorates: PersistProcessor::class)]
final readonly class CustomFieldsStateProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<object, object> $inner
     */
    public function __construct(
        private ProcessorInterface $inner,
        private EntityManagerInterface $em,
        private CustomFieldValueRepository $values,
        private FeatureGate $featureGate,
        private CustomFieldStagingStore $stagingStore,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $result = $this->inner->process($data, $operation, $uriVariables, $context);

        if (! $this->featureGate->isEnabled(Feature::CustomFields->value)) {
            return $result;
        }

        if (
            ! $data instanceof Client
            && ! $data instanceof Contact
            && ! $data instanceof Invoice
            && ! $data instanceof RecurringInvoice
            && ! $data instanceof Quote
        ) {
            return $result;
        }

        $staged = $this->stagingStore->pull($data);
        if ($staged === null || ! $data->getId() instanceof Ulid) {
            return $result;
        }

        $target = match (true) {
            $data instanceof Client => CustomFieldTarget::CLIENT,
            $data instanceof Contact => CustomFieldTarget::CONTACT,
            $data instanceof Invoice, $data instanceof RecurringInvoice => CustomFieldTarget::INVOICE,
            $data instanceof Quote => CustomFieldTarget::QUOTE,
        };
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
                $newValue = new CustomFieldValue()
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
