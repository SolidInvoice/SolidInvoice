<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Form\Type;

use SolidInvoice\CoreBundle\Generator\BillingIdGenerator\IdGeneratorInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use function array_combine;
use function array_keys;
use function array_map;
use function str_replace;

final class BillingIdConfigurationType extends AbstractType
{
    /**
     * @param ServiceLocator<IdGeneratorInterface> $generators
     */
    public function __construct(
        #[TaggedLocator(IdGeneratorInterface::class, defaultIndexMethod: 'getName')]
        private readonly ServiceLocator $generators,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $services = array_keys($this->generators->getProvidedServices());

        $strategies = array_combine(
            array_map(static fn (string $name): string => ucwords(str_replace('_', ' ', $name)), $services),
            $services
        );

        $resolver->setDefaults([
            'choices' => $strategies,
            'empty_data' => 'Default',
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
