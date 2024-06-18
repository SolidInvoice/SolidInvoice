<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\DataGridBundle\GridBuilder\Column;

use Closure;

final class StringColumn extends Column
{
    private ?string $template = null;

    private array|Closure $templateParams = [];

    private ?string $twigFunction = null;

    private ?Closure $callback = null;

    public function template(string $template, array | Closure $params = []): static
    {
        $this->template = $template;
        $this->templateParams = $params;

        return $this;
    }

    public function twigFunction(string $function): static
    {
        $this->twigFunction = $function;
        return $this;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function getTemplateParams(): array|Closure
    {
        return $this->templateParams;
    }

    public function getTwigFunction(): ?string
    {
        return $this->twigFunction;
    }

    public function format(Closure $callback): self
    {
        $this->callback = $callback;

        return $this;
    }

    public function getCallback(): ?Closure
    {
        return $this->callback;
    }
}
