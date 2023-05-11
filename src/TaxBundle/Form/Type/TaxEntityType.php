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

namespace SolidInvoice\TaxBundle\Form\Type;

use SolidInvoice\CoreBundle\Form\DataTransformer\EntityUuidTransformer;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Repository\TaxRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use function array_combine;

final class TaxEntityType extends AbstractType
{
    private TaxRepository $repository;

    public function __construct(TaxRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new EntityUuidTransformer($this->repository->findAll()));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $values = $this->repository->findAll();

        $resolver->setDefault(
            'choices',
            array_combine(
                $values,
                array_map(static fn (Tax $tax) => $tax->getId()->toString(), $values),
            )
        );
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
