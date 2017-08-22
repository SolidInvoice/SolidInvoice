<?php

declare(strict_types = 1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\QuoteBundle\Action;

use SolidInvoice\CoreBundle\Form\FieldRenderer;
use SolidInvoice\CoreBundle\Traits\JsonTrait;
use SolidInvoice\QuoteBundle\Form\Type\QuoteType;
use Money\Currency;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

final class Fields
{
    use JsonTrait;

    /**
     * @var FormFactoryInterface
     */
    private $factory;

    /**
     * @var FieldRenderer
     */
    private $renderer;

    public function __construct(FormFactoryInterface $factory, FieldRenderer $renderer)
    {
        $this->factory = $factory;
        $this->renderer = $renderer;
    }

    public function __invoke(Request $request, string $currency)
    {
        $form = $this->factory->create(QuoteType::class, null, ['currency' => new Currency($currency)]);

        return $this->json($this->renderer->render($form->createView(), 'children[items].vars[prototype]'));
    }
}
