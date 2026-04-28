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

namespace SolidInvoice\CoreBundle\Export\Attribute;

use Attribute;

/**
 * Opts an entity class or entity property out of the full company export.
 *
 * Applied at class level, the entity is skipped entirely by the full company
 * export. Applied at property level, the property is stripped from the
 * serialized output for the full company export only.
 *
 * Grid exports are defined by the configured grid columns and do not consult
 * this attribute.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
final class ExportIgnore
{
}
