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
    use JsonTrait;
    use SaveableTrait;

    public function __construct(
        private readonly FormFactoryInterface $factory
    ) {
    }

    public function __invoke(Request $request, Address $address)
    {
        $form = $this->factory->create(AddressType::class, $address, ['canDelete' => false]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->save($address);
        }

        return new Template(
            '@SolidInvoiceClient/Ajax/address_edit.html.twig',
            [
                'form' => $form->createView(),
                'address' => $address,
            ]
        );
    }
}
