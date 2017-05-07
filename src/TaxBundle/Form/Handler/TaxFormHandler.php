<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\TaxBundle\Form\Handler;

use CSBill\CoreBundle\Response\FlashResponse;
use CSBill\CoreBundle\Templating\Template;
use CSBill\CoreBundle\Traits\SaveableTrait;
use CSBill\TaxBundle\Form\Type\TaxType;
use SolidWorx\FormHandler\FormHandlerInterface;
use SolidWorx\FormHandler\FormHandlerResponseInterface;
use SolidWorx\FormHandler\FormHandlerSuccessInterface;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class TaxFormHandler implements FormHandlerInterface, FormHandlerSuccessInterface, FormHandlerResponseInterface
{
    use SaveableTrait;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function getForm(FormFactoryInterface $factory = null, ...$options)
    {
        return $factory->create(TaxType::class, $options[0] ?? null);
    }

    /**
     * {@inheritdoc}
     */
    public function onSuccess($invoice, FormRequest $form): ?Response
    {
        $this->save($invoice);

        $route = $this->router->generate('_tax_rates');

        return new class($route) extends RedirectResponse implements FlashResponse {
            public function getFlash(): iterable
            {
                yield FlashResponse::FLASH_SUCCESS => 'Tax rate successfully saved';
            }
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse(FormRequest $formRequest)
    {
        return new Template('@CSBillTax/Default/form.html.twig', ['form' => $formRequest->getForm()->createView()]);
    }
}
