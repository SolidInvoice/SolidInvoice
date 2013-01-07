<?php
/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\InstallBundle\Installer;

interface StepInterface
{
    /**
     * @return boolean
     */
    public function validate(array $request);

    /**
     * @return void
     */
    public function process(array $request);

    /**
     * @return void
     */
    public function start();
}
