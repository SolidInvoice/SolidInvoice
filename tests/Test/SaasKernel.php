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

namespace SolidInvoice\Test;

use SolidInvoice\AppMode;

class SaasKernel extends \SolidInvoice\Kernel
{
    public function __construct(string $environment, bool $debug)
    {
        parent::__construct(AppMode::SAAS, $environment, $debug);
    }

    public function getCacheDir(): string
    {
        return $this->getProjectDir() . '/var/cache/' . $this->environment . '/saas';
    }
}
