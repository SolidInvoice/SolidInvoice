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

namespace CSBill\ClientBundle\Action;

use CSBill\ClientBundle\Entity\Client;
use CSBill\ClientBundle\Form\Handler\ClientEditFormHandler;
use SolidWorx\FormHandler\FormHandler;
use SolidWorx\FormHandler\FormRequest;
use Symfony\Component\HttpFoundation\Request;

class Edit
{
    /**
     * @var FormHandler
     */
    private $handler;

    /**
     * @param FormHandler $handler
     */
    public function __construct(FormHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @param Request $request
     * @param Client  $client
     *
     * @return FormRequest
     */
    public function __invoke(Request $request, Client $client): FormRequest
    {
        return $this->handler->handle(ClientEditFormHandler::class, $client);
    }
}