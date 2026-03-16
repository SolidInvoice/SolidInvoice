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

namespace SolidInvoice\TimeTrackingBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Transforms between seconds (int) and "HH:MM" string.
 *
 * @implements DataTransformerInterface<int, string>
 */
final class DurationTransformer implements DataTransformerInterface
{
    /**
     * Transform seconds (int) to "HH:MM" string for the form view.
     */
    public function transform(mixed $value): string
    {
        if ($value === null || $value === 0) {
            return '00:00';
        }

        $hours = (int) floor($value / 3600);
        $minutes = (int) floor(($value % 3600) / 60);

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    /**
     * Transform "HH:MM" string back to seconds (int).
     */
    public function reverseTransform(mixed $value): int
    {
        if (! is_string($value) || $value === '') {
            return 0;
        }

        if (! preg_match('/^(\d+):([0-5]\d)$/', $value, $matches)) {
            throw new TransformationFailedException('Invalid duration format. Expected HH:MM.');
        }

        return ((int) $matches[1] * 3600) + ((int) $matches[2] * 60);
    }
}
