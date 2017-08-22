<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

class_alias("PHPUnit\\Framework\\Assert", "PHPUnit_Framework_Assert");
class_alias("PHPUnit\\Framework\\AssertionFailedError", "PHPUnit_Framework_AssertionFailedError");
class_alias("PHPUnit\\Framework\\BaseTestListener", "PHPUnit_Framework_BaseTestListener");
class_alias("PHPUnit\\Framework\\ExpectationFailedException", "PHPUnit_Framework_ExpectationFailedException");
class_alias("PHPUnit\\Framework\\Test", "PHPUnit_Framework_Test");
class_alias("PHPUnit\\Framework\\TestCase", "PHPUnit_Framework_TestCase");
class_alias("PHPUnit\\Framework\\TestSuite", "PHPUnit_Framework_TestSuite");
class_alias("PHPUnit\\Framework\\TestListener", "PHPUnit_Framework_TestListener");
class_alias("PHPUnit\\Runner\\BaseTestRunner", "PHPUnit_Runner_BaseTestRunner");
class_alias("PHPUnit\\Util\\ErrorHandler", "PHPUnit_Util_ErrorHandler");
class_alias("PHPUnit\\Util\\Test", "PHPUnit_Util_Test");

require_once dirname(__DIR__).'/app/autoload.php';
