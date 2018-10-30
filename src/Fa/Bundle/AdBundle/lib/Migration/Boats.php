<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\lib\Migration;

use Fa\Bundle\AdBundle\Entity\AdMotors;

/**
 * Fa\Bundle\AdBundle\lib\Migration
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 */
class Boats
{
    private $meta_text;

    private $ad_id;

    private $data = array();


    public function __construct($metaText, $ad_id, $em)
    {
        $this->meta_text = $metaText;
        $this->ad_id     = $ad_id;
        $this->em = $em;
        $this->init();
    }

    public function init()
    {
        $string     = null;
        $this->data = array();

        if ($this->meta_text != "") {
            try {
                $string = simplexml_load_string($this->meta_text);
                libxml_use_internal_errors(true);
            } catch (\Exception $e) {
                return 0;
            }

            if (isset($string->BoatsTransMeta->fuelType)) {
                $this->data['fuel_type'] =  (string) $string->BoatsTransMeta->fuelType;
            }
        }
    }

    private function getFuelTypeId($string)
    {
        $string = trim($string);
        $cType  = array();

        $cType['Petrol']    = 1627;
        $cType['Diesel']    = 1628;
        $cType['Daul-Fuel'] = 1629;
        $cType['Gas']       = 1631;
        $cType['Electric']  = 6418;
        $cType['Hybrid']    = 1630;

        if (isset($cType[$string])) {
            return $cType[$string];
        }
    }

    public function update()
    {
        if (count($this->data) > 0) {
            $boatRepository = $this->em->getRepository('FaAdBundle:AdMotors')->findOneBy(array('ad' => $this->ad_id));

            if (!$boatRepository) {
                $boatRepository = new AdMotors();
                $boatRepository->setAd($this->em->getReference('FaAdBundle:Ad', $this->ad_id));
            }

            $boatRepository->setFuelTypeId($this->getFuelTypeId($this->data['fuel_type']));
            $this->em->persist($boatRepository);
            echo "Dimension updated for ".$boatRepository->getAd()->getId()."\n";
        }
    }
}
