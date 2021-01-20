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
     * @param string $template #Template
     */
    public function __construct(string $template = null, array $params = [], Response $response = null)
    {
        $this->template = $template;
        $this->params = $params;
        $this->response = $response ?: new Response();
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function getParams(): array
    {
        return $this->params;
    }

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
