<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\lib\Carweb\Cache;

use Fa\Bundle\CoreBundle\Entity\Carweb;
use Fa\Bundle\CoreBundle\Repository\CarwebRepository;

/**
 * Fa\Bundle\CoreBundle\lib\Carweb\Cach\DBCache
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 */
class DBCache implements CacheInterface
{

    /**
     * Construct
     *
     * @param string $path
     * @param int $ttl
     */
    public function __construct($em, $ttl = 648000)
    {
        $this->em  = $em;
        $this->ttl = $ttl;
    }


    /**
     * check if the current item is cached
     *
     * @param $key
     *
     * @return bool
     */
    public function has($key)
    {
        $carData = $this->em->getRepository('FaCoreBundle:Carweb')->findOneBy(array('key_string' => $key));

        if ($carData && $carData->getCreatedAt() > (time() - $this->ttl)) {
            return true;
        } elseif ($carData) {
            $this->clear($key);
        }
    }

      /**
     * gets cached value for current item
     *
     * @param $key
     *
     * @return mixed
     */
    public function get($key)
    {
        $carData = $this->em->getRepository('FaCoreBundle:Carweb')->findOneBy(array('key_string' => $key));

        if ($carData) {
            return $carData->getCarData();
        } else {
            return null;
        }
    }

    /**
     * Saves the value to cache
     *
     * @param $key
     * @param $value
     *
     * @return null
     */
    public function save($key, $value)
    {
        $carweb = new Carweb();
        $carweb->setKeyString($key);
        $carweb->setCarData($value);
        $carweb->setCreatedAt(time());

        $this->em->persist($carweb);
        $this->em->flush();
    }

    /**
     * Clears the current value
     *
     * @param $key
     *
     * @return null
     */
    public function clear($key)
    {
        $carData = $this->em->getRepository('FaCoreBundle:Carweb')->findBy(array('key_string' => $key));

        foreach ($carData as $data) {
            $this->em->remove($data);
            $this->em->flush();
        }
    }
}
