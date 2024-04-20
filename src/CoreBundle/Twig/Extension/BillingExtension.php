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

namespace SolidInvoice\CoreBundle\Twig\Extension;

use Brick\Math\BigDecimal;
use SolidInvoice\CoreBundle\Form\FieldRenderer;
use SolidInvoice\MoneyBundle\Calculator;
use Symfony\Component\Form\FormView;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class BillingExtension extends AbstractExtension
{
    public function __construct(
        private readonly FieldRenderer $fieldRenderer,
        private readonly Calculator $calculator
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('billing_fields', fn (FormView $form) => $this->fieldRenderer->render($form, 'children[items].vars[prototype]'), ['is_safe' => ['html']]),
            new TwigFunction('discount', fn ($entity): BigDecimal => $this->calculator->calculateDiscount($entity)),
        ];
    }
}
