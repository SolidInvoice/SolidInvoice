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

namespace SolidInvoice\UIBundle\Twig\Extension;

use SolidInvoice\UIBundle\Config\UIConfig;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UiConfigExtension extends AbstractExtension
{
    /**
     * @var UIConfig
     */
    private $config;

    public function __construct(UIConfig $config)
    {
        $this->config = $config;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('set_ui_config', [$this->config, 'add']),

            new TwigFunction('get_ui_config', [$this->config, 'all']),
        ];
    }
}