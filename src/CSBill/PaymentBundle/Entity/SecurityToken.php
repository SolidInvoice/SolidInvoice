<?php

namespace CSBill\PaymentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Payum\Core\Model\Token;

/**
 * @ORM\Table(name="security_token")
 * @ORM\Entity
 */
class SecurityToken extends Token
{
}
