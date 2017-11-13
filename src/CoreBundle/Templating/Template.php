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

namespace SolidInvoice\CoreBundle\Templating;

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
     * @param string        $template #Template
     * @param array         $params
     * @param Response|null $response
     */
    public function __construct(string $template = null, array $params = [], Response $response = null)
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

    /**
     * @param string $template #Template
     *
     * @return Template
     */
    public function setTemplate(string $template): self
    {
        $this->template = $template;

        return $this;
    }
}
