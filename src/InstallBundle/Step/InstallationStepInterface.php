<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InstallBundle\Step;

use SolidInvoice\InstallBundle\DTO\Installation;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(InstallationStepInterface::DI_TAG)]
interface InstallationStepInterface
{
    public const string DI_TAG = 'solidinvoice.installation_step';

    public static function priority(): int;

    public function execute(Installation $installationData, ?callable $callback = null): \Generator;

    public static function getLabel(): string;
}
