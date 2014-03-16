<?php

namespace CSBill\UserBundle\Util;

use FOS\UserBundle\Util\Canonicalizer as BaseCanonicalizer;

class Canonicalizer extends BaseCanonicalizer
{
    public function canonicalize($string)
    {
        if (extension_loaded('mbstring')) {
            return parent::canonicalize($string);
        } else {
            return strtolower($string);
        }
    }
}
