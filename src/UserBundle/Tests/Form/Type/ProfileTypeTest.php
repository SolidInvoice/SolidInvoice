<?php
/**
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\UserBundle\Tests\Form\Type;

use CSBill\CoreBundle\Tests\FormTestCase;
use CSBill\UserBundle\Entity\User;
use CSBill\UserBundle\Form\Type\ProfileType;
use FOS\UserBundle\Form\Type\ProfileFormType;
use Symfony\Component\Form\PreloadedExtension;

class ProfileTypeTest extends FormTestCase
{
    public function testSubmit()
    {
	$mobile = $this->faker->phoneNumber;

	$formData = [
	    'mobile' => $mobile,
	];

	$object = new User();
	$object->setMobile($mobile);

	$this->assertFormData(ProfileType::class, $formData, $object);
    }

    protected function getExtensions()
    {
	$type = new ProfileFormType(User::class);

	return [
	    new PreloadedExtension([$type], []),
	];
    }
}
