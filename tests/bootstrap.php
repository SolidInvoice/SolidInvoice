<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

class_alias("PHPUnit\\Framework\\TestCase", "PHPUnit_Framework_TestCase");
class_alias("PHPUnit\\Framework\\TestListener", "PHPUnit_Framework_TestListener");
class_alias("PHPUnit\\Framework\\Assert", "PHPUnit_Framework_Assert");

require_once dirname(__DIR__).'/app/autoload.php';
