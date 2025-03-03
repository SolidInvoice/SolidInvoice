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

namespace SolidInvoice\ClientBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @see \SolidInvoice\ClientBundle\Tests\Form\Type\CreditTypeTest
 */
class CreditType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {

    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'amount',
            MoneyType::class,
            [
                'help' => $this->translator->trans('client.modal.credit.tip', ['%amount%' => '-20']),
                'help_html' => true,
                'constraints' => new Assert\NotBlank(),
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'credit';
    }
}
