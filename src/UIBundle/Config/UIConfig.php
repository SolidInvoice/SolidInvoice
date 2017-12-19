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

namespace SolidInvoice\UIBundle\Config;

final class UIConfig
{
    private $config = [];

    public function add(string $key, array $config = [])
    {
        $this->config[$key] = $config;
    }

    public function get(string $key): array
    {
        if (array_key_exists($key, $this->config)) {
            return $this->config[$key];
        }

        return [];
    }

    public function all(): array
    {
        return $this->config;
    }
}