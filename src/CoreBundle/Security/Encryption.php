<?php

declare(strict_types=1);
/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Security;

class Encryption
{
    const CIPHER = MCRYPT_RIJNDAEL_256;
    const MODE = MCRYPT_MODE_ECB;

    /**
     * @var string
     */
    protected $salt;

    /**
     * @param string $salt
     */
    public function __construct($salt)
    {
        $this->salt = $salt;
    }

    /**
     * @param string $data
     *
     * @return string
     */
    public function encrypt($data)
    {
        return trim(
            base64_encode(
                mcrypt_encrypt(
                    self::CIPHER,
                    $this->salt,
                    $data,
                    self::MODE,
                    mcrypt_create_iv(mcrypt_get_iv_size(self::CIPHER, self::MODE), MCRYPT_RAND)
                )
            )
        );
    }

    /**
     * @param string $data
     *
     * @return string
     */
    public function decrypt($data)
    {
        return trim(
            mcrypt_decrypt(
                self::CIPHER,
                $this->salt,
                base64_decode($data, true),
                self::MODE,
                mcrypt_create_iv(mcrypt_get_iv_size(self::CIPHER, self::MODE), MCRYPT_RAND)
            )
        );
    }
}
