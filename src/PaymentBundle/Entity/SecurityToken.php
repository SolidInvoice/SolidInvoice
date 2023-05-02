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

namespace SolidInvoice\PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Payum\Core\Model\Token;

#[ORM\Table(name: SecurityToken::TABLE_NAME)]
#[ORM\Entity]
class SecurityToken extends Token
{
    final public const TABLE_NAME = 'security_token';
}
