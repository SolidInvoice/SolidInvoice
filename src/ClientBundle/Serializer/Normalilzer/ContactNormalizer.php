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

namespace SolidInvoice\ClientBundle\Serializer\Normalilzer;

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use function is_array;
use function sprintf;

/**
 * @see \SolidInvoice\ClientBundle\Tests\Serializer\Normalizer\ContactNormalizerTest
 */
final class ContactNormalizer implements NormalizerAwareInterface, NormalizerInterface, DenormalizerAwareInterface, DenormalizerInterface
{
    use NormalizerAwareTrait;
    use DenormalizerAwareTrait;

    public function __construct(
        private readonly ManagerRegistry $registry,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     * @throws ExceptionInterface
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): Contact
    {
        $contact = $this->denormalizer->denormalize($data, $type, $format, $context + [self::class => true]);

        if (isset($context['uri_variables']['clientId'])) {
            $clientId = $context['uri_variables']['clientId'];
            // findOneBy() and not find(), so the CompanyFilter applies, matching what
            // ContactPersistProcessor already does rather than relying on it.
            $client = $this->registry->getRepository(Client::class)
                ->findOneBy(['id' => $clientId]);

            // The post operation does not read an existing contact first, so refusing a
            // contact whose client is not there is this normalizer's job rather than the
            // framework's. Without it setClient() fatals on null.
            if (! $client instanceof Client) {
                throw new NotFoundHttpException(sprintf('Client "%s" not found.', $clientId));
            }

            $contact->setClient($client);
        }

        return $contact;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return Contact::class === $type && is_array($data) && ! isset($context[self::class]);
    }

    /**
     * @param array<string, mixed> $context
     * @throws ExceptionInterface
     * @return array<string, mixed>
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        return $this->normalizer->normalize($object, $format, $context + [self::class => true]);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Contact && ! isset($context[self::class]);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Contact::class => false,
        ];
    }
}
