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

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * @extends AbstractType<mixed>
 */
class TaxNumberType extends AbstractType
{
    #[Override]
    public function getParent()
    {
        return TextType::class;
    }

    #[Override]
    public function getBlockPrefix()
    {
        return 'tax_number';
    }
}
