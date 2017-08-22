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

namespace SolidInvoice\ClientBundle\Action\Ajax\Address;

use SolidInvoice\ClientBundle\Entity\Address;
use SolidInvoice\ClientBundle\Form\Type\AddressType;
use SolidInvoice\CoreBundle\Response\AjaxResponse;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\CoreBundle\Traits\JsonTrait;
use SolidInvoice\CoreBundle\Traits\SaveableTrait;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

final class Edit implements AjaxResponse
{
    use SaveableTrait,
        JsonTrait;

    /**
     * @var FormFactoryInterface
     */
    private $factory;

    public function __construct(FormFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    public function __invoke(Request $request, Address $address)
    {
        $form = $this->factory->create(AddressType::class, $address, ['canDelete' => false]);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->save($address);
        }

        return new Template(
            'SolidInvoiceClientBundle:Ajax:address_edit.html.twig',
            [
                'form' => $form->createView(),
                'address' => $address,
            ]
        );
    }
}
