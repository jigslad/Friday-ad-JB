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

// use Fa\Bundle\AdBundle\Entity\Fa\Bundle\AdBundle\Entity;

/**
 * Fa\Bundle\AdBundle\lib\Migration
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 */
class Caravan
{
    private $meta_text;

    private $ad_id;

    private $data = array();


    public function __construct($metaText, $ad_id, $em, $category)
    {
        $this->meta_text = $metaText;
        $this->ad_id     = $ad_id;
        $this->category  = $category;
        $this->em = $em;
        $this->init();
    }

    public function getBirthId($string)
    {
        $string = trim($string);
        $cType  = array();

        $cType['1'] = 2306;
        $cType['2'] = 2307;
        $cType['3'] = 2308;
        $cType['4'] = 2309;
        $cType['5'] = 2310;
        $cType['6'] = 2311;
        $cType['7'] = 2312;
        $cType['8'] = 2313;

        if (isset($cType[$string])) {
            return $cType[$string];
        }
    }

    public function getMakeId($string)
    {
        if ($this->category == 'Motorhomes') {
            $category_dimension_id = 250;
        } elseif ($this->category == 'Caravans') {
            $category_dimension_id = 128;
        } elseif ($this->category == 'Static Caravans') {
            $category_dimension_id = 252;
        }

        $entity = $this->em->getRepository('FaEntityBundle:Entity')->findOneBy(array('name' => $string, 'category_dimension' => $category_dimension_id));

        if ($entity) {
            return $entity->getId();
        }
    }

    /**
     * get model id
     *
     * @param unknown $string
     * @param unknown $make_id
     */
    public function getModelId($string, $make_id)
    {
        $entity = $this->em->getRepository('FaEntityBundle:Entity')->findOneBy(array('name' => $string, 'parent_id' => $make_id));

        if ($entity) {
            return $entity->getId();
        }
    }

    public function init()
    {
        $string1    = null;
        $string     = null;
        $this->data = array();

        if ($this->meta_text != "") {
            try {
                $string1 = preg_replace('/fieldValue="\/><\/TransMeta>/', 'fieldValue="" /></TransMeta>', $this->meta_text);
                $string = simplexml_load_string($string1);
                libxml_use_internal_errors(true);
            } catch (\Exception $e) {
                return 0;
            }

            if (isset($string->CaravansTransMeta->make)) {
                $make = trim((string) $string->CaravansTransMeta->make);
                if ($this->getMakeId($make)) {
                    $this->data['make_id']  = $this->getMakeId($make);
                } else {
                    $this->data['old_make']  = trim((string) $string->CaravansTransMeta->make);
                }
            }

            if (isset($string->CaravansTransMeta->model)) {
                $model = trim((string) $string->CaravansTransMeta->model);

                if ($this->category == 'Motorhomes') {
                    if (isset($this->data['make_id']) && $this->getModelId($model, $this->data['make_id'])) {
                        $this->data['model_id']  = $this->getModelId($model, $this->data['make_id']);
                    } else {
                        $this->data['old_model']  = trim((string) $string->CaravansTransMeta->model);
                    }
                } else {
                    $this->data['old_model']  = trim((string) $string->CaravansTransMeta->model);
                }
            }

            if (isset($string->CaravansTransMeta->isForHire) && (string) $string->CaravansTransMeta->isForHire == "true") {
                $this->data['features_id'][] = 2321;
            }
            if (isset($string->CaravansTransMeta->crisReg) && (string) $string->CaravansTransMeta->crisReg != "false") {
                $this->data['features_id'][] = 2322;
                $this->data['reg_year']  =  date('Y', strtotime(trim((string) $string->CaravansTransMeta->crisReg)));
            }

            if (isset($string->CaravansTransMeta->isTwinAxle) && (string) $string->CaravansTransMeta->isTwinAxle == "true") {
                $this->data['features_id'][] = 2323;
            }

            if (isset($string->CaravansTransMeta->hasEndBedroom) && (string) $string->CaravansTransMeta->hasEndBedroom == "true") {
                $this->data['features_id'][] = 2324;
            }

            if (isset($string->CaravansTransMeta->hasEndBathroom) && (string) $string->CaravansTransMeta->hasEndBathroom == "true") {
                $this->data['features_id'][] = 2325;
            }

            if (isset($string->CaravansTransMeta->numBedrooms) && (string) $string->CaravansTransMeta->numBedrooms != "false") {
                $this->data['berth_id']= $this->getBirthId((string) $string->CaravansTransMeta->numBedrooms);
            }
        }
    }

    public function update()
    {
        $metaData = array();

        if (count($this->data) > 0) {
            $caravanRepository = $this->em->getRepository('FaAdBundle:AdMotors')->findOneBy(array('ad' => $this->ad_id));

            if (!$caravanRepository) {
                $caravanRepository = new AdMotors();
                $caravanRepository->setAd($this->em->getReference('FaAdBundle:Ad', $this->ad_id));
            }

            if (isset($this->data['make_id']) && $this->data['make_id']) {
                $caravanRepository->setMakeId($this->data['make_id']);
            } else {
                $caravanRepository->setMakeId(null);
                $metaData['make'] = $this->data['old_make'];
            }

            if (isset($this->data['model_id']) && $this->data['model_id']) {
                $caravanRepository->setModelId($this->data['model_id']);
            } else {
                $caravanRepository->setModelId(null);
                $metaData['model'] = $this->data['old_model'];
            }

            if (isset($this->data['old_make']) && $this->data['old_make']) {
                $caravanRepository->setOldMake($this->data['old_make']);
            } else {
                $caravanRepository->setOldMake(null);
            }

            if (isset($this->data['old_model']) && $this->data['old_model']) {
                $caravanRepository->setOldModel($this->data['old_model']);
            } else {
                $caravanRepository->setOldModel(null);
            }

            if (isset($this->data['berth_id']) && $this->data['berth_id']) {
                $caravanRepository->setBerthId($this->data['berth_id']);
            } else {
                $caravanRepository->setBerthId(null);
            }

            if (isset($this->data['features_id']) && count($this->data['features_id']) > 0) {
                $metaData['features_id'] = implode(',', $this->data['features_id']);
            }

            if (isset($this->data['reg_year']) && $this->data['reg_year']) {
                $metaData['reg_year'] = $this->data['reg_year'];
            }

            if (count($metaData) > 0) {
                $caravanRepository->setMetaData(serialize($metaData));
            } else {
                $caravanRepository->setMetaData(null);
            }

            $this->em->persist($caravanRepository);
            echo "Dimension updated for ".$caravanRepository->getAd()->getId()."\n";
        }
    }
}
