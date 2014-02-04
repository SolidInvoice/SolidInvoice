<?php

/*
 * This file is part of the CSBillUserBundle package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\UserBundle\Security\Encoder;

use Symfony\Component\Security\Core\Encoder\Pbkdf2PasswordEncoder;

class PasswordEncoder extends Pbkdf2PasswordEncoder
{
    /**
     * Is the current version of php supported for the password hash api?
     *
     * @var bool $supportedPhp
     */
    protected $supportedPhp;

    /**
     * Constructor.
     *
     * @param string  $algorithm          The digest algorithm to use
     * @param Boolean $encodeHashAsBase64 Whether to base64 encode the password hash
     * @param integer $iterations         The number of iterations to use to stretch the password hash
     * @param integer $length             Length of derived key to create
     */
    public function __construct($algorithm = 'sha512', $encodeHashAsBase64 = true, $iterations = 10, $length = 40)
    {
        // Use the build in pashword hash api if using php >= 5.5
        $this->supportedPhp = version_compare(PHP_VERSION, '5.5.0', '>=');

        parent::__construct($algorithm, $encodeHashAsBase64, $iterations, $length);
    }

    public function encodePassword($raw, $salt)
    {
        if ($this->supportedPhp) {
            return password_hash($raw, PASSWORD_BCRYPT);
        }

        return parent::encodePassword($raw, $salt);
    }

    public function isPasswordValid($encoded, $raw, $salt)
    {
        if ($this->supportedPhp) {
            return password_verify($raw, $encoded);
        }

        return parent::isPasswordValid($encoded, $raw, $salt);
    }
}
