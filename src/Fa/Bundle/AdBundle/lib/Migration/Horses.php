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
class Horses
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
        $this->data = array();
        $string     = null;

        if ($this->meta_text != "") {
            try {
                $string = simplexml_load_string($this->meta_text);
                libxml_use_internal_errors(true);
            } catch (\Exception $e) {
                return 0;
            }

            if (isset($string->HorsesAndEquestrianTransMeta->gender)) {
                $this->data['gender_id'] =  $this->getHorseGender((string) $string->HorsesAndEquestrianTransMeta->gender);
            }

            if (isset($string->HorsesAndEquestrianTransMeta->colour)) {
                $this->data['colour_id'] =  $this->getHorseColour((string) $string->HorsesAndEquestrianTransMeta->colour);
            }

            if (isset($string->HorsesAndEquestrianTransMeta->breed)) {
                $this->data['breed_id'] =  $this->getHorseBreed((string) $string->HorsesAndEquestrianTransMeta->breed);
            }

            if (isset($string->HorsesAndEquestrianTransMeta->ageYears)) {
                $this->data['age'] =  $this->getHourseAge((string) $string->HorsesAndEquestrianTransMeta->ageYears);
            }
        }
    }

    private function getHorseGender($string)
    {
        $string = strtolower(trim($string));
        $cType  = array();
        // mapping done
        $cType['colt']     = 2859;
        $cType['filly']    = 2860;
        $cType['gelding']  = 2857;
        $cType['mare']     = 2856;
        $cType['stallion'] = 2858;

        if (isset($cType[$string])) {
            return $cType[$string];
        }
    }

    private function getHorseColour($string)
    {
        $string1 = strtolower(trim($string));

        $cType  = array();
        $cType['blue roan']     = 2874;
        $cType['bright bay']    = 2878;
        $cType['brown']  = 2872;
        $cType['chestnut'] = 2880;
        $cType['coloured'] = 2883;
        $cType['dapple grey'] = 2879;
        $cType['dark bay'] = 2878;
        $cType['dark brown'] = 2872;
        $cType['fleabitten grey'] = 2879;
        $cType['grey'] = 2879;
        $cType['liver chestnut'] = 2880;
        $cType['palomino'] = 2881;
        $cType['rose grey'] = 2879;
        $cType['steel grey'] = 2879;

        if (isset($cType[$string1])) {
            return $cType[$string1];
        } else {
            return trim($string);
        }
    }

    private function getHorseBreed($string)
    {
        $string = strtolower(trim($string));
        $cType  = array();

        $cType['akhal-teke'] = 2768;
        $cType['american quarter horse'] = 2771;
        $cType['andalusian'] = 2775;
        $cType['arab'] = 2779;
        $cType['cleveland bay'] = 2789;
        $cType['fjord'] = 2802;
        $cType['friesian'] = 2806;
        $cType['hackney'] = 2809;
        $cType['hanoverian'] = 2811;
        $cType['haflinger'] = 2810;
        $cType['lipizzaner'] = 2823;
        $cType['irish'] = 2820; // not exact match
        $cType['draught'] = 2817;// not exact match
        $cType['lustiano'] = 2825;// not exact match
        $cType['morgan'] = 2826;
        $cType['mustang'] = 2827;
        $cType['warmblood'] = 2849;
        $cType['palomino'] = 2832;
        $cType['shetland'] = 2839;
        $cType['shire'] = 2840;
        $cType['suffork punch'] = 2841;
        $cType['tennesse walking horse'] = 2845;
        $cType['tersk'] = 2846;
        $cType['thoroughbred'] = 2847;
        $cType['trakehner'] = 2848;

        if (isset($cType[$string])) {
            return $cType[$string];
        }
    }

    private function getHourseAge($string)
    {
        $string = strtolower(trim($string));
        $cType  = array();
        $cType['0.15']     = 2861;
        $cType['0.25']    = 2862;
        $cType['0.5']  = 2863;
        $cType['0.75']     = 2864;
        $cType['1'] = 2865;
        $cType['2'] = 2866;
        $cType['3'] = 2867;
        $cType['4'] = 2867;
        $cType['5'] = 2868;
        $cType['6'] = 2868;
        $cType['7'] = 2869;
        $cType['8'] = 2870;

        if (isset($cType[$string])) {
            return $cType[$string];
        }
    }

    public function update()
    {
        if (count($this->data) > 0) {
            $animalRepository = $this->em->getRepository('FaAdBundle:AdAnimals')->findOneBy(array('ad' => $this->ad_id));
            $metaData = array();

            if (!$animalRepository) {
                $animalRepository = new AdAnimals();
                $animalRepository->setAd($this->em->getReference('FaAdBundle:Ad', $this->ad_id));
            }

            if (isset($this->data['gender_id']) && $this->data['gender_id'] != '') {
                $animalRepository->setGenderId($this->data['gender_id']);
            }

            if (isset($this->data['age']) && $this->data['age'] != '') {
                $metaData['age'] = $this->data['age'];
            }

            if (isset($this->data['breed_id']) && $this->data['breed_id'] != '') {
                $animalRepository->setBreedId($this->data['breed_id']);
            }

            if (isset($this->data['colour_id']) && is_numeric($this->data['colour_id'])) {
                $animalRepository->setColourId($this->data['colour_id']);
            } elseif (isset($this->data['colour_id']) && trim($this->data['colour_id']) != '') {
                $metaData['colour'] = $this->data['colour_id'];
            }

            $animalRepository->setMetaData(serialize($metaData));

            $this->em->persist($animalRepository);
            echo "Dimension updated for ".$animalRepository->getAd()->getId()."\n";
        }
    }
}
