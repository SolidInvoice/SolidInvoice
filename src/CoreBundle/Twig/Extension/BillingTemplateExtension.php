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

namespace SolidInvoice\CoreBundle\Twig\Extension;

use SolidInvoice\CoreBundle\Templating\BillingTemplateResolver;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TemplateWrapper;
use Twig\TwigFunction;

final class BillingTemplateExtension extends AbstractExtension
{
    public function __construct(
        private readonly BillingTemplateResolver $resolver,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('billing_template', $this->resolveTemplate(...), ['needs_environment' => true]),
        ];
    }

    public function resolveTemplate(Environment $environment, string $type): string|TemplateWrapper
    {
        return $this->resolver->resolveTemplate($environment, $type, 'html');
    }
}
