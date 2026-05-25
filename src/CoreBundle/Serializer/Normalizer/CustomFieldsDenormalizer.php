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
use SolidInvoice\CoreBundle\Service\CustomField\CustomFieldStagingStore;
use SolidInvoice\CoreBundle\Service\CustomField\CustomFieldTypeResolver;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use function array_diff;
use function array_key_exists;
use function array_keys;
use function implode;
use function is_array;
use function method_exists;

#[AutoconfigureTag('serializer.normalizer')]
final class CustomFieldsDenormalizer implements DenormalizerAwareInterface, DenormalizerInterface
{
    private const string SKIP_KEY = self::class . '::skip';

    use DenormalizerAwareTrait;

    public function __construct(
        private readonly CustomFieldRepository $fields,
        private readonly CustomFieldTypeResolver $resolver,
        private readonly FeatureGate $featureGate,
        private readonly CustomFieldStagingStore $stagingStore,
    ) {
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $payload = is_array($data) ? ($data['customFields'] ?? null) : null;
        if (is_array($data)) {
            unset($data['customFields']);
        }

        if (! $this->featureGate->isEnabled(Feature::CustomFields->value)) {
            if (is_array($payload) && $payload !== []) {
                throw new UnexpectedValueException('Custom fields are not available on the current plan.');
            }

            $context[self::SKIP_KEY] = true;
            return $this->denormalizer->denormalize($data, $type, $format, $context);
        }

        $context[self::SKIP_KEY] = true;
        $object = $this->denormalizer->denormalize($data, $type, $format, $context);

        $target = match ($type) {
            Client::class => CustomFieldTarget::CLIENT,
            Contact::class => CustomFieldTarget::CONTACT,
            Invoice::class, RecurringInvoice::class => CustomFieldTarget::INVOICE,
            Quote::class => CustomFieldTarget::QUOTE,
            default => CustomFieldTarget::CONTACT,
        };
        $defs = [];
        foreach ($this->fields->findByTargetOrdered($target) as $def) {
            $defs[$def->getFieldKey()] = $def;
        }

        $payloadArray = is_array($payload) ? $payload : [];
        $unknown = array_diff(array_keys($payloadArray), array_keys($defs));
        if ($unknown !== []) {
            throw new UnexpectedValueException('Unknown custom field keys: ' . implode(', ', $unknown));
        }

        $isNew = ! (method_exists($object, 'getId') && $object->getId() !== null);

        $staged = [];
        foreach ($defs as $key => $def) {
            if (array_key_exists($key, $payloadArray)) {
                $staged[(string) $def->getId()] = [
                    'field' => $def,
                    'value' => $this->resolver->serialize($def, $payloadArray[$key]),
                ];
                continue;
            }

            if ($isNew && $def->getDefaultValue() !== null) {
                $staged[(string) $def->getId()] = [
                    'field' => $def,
                    'value' => $def->getDefaultValue(),
                ];
            }
        }

        if ($staged !== [] || is_array($payload)) {
            $this->stagingStore->stage($object, $staged);
        }

        return $object;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        if ($context[self::SKIP_KEY] ?? false) {
            return false;
        }

        return in_array($type, [Client::class, Contact::class, Invoice::class, RecurringInvoice::class, Quote::class], true);
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
