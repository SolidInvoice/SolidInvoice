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
use Symfony\Component\Form\FormView;

class BillingExtension extends \Twig\Extension\AbstractExtension
{
    /**
     * @var FieldRenderer
     */
    private $fieldRenderer;

    /**
     * @param FieldRenderer $fieldRenderer
     */
    public function __construct(FieldRenderer $fieldRenderer)
    {
        $this->fieldRenderer = $fieldRenderer;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig\TwigFunction('billing_fields', function (FormView $form) {
                return $this->fieldRenderer->render($form, 'children[items].vars[prototype]');
            }, ['is_safe' => ['html']]),
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
