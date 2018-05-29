<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * This table is used to store ad image information.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 *
 * @ORM\Table(name="carweb")
 * @ORM\Entity(repositoryClass="Fa\Bundle\CoreBundle\Repository\CarwebRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Carweb
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="key_string", type="string", length=50)
     */
    private $key_string;

    /**
     * @var string
     *
     * @ORM\Column(name="car_data", type="text")
     */
    private $car_data;

    /**
     * @var integer
     *
     * @ORM\Column(name="created_at", type="integer", length=10)
     */
    private $created_at;

    /**
     * Get id.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id.
     *
     * @param integer $id
     *
     * @return \Fa\Bundle\AdBundle\Entity\Carweb
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get key string.
     *
     * @return integer
     */
    public function getKeyString()
    {
        return $this->key_string;
    }

    /**
     * Set key_string.
     *
     * @return \Fa\Bundle\AdBundle\Entity\Carweb
     */
    public function setKeyString($key_string)
    {
        $this->key_string = $key_string;
        return $this;
    }

    /**
     * Get car data.
     *
     * @return string
     */
    public function getCarData()
    {
        return $this->car_data;
    }

    /**
     * Set car data.
     *
     * @param string $car_data
     *
     * @return \Fa\Bundle\AdBundle\Entity\Carweb
     */
    public function setCarData($car_data)
    {
        $this->car_data = $car_data;
        return $this;
    }

    /**
     * Get created at.
     *
     * @return number
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set created at.
     *
     * @param integer $created_at
     *
     * @return \Fa\Bundle\AdBundle\Entity\Carweb
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;
        return $this;
    }
}
