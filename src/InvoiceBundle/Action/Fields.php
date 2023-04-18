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

namespace SolidInvoice\InvoiceBundle\Action;

use Money\Currency;
use SolidInvoice\CoreBundle\Form\FieldRenderer;
use SolidInvoice\CoreBundle\Traits\JsonTrait;
use SolidInvoice\InvoiceBundle\Form\Type\InvoiceType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

final class Fields
{
    use JsonTrait;

    private FormFactoryInterface $factory;

    private FieldRenderer $renderer;

    public function __construct(FormFactoryInterface $factory, FieldRenderer $renderer)
    {
        $this->factory = $factory;
        $this->renderer = $renderer;
    }

    public function __invoke(Request $request, string $currency)
    {
        $form = $this->factory->create(InvoiceType::class, null, ['currency' => new Currency($currency)]);

        return $this->json($this->renderer->render($form->createView(), 'children[items].vars[prototype]'));
    }
}
