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

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpFoundation\Request;

interface StepInterface extends ContainerAwareInterface
{
    /**
     * @param Request $request
     * @return mixed
     */
    public function handleRequest(Request $request);

    /**
     * @return bool
     */
    public function isValid();

    /**
     * @return void
     */
    public function process();

    /**
     * Initializes the current step
     */
    public function init();
}
