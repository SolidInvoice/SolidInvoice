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
use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Repository\CustomFieldRepository;
use SolidInvoice\CoreBundle\Service\CustomField\CustomFieldTypeResolver;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use function array_diff;
use function array_keys;
use function implode;
use function is_array;

#[AutoconfigureTag('serializer.normalizer')]
final class CustomFieldsDenormalizer implements DenormalizerAwareInterface, DenormalizerInterface
{
    private const SKIP_KEY = self::class . '::skip';

    use DenormalizerAwareTrait;

    public function __construct(
        private readonly CustomFieldRepository $fields,
        private readonly CustomFieldTypeResolver $resolver,
    ) {
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $payload = is_array($data) ? ($data['customFields'] ?? null) : null;
        if (is_array($data)) {
            unset($data['customFields']);
        }

        $context[self::SKIP_KEY] = true;
        $object = $this->denormalizer->denormalize($data, $type, $format, $context);

        if (! is_array($payload)) {
            return $object;
        }

        $target = $type === Client::class ? CustomFieldTarget::CLIENT : CustomFieldTarget::CONTACT;
        $defs = [];
        foreach ($this->fields->findByTargetOrdered($target) as $def) {
            $defs[$def->getFieldKey()] = $def;
        }

        $unknown = array_diff(array_keys($payload), array_keys($defs));
        if ($unknown !== []) {
            throw new UnexpectedValueException('Unknown custom field keys: ' . implode(', ', $unknown));
        }

        $staged = [];
        foreach ($payload as $key => $raw) {
            /** @var CustomField $def */
            $def = $defs[$key];
            $staged[(string) $def->getId()] = [
                'field' => $def,
                'value' => $this->resolver->serialize($def, $raw),
            ];
        }

        $object->__customFieldsStaged = $staged;

        return $object;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        if ($context[self::SKIP_KEY] ?? false) {
            return false;
        }
        return $type === Client::class || $type === Contact::class;
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [Client::class => false, Contact::class => false];
    }
}
