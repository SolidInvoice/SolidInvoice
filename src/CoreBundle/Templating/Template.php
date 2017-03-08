<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Templating;

use Symfony\Component\HttpFoundation\Response;

class Template
{
    /**
     * @var string
     */
    private $template;

    /**
     * @var array
     */
    private $params;

    /**
     * @var Response
     */
    private $response;

    /**
     * @param string        $template
     * @param array         $params
     * @param Response|null $response
     */
    public function __construct(string $template, array $params = [], Response $response = null)
    {
        $this->template = $template;
        $this->params = $params;
        $this->response = $response ?: new Response();
    }

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @return Response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }
}
