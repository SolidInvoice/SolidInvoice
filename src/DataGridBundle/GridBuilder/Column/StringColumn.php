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

namespace SolidInvoice\DataGridBundle\GridBuilder\Column;

use Closure;
use function is_array;

/**
 * @see \SolidInvoice\DataGridBundle\Tests\GridBuilder\Column\StringColumnTest
 */
final class StringColumn extends Column
{
    private ?string $template = null;

    private Closure $templateParams;

    private ?string $twigFunction = null;

    /**
     * @param array<string, mixed>|Closure $params
     */
    public function template(string $template, array | callable $params = []): self
    {
        $this->template = $template;

        $this->templateParams = is_array($params) ? static fn () => $params : $params(...);

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
}
