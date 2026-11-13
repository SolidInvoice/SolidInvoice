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
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;
use SolidInvoice\CoreBundle\Billing\LineName;
use SolidInvoice\CoreBundle\Form\Transformer\QuantityTransformer;
use SolidInvoice\ProductBundle\Entity\Product;
use SolidInvoice\TaxBundle\Entity\Tax;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use function max;
use function str_replace;
use function trim;

/**
 * @see \SolidInvoice\ProductBundle\Tests\Form\Type\ProductTypeTest
 * @extends AbstractType<Product>
 */
class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class, [
            'label' => 'label.name',
            'attr' => ['maxlength' => LineName::MAX_LENGTH],
        ]);

        $builder->add('description', TextareaType::class, [
            'label' => 'label.description',
            'required' => false,
        ]);

        // A major-unit decimal with no currency, so deliberately not a MoneyType. NumberType's own
        // view transformer would round-trip through a float and pad to `scale` digits ("19.990000"),
        // so it is swapped for one that goes straight between the input string and a BigDecimal.
        $builder->add('price', NumberType::class, [
            'label' => 'product.form.price',
            'html5' => true,
        ]);

        $builder->get('price')
            ->resetViewTransformers()
            ->addViewTransformer(new CallbackTransformer(
                static function (?BigDecimal $price): string {
                    if (! $price instanceof BigDecimal) {
                        return '';
                    }

                    // Keep at least two decimals ("19.90"), but never pad beyond what was stored.
                    return (string) $price->toScale(max(2, $price->strippedOfTrailingZeros()->getScale()), RoundingMode::Unnecessary);
                },
                static function (?string $price): ?BigDecimal {
                    $price = trim(str_replace(',', '.', (string) $price));

                    if ($price === '') {
                        return null;
                    }

                    try {
                        return BigDecimal::of($price);
                    } catch (MathException $e) {
                        throw new TransformationFailedException($e->getMessage(), $e->getCode(), $e);
                    }
                },
            ));

        $builder->add('quantity', NumberType::class, [
            'label' => 'product.form.quantity',
            'empty_data' => '1',
        ]);

        // NumberType's own view transformer round-trips through a float, which would undo
        // the exact-decimal quantity the entity holds.
        $builder->get('quantity')
            ->resetViewTransformers()
            ->addViewTransformer(new QuantityTransformer());

        $builder->add('tax', EntityType::class, [
            'label' => 'product.form.tax',
            'class' => Tax::class,
            'required' => false,
            'placeholder' => 'product.form.no_tax',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Product::class]);
    }
}
