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

namespace SolidInvoice\MoneyBundle\Form\Extension;

use Money\Currency;
use SolidInvoice\MoneyBundle\Form\DataTransformer\ViewTransformer;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MoneyExtension extends AbstractTypeExtension
{
    public function __construct(
        private readonly SystemConfig $config
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addViewTransformer(new ViewTransformer(), true);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('currency', $this->config->getCurrency());
        $resolver->setAllowedTypes('currency', [Currency::class]);
    }

    public static function getExtendedTypes(): iterable
    {
        yield MoneyType::class;
    }
}
