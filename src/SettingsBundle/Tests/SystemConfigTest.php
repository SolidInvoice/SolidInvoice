<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\SettingsBundle\Tests;

use CSBill\CoreBundle\Test\Traits\DoctrineTestTrait;
use CSBill\SettingsBundle\Entity\Setting;
use CSBill\SettingsBundle\Exception\InvalidSettingException;
use CSBill\SettingsBundle\SystemConfig;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class SystemConfigTest extends TestCase
{
    use DoctrineTestTrait,
        MockeryPHPUnitIntegration;

    protected function setUp()
    {
        parent::setUp();

        $this->setupDoctrine();

        $setting = (new Setting())
            ->setKey('one/two/three')
            ->setValue('four');

        $this->em->persist($setting);
        $this->em->flush();
    }

    public function testGet()
    {
        $config = new SystemConfig($this->em->getRepository('CSBillSettingsBundle:Setting'));

        $this->assertSame('four', $config->get('one/two/three'));
    }

    public function testGetAll()
    {
        $config = new SystemConfig($this->em->getRepository('CSBillSettingsBundle:Setting'));

        $this->assertSame(['one/two/three' => 'four'], $config->getAll());
    }

    public function testInvalidGet()
    {
        $config = new SystemConfig($this->em->getRepository('CSBillSettingsBundle:Setting'));

        $this->expectException(InvalidSettingException::class);
        $this->expectExceptionMessage('Invalid settings key: some/invalid/key');

        $config->get('some/invalid/key');
    }

    public function getEntityNamespaces(): array
    {
        return [
            'CSBillSettingsBundle' => 'CSBill\SettingsBundle\Entity',
        ];
    }

    public function getEntities(): array
    {
        return [
            'CSBillSettingsBundle:Setting',
        ];
    }
}
