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

namespace SolidInvoice\CoreBundle\Serializer\Normalizer;

use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Repository\CustomFieldRepository;
use SolidInvoice\CoreBundle\Repository\CustomFieldValueRepository;
use SolidInvoice\CoreBundle\Service\CustomField\CustomFieldTypeResolver;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AutoconfigureTag('serializer.normalizer')]
final class CustomFieldsNormalizer implements NormalizerAwareInterface, NormalizerInterface
{
    use NormalizerAwareTrait;

    private const SKIP_KEY = self::class . '::skip';

    public function __construct(
        private readonly CustomFieldRepository $fields,
        private readonly CustomFieldValueRepository $values,
        private readonly CustomFieldTypeResolver $resolver,
        private readonly FeatureGate $featureGate,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        $context[self::SKIP_KEY] = true;
        $data = $this->normalizer->normalize($object, $format, $context);

        if (! is_array($data)) {
            return ['customFields' => (object) []];
        }

        $target = match (true) {
            $object instanceof Client => CustomFieldTarget::CLIENT,
            $object instanceof Contact => CustomFieldTarget::CONTACT,
            $object instanceof Invoice, $object instanceof RecurringInvoice => CustomFieldTarget::INVOICE,
            $object instanceof Quote => CustomFieldTarget::QUOTE,
            default => CustomFieldTarget::CONTACT,
        };
        $defs = $this->fields->findByTargetOrdered($target);

        if ($defs === [] || $object->getId() === null) {
            $data['customFields'] = (object) [];
            return $data;
        }

        $byField = [];
        foreach ($this->values->findForRecord($target, $object->getId()) as $v) {
            $byField[(string) $v->getField()->getId()] = $v;
        }

        $custom = [];
        foreach ($defs as $def) {
            $value = $byField[(string) $def->getId()] ?? null;
            $custom[$def->getFieldKey()] = $this->resolver->deserialize($def, $value?->getValue());
        }
        $data['customFields'] = $custom;

        return $data;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        if ($context[self::SKIP_KEY] ?? false) {
            return false;
        }
        if (! $this->featureGate->isEnabled(Feature::CustomFields->value)) {
            return false;
        }
        return $data instanceof Client
            || $data instanceof Contact
            || $data instanceof Invoice
            || $data instanceof RecurringInvoice
            || $data instanceof Quote;
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            Client::class => false,
            Contact::class => false,
            Invoice::class => false,
            RecurringInvoice::class => false,
            Quote::class => false,
        ];
    }
}
