<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InstallBundle\Tests\Form\Type;

use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\InstallBundle\DTO\DatabaseConfig;
use SolidInvoice\InstallBundle\Form\Step\DatabaseConfigStep;

/**
 * @covers \SolidInvoice\InstallBundle\Form\Step\DatabaseConfigStep
 */
final class DatabaseConfigTypeTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $formData = [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 1234,
            'user' => 'root',
            'password' => 'password',
            'name' => 'testdb',
        ];

        $this->assertFormData(
            $this->factory->create(DatabaseConfigStep::class),
            $formData,
            new DatabaseConfig(
                driver: 'mysql',
                host: '127.0.0.1',
                port: 1234,
                user: 'root',
                password: 'password',
                name: 'testdb',
            )
        );
    }

    public function testSubmitWithPostgres(): void
    {
        $formData = [
            'driver' => 'pgsql',
            'host' => 'localhost',
            'port' => 5432,
            'user' => 'postgres',
            'password' => 'secret',
            'name' => 'solidinvoice',
        ];

        $this->assertFormData(
            $this->factory->create(DatabaseConfigStep::class),
            $formData,
            new DatabaseConfig(
                driver: 'pgsql',
                host: 'localhost',
                port: 5432,
                user: 'postgres',
                password: 'secret',
                name: 'solidinvoice',
            )
        );
    }

    public function testSubmitWithMariaDB(): void
    {
        $formData = [
            'driver' => 'mariadb',
            'host' => 'localhost',
            'port' => 3306,
            'user' => 'root',
            'password' => '',
            'name' => 'solidinvoice',
        ];

        $this->assertFormData(
            $this->factory->create(DatabaseConfigStep::class),
            $formData,
            new DatabaseConfig(
                driver: 'mariadb',
                host: 'localhost',
                port: 3306,
                user: 'root',
                password: '',
                name: 'solidinvoice',
            )
        );
    }

    public function testSubmitWithSQLite(): void
    {
        $formData = [
            'driver' => 'sqlite',
        ];

        $form = $this->factory->create(DatabaseConfigStep::class);
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());

        $data = $form->getData();
        self::assertInstanceOf(DatabaseConfig::class, $data);
        self::assertSame('sqlite', $data->driver);
    }

    public function testSubmitWithOptionalFields(): void
    {
        $formData = [
            'driver' => 'mysql',
            'host' => 'db.example.com',
            'user' => 'admin',
            'name' => 'mydb',
        ];

        $form = $this->factory->create(DatabaseConfigStep::class);
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());

        $data = $form->getData();
        self::assertInstanceOf(DatabaseConfig::class, $data);
        self::assertSame('mysql', $data->driver);
        self::assertSame('db.example.com', $data->host);
        self::assertSame('admin', $data->user);
        self::assertSame('mydb', $data->name);
        self::assertNull($data->port);
        self::assertNull($data->password);
    }

    public function testFormViewHasDriverField(): void
    {
        $form = $this->factory->create(DatabaseConfigStep::class);
        $view = $form->createView();

        self::assertArrayHasKey('driver', $view->children);
    }

    public function testConfigureOptions(): void
    {
        $form = $this->factory->create(DatabaseConfigStep::class, new DatabaseConfig());

        self::assertInstanceOf(DatabaseConfig::class, $form->getData());
    }
}
