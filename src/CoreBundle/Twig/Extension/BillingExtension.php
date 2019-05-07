<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Twig\Extension;

use SolidInvoice\CoreBundle\Form\FieldRenderer;
use SolidInvoice\MoneyBundle\Calculator;
use Symfony\Component\Form\FormView;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class BillingExtension extends AbstractExtension
{
    /**
     * @var FieldRenderer
     */
    private $fieldRenderer;

    /**
     * @var Calculator
     */
    private $calculator;

    public function __construct(FieldRenderer $fieldRenderer, Calculator $calculator)
    {
        $this->fieldRenderer = $fieldRenderer;
        $this->calculator = $calculator;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('billing_fields', function (FormView $form) {
                return $this->fieldRenderer->render($form, 'children[items].vars[prototype]');
            }, ['is_safe' => ['html']]),
            new TwigFunction('discount', [$this->calculator, 'calculateDiscount']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'twig.billing.extension';
    }
}
