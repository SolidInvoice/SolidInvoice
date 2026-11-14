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

namespace SolidInvoice\ProductBundle\Form\Type;

use Brick\Math\BigDecimal;
use Override;
use SolidInvoice\ProductBundle\Entity\Product;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;
use function rtrim;
use function sprintf;
use function str_contains;

/**
 * The "+ Add Product" picker on the invoice, quote and recurring invoice editors.
 *
 * @extends AbstractType<mixed>
 * @see \SolidInvoice\ProductBundle\Tests\Form\Type\ProductAutocompleteTypeTest
 */
#[AsEntityAutocompleteField]
final class ProductAutocompleteType extends AbstractType
{
    public function __construct(
        private readonly FeatureGate $featureGate,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Product::class,
            'searchable_fields' => ['name', 'description'],
            // The price is a plain number: it has no currency until it lands on a document.
            'choice_label' => static fn (Product $product): string => sprintf('%s — %s', $product->getName(), self::plain($product->getPrice())),
            'placeholder' => 'product.picker.placeholder',
            // The autocomplete bundle only translates this in its own domain, so it is translated here.
            'no_results_found_text' => $this->translator->trans('product.picker.no_results'),
            'max_results' => 10,
            'required' => false,
            // Gates the autocomplete endpoint itself, not just the editor that renders the field.
            'security' => fn (): bool => $this->featureGate->isEnabled(Feature::Products->value),
        ]);
    }

    /**
     * A decimal as a plain number: "4500.50" stays, "4500.000000" becomes "4500".
     */
    private static function plain(BigDecimal $number): string
    {
        $string = (string) $number;

        return str_contains($string, '.') ? rtrim(rtrim($string, '0'), '.') : $string;
    }

    #[Override]
    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
