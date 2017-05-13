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

namespace CSBill\UserBundle\Action\Ajax;

use CSBill\CoreBundle\Response\AjaxResponse;
use CSBill\UserBundle\Entity\ApiToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApiTokenHistory implements AjaxResponse
{
    /**
     * @var \Twig_Environment
     */
    private $twig;

    public function __construct(\Twig_Environment $twig)
    {
        $this->twig = $twig;
    }

    public function __invoke(Request $request, ApiToken $token)
    {
        return new Response(
            $this->twig->render(
                '@CSBillUser/Api/history.html.twig',
                [
                    'history' => $token->getHistory(),
                ]
            )
        );
    }
}
