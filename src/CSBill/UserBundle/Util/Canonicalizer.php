<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

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
