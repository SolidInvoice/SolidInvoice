<?php
/**
 * This file is part of the CSBill project.
 * 
 * @author      MiWay Development Team
 * @copyright   Copyright (c) 2014 MiWay Insurance Ltd
 */

namespace CSBill\CoreBundle\Tests;

use CSBill\CoreBundle\Form\Extension;
use CSBill\CoreBundle\Form\Type;
use CSBill\CoreBundle\Security\Encryption;
use CSBill\CoreBundle\Util\Currency;
use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Test\TypeTestCase;

class FormTestCase extends TypeTestCase
{
    protected function setUp()
    {
        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtensions($this->getExtensions())
            ->addTypeExtensions($this->getTypedExtensions())
            ->addTypes($this->getTypes())
            ->getFormFactory();

        $this->dispatcher = \Mockery::mock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->builder = new FormBuilder(null, null, $this->dispatcher, $this->factory);
    }

    /**
     * Get registered form extensions
     *
     * @return array
     */
    protected function getTypedExtensions()
    {
        $validator = \Mockery::mock('Symfony\Component\Validator\ValidatorInterface');

        $validator->shouldReceive('validate')->zeroOrMoreTimes()->andReturn(array());

        return array(
            new Extension\FormHelpExtension(),
            new Extension\MoneyExtension(new Currency('en', 'USD')),
            new FormTypeValidatorExtension(
                $validator
            )
        );
    }

    /**
     * Get registered form types
     *
     * @return array
     */
    protected function getTypes()
    {
        return array(
            'select2' => new Type\Select2(),
            'image_upload' => new Type\ImageUpload(
                \Mockery::mock('Symfony\Component\HttpFoundation\Session\SessionInterface'),
                new Encryption(rand())
            ),
        );
    }
}