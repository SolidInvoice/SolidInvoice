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

namespace SolidInvoice\ProductBundle\Form\Transformer;

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\ProductBundle\Entity\Product;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Uid\Ulid;
use function is_string;

/**
 * Carries a line's product through a hidden input as its id.
 *
 * An id that is malformed, unknown, or belongs to another company comes back as no product
 * rather than a form error: the field is hidden, so an error on it has nowhere to show, and
 * the link is informational (the line already holds copies of every value), so dropping it
 * loses nothing the invoice depends on. The lookup goes through the company SQL filter,
 * which is what keeps a foreign id from resolving.
 *
 * @implements DataTransformerInterface<Product, string>
 * @see \SolidInvoice\ProductBundle\Tests\Form\Transformer\ProductIdTransformerTest
 */
final readonly class ProductIdTransformer implements DataTransformerInterface
{
    public function __construct(
        private ManagerRegistry $registry,
    ) {
    }

    public function transform(mixed $value): string
    {
        return $value instanceof Product ? (string) $value->getId() : '';
    }

    public function reverseTransform(mixed $value): ?Product
    {
        if (! is_string($value) || ! Ulid::isValid($value, Ulid::FORMAT_BASE_32)) {
            return null;
        }

        return $this->registry->getManager()->find(Product::class, Ulid::fromString($value));
    }
}
