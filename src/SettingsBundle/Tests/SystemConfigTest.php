<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\SettingsBundle\Tests;

use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\SettingsBundle\Entity\Setting;
use SolidInvoice\SettingsBundle\Exception\InvalidSettingException;
use SolidInvoice\SettingsBundle\SystemConfig;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;

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
            ->setValue('four')
            ->setType(TextType::class);

        $this->em->persist($setting);
        $this->em->flush();
    }

    public function testGet()
    {
        $config = new SystemConfig($this->em->getRepository(Setting::class));

        $this->assertSame('four', $config->get('one/two/three'));
    }

    public function testGetAll()
    {
        $config = new SystemConfig($this->em->getRepository(Setting::class));

        $this->assertSame(['one/two/three' => 'four'], $config->getAll());
    }

    public function testInvalidGet()
    {
        $config = new SystemConfig($this->em->getRepository(Setting::class));

        $this->expectException(InvalidSettingException::class);
        $this->expectExceptionMessage('Invalid settings key: some/invalid/key');

        $config->get('some/invalid/key');
    }
}
