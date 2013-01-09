<?php
/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\InstallBundle\Exception;

class ApplicationInstalledException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Application is already installed');
    }
}
