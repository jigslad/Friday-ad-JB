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
class Motorbikes
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
                libxml_use_internal_errors(true);
                $string = simplexml_load_string($this->meta_text);
            } catch (\Exception $e) {
                return 0;
            }

            $adObj = $this->em->getRepository('FaAdBundle:Ad')->find($this->ad_id);

            if ($adObj->getOldClassId() > 0) {
                $mappingCategory = $this->em->getRepository('FaEntityBundle:MappingCategory')->find($adObj->getOldClassId());
                if ($mappingCategory) {
                    $make = $mappingCategory->getName();
                }

                if ($this->getMakeId($make)) {
                    $this->data['make_id']  = $this->getMakeId($make);
                } else {
                    $this->data['old_make']  = $make;
                }
            }

            if ($adObj->getOldSubClassId() > 0) {
                $mappingCategory = $this->em->getRepository('FaEntityBundle:MappingCategory')->find($adObj->getOldClassId());
                if ($mappingCategory) {
                    $make = $mappingCategory->getName();
                }

                $mappingCategory = $this->em->getRepository('FaEntityBundle:MappingCategory')->find($adObj->getOldSubClassId());
                if ($mappingCategory) {
                    $model = $mappingCategory->getName();
                }

                if (isset($this->data['make_id']) && $this->getModelId($model, $this->data['make_id'])) {
                    $this->data['model_id']  = $this->getModelId($model, $this->data['make_id']);
                } else {
                    $this->data['old_model']  = $model;
                }
            }
        }
    }

    public function getMakeId($string)
    {
        $string = trim($string);
        $entity = $this->em->getRepository('FaEntityBundle:Entity')->findOneBy(array('name' => $string, 'category_dimension' => 114));

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


    public function update()
    {

        $metaData = array();

        if (count($this->data) > 0) {
            $motorbikeRepository = $this->em->getRepository('FaAdBundle:AdMotors')->findOneBy(array('ad' => $this->ad_id));

            if (!$motorbikeRepository) {
                $motorbikeRepository = new AdMotors();
                $motorbikeRepository->setAd($this->em->getReference('FaAdBundle:Ad', $this->ad_id));
            }

            if (isset($this->data['make_id']) && $this->data['make_id']) {
                $motorbikeRepository->setMakeId($this->data['make_id']);
            } else {
                $motorbikeRepository->setMakeId(null);

                if ($this->data['old_make']) {
                    $metaData['make'] = $this->data['old_make'];
                }
            }

            if (isset($this->data['model_id']) && $this->data['model_id']) {
                $motorbikeRepository->setModelId($this->data['model_id']);
            } else {
                $motorbikeRepository->setModelId(null);

                if (isset($this->data['old_model'])) {
                    $metaData['model'] = $this->data['old_model'];
                }
            }

            if (count($metaData) > 0) {
                $motorbikeRepository->setMetaData(serialize($metaData));
            } else {
                $motorbikeRepository->setMetaData(null);
            }


            $this->em->persist($motorbikeRepository);
            echo "Dimension updated for ".$motorbikeRepository->getAd()->getId()."\n";

        }
    }
}
