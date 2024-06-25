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
use function is_array;

final class StringColumn extends Column
{
    private ?string $template = null;

    private Closure $templateParams;

    private ?string $twigFunction = null;

    private ?Closure $callback = null;

    /**
     * @param array<string, mixed>|Closure $params
     */
    public function template(string $template, array | callable $params = []): self
    {
        $this->template = $template;

        if (is_array($params)) {
            $this->templateParams = static fn () => $params;
        } else {
            $this->templateParams = $params(...);
        }

        return $this;
    }

    public function twigFunction(string $function): self
    {
        $this->twigFunction = $function;
        return $this;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    /**
     * @return array<string, mixed>
     */
    public function getTemplateParams(): array
    {
        return ($this->templateParams)();
    }

    public function getTwigFunction(): ?string
    {
        return $this->twigFunction;
    }

    public function getCallback(): Closure
    {
        return $this->callback ?? static fn (mixed $value = null): mixed => $value;
    }
}
