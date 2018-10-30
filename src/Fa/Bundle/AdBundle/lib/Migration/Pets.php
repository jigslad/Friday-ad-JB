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

use Fa\Bundle\AdBundle\Entity\AdAnimals;

/**
 * Fa\Bundle\AdBundle\lib\Migration
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 */
class Pets
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

    private function getAnimalAgeId($string)
    {
        return $string;
    }

    private function getAnimalColorId($string)
    {
        return trim($string);
    }

    private function getAnimalGenderId($string)
    {
        $string = trim($string);
        $cType  = array();

        $cType['Male']   = 2624;
        $cType['Female'] = 2625;

        if (isset($cType[$string])) {
            return $cType[$string];
        }
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

            if (isset($string->FarmingAndLivestockTransMeta->colour)) {
                $this->data['colour'] =  $this->getAnimalColorId((string) $string->FarmingAndLivestockTransMeta->colour);
            }

            if (isset($string->FarmingAndLivestockTransMeta->gender)) {
                $this->data['gender_id'] =  $this->getAnimalGenderId((string) $string->FarmingAndLivestockTransMeta->gender);
            }
        }
    }

    public function update()
    {
        if (count($this->data) > 0) {
            $animalRepository = $this->em->getRepository('FaAdBundle:AdAnimals')->findOneBy(array('ad' => $this->ad_id));

            if (!$animalRepository) {
                $animalRepository = new AdAnimals();
                $animalRepository->setAd($this->em->getReference('FaAdBundle:Ad', $this->ad_id));
            }

            if (isset($this->data['gender_id']) && $this->data['gender_id'] != '') {
                $animalRepository->setGenderId($this->data['gender_id']);
            }

            if (isset($this->data['colour']) && trim($this->data['colour']) != '') {
                $animalRepository->setMetaData(serialize(array('colour' => $this->data['colour'])));
            }

            $this->em->persist($animalRepository);
            echo "Dimension updated for ".$animalRepository->getAd()->getId()."\n";
        }
    }
}
