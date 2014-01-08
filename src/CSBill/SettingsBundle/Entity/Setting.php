<?php

/*
 * This file is part of the CSBillSettingsBundle package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
