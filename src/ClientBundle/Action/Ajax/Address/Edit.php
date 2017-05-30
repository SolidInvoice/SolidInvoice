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

namespace CSBill\ClientBundle\Action\Ajax\Address;

use CSBill\ClientBundle\Entity\Address;
use CSBill\ClientBundle\Form\Type\AddressType;
use CSBill\CoreBundle\Response\AjaxResponse;
use CSBill\CoreBundle\Templating\Template;
use CSBill\CoreBundle\Traits\JsonTrait;
use CSBill\CoreBundle\Traits\SaveableTrait;
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
            'CSBillClientBundle:Ajax:address_edit.html.twig',
            [
                'form' => $form->createView(),
                'address' => $address,
            ]
        );
    }
}
