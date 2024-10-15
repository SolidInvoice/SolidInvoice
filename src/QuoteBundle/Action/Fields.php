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

namespace SolidInvoice\QuoteBundle\Action;

use Money\Currency;
use SolidInvoice\CoreBundle\Form\FieldRenderer;
use SolidInvoice\CoreBundle\Traits\JsonTrait;
use SolidInvoice\QuoteBundle\Form\Type\QuoteType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class Fields
{
    use JsonTrait;

    public function __construct(
        private readonly FormFactoryInterface $factory,
        private readonly FieldRenderer $renderer
    ) {
    }

    public function __invoke(Request $request, string $currency): JsonResponse
    {
        $form = $this->factory->create(QuoteType::class, null, ['currency' => new Currency($currency)]);

        return $this->json($this->renderer->render($form->createView(), 'children[lines].vars[prototype]'));
    }
}
