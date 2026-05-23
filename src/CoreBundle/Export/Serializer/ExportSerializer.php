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

namespace SolidInvoice\CoreBundle\Export\Serializer;

use SolidInvoice\CoreBundle\Export\Enum\ExportFormat;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Facade over a dedicated Symfony Serializer configured for export output.
 *
 * The inner serializer is composed in CoreBundle service config and is
 * independent of API Platform's normalizer chain, so it produces raw,
 * predictable values suitable for user-facing data exports.
 */
final readonly class ExportSerializer
{
    public function __construct(
        private SerializerInterface&NormalizerInterface&EncoderInterface $inner,
    ) {
    }

    /**
     * Encode an already-normalized array payload (e.g. grid rows) to the given format.
     *
     * @param array<mixed> $data
     * @param array<string, mixed> $context
     */
    public function encode(array $data, ExportFormat $format, array $context = []): string
    {
        return $this->inner->encode($data, $format->encoderFormat(), $context);
    }

    /**
     * Fully serialize an object graph to the given format.
     *
     * @param array<string, mixed> $context
     */
    public function serialize(mixed $data, ExportFormat $format, array $context = []): string
    {
        return $this->inner->serialize($data, $format->encoderFormat(), $context);
    }

    /**
     * Normalize a value for the given format without encoding it.
     *
     * @param array<string, mixed> $context
     */
    public function normalize(mixed $data, ExportFormat $format, array $context = []): mixed
    {
        return $this->inner->normalize($data, $format->encoderFormat(), $context);
    }
}
