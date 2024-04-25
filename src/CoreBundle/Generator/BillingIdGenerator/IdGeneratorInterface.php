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

namespace SolidInvoice\CoreBundle\Generator\BillingIdGenerator;

use Symfony\Component\Form\FormTypeInterface;

interface IdGeneratorInterface
{
    public static function getName(): string;

    /**
     * @return class-string<FormTypeInterface>|null
     */
    public function getConfigurationFormType(): ?string;

    /**
     * @param array<string, mixed> $options
     */
    public function generate(object $entity, array $options): string;
}
