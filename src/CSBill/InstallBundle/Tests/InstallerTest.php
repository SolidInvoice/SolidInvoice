<?php
/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\InstallBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class InstallControllerTest extends WebTestCase
{
    public function testInstall()
    {
    	static::createClient();

        $installer = static::$kernel->getContainer()->get('csbill.installer');

		// License Agreement - fail
      	$step = $installer->getStep();

      	$options = array('accept' => 'n');

		$response = $installer->validateStep($options);

		$this->assertFalse($response);
		$this->assertGreaterThan(0, count($step->getErrors()));

       	// License Agreement - succeed
       	$options = array('accept' => 'y');

       	$response = $installer->validateStep($options);

       	$this->assertInstanceOf('\Symfony\Component\HttpFoundation\RedirectResponse', $response);
       	$this->assertCount(0, $step->getErrors());

       	// System Check
       	$step = $installer->getStep();

       	$options = array();

       	$response = $installer->validateStep($options);

       	$this->assertInstanceOf('\Symfony\Component\HttpFoundation\RedirectResponse', $response);
       	$this->assertCount(0, $step->getErrors());

       	// Database Config - fail
       	$step = $installer->getStep();

       	$options = array();

       	$response = $installer->validateStep($options);

       	$this->assertFalse($response);
       	$this->assertGreaterThan(0, $step->getErrors());

       	// Database Config - succeed
       	$options = array(
                           'database_host' 		=> 'localhost',
                           'database_user' 		=> 'root',
                           'database_password' 	=> '',
                           'database_name' 		=> 'csbill_test',
                           'database_port' 		=> '3306',
                           'database_driver' 	=> 'pdo_mysql',
                       );

       	$response = $installer->validateStep($options);

       	$this->assertInstanceOf('\Symfony\Component\HttpFoundation\RedirectResponse', $response);
       	$this->assertCount(0, $step->getErrors());

       	// System Config - fail
       	$step = $installer->getStep();

       	$options = array();

       	$response = $installer->validateStep($options);

       	$this->assertFalse($response);
       	$this->assertGreaterThan(0, count($step->getErrors()));

       	// System Config - succeed
       	$options = array(
               'email_address' 	=> 'test@example.com',
               'password' 		=> 'test',
       	);

       	$response = $installer->validateStep($options);

       	$this->assertInstanceOf('\Symfony\Component\HttpFoundation\RedirectResponse', $response);
       	$this->assertCount(0, $step->getErrors());
    }
}
