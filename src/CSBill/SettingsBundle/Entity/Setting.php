<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\SettingsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use CSBill\SettingsBundle\Model\Setting as BaseClass;

/**
 * Class Setting
 * @package CSBill\SettingsBundle\Entity
 *
 * @ORM\Table(name="app_config")
 * @ORM\Entity(repositoryClass="CSBill\SettingsBundle\Repository\SettingsRepository")
 */
class Setting extends BaseClass
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
