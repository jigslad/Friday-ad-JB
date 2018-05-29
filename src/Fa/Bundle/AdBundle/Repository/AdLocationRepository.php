<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\AdBundle\Solr\AdSolrFieldMapping;

/**
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdLocationRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'al';

    /**
     * PrepareQueryBuilder.
     *
     * @param array $data Array of data.
     *
     * @return Doctrine\ORM\QueryBuilder The query builder.
     */
    public function getBaseQueryBuilder()
    {
        return $this->createQueryBuilder(self::ALIAS);
    }

    /**
     * Get comma saperated location id array based on ad id.
     *
     * @param array $adId Ad id array.
     *
     * @return array
     */
    public function getIdArrayByAdId($adId = array())
    {
        $qb = $this->createQueryBuilder(self::ALIAS)
            ->select(self::ALIAS.'.id', AdRepository::ALIAS.'.id as aid', 'ld.id as ldid', 'lt.id as ltid')
            ->leftJoin(self::ALIAS.'.ad', AdRepository::ALIAS)
            ->leftJoin(self::ALIAS.'.location_domicile', 'ld')
            ->leftJoin(self::ALIAS.'.location_town', 'lt');

        if (!is_array($adId)) {
            $adId = array($adId);
        }

        if (count($adId)) {
            $qb->andWhere(self::ALIAS.'.ad IN (:adId)');
            $qb->setParameter('adId', $adId);
        }

        $objResources = $qb->getQuery()->getArrayResult();
        $arr = array();
        if (count($objResources)) {
            for ($i=0; $i<count($objResources); $i++) {
                $arr[$objResources[$i]['aid']] = $objResources[$i]['ldid'].','.$objResources[$i]['ltid'];
            }
        }

        return $arr;
    }

    /**
     * Returns ad solr document object.
     *
     * @param object $ad       Ad object.
     * @param object $document Solr document object.
     *
     * @return Apache_Solr_Document
     */
    public function getSolrDocument($ad, $document = null)
    {
        if (!$document) {
            $document = new \SolrInputDocument($ad);
        }

        $locations = $this->findBy(array('ad' => $ad->getId()));

        foreach ($locations as $location) {
            $document = $this->addField($document, AdSolrFieldMapping::POSTCODE, $location->getPostcode());
            $document = $this->addField($document, AdSolrFieldMapping::DOMICILE_ID, ($location->getLocationDomicile() ? $location->getLocationDomicile()->getId() : null));
            $document = $this->addField($document, AdSolrFieldMapping::TOWN_ID, ($location->getLocationTown() ? $location->getLocationTown()->getId() : null));
            $document = $this->addField($document, AdSolrFieldMapping::MAIN_TOWN_ID, ($location->getLocationTown() ? $location->getLocationTown()->getId() : null));
            $document = $this->addField($document, AdSolrFieldMapping::LATITUDE, $location->getLatitude());
            $document = $this->addField($document, AdSolrFieldMapping::LONGITUDE, $location->getLongitude());
            $document = $this->addField($document, AdSolrFieldMapping::LOCALITY_ID, ($location->getLocality() ? $location->getLocality()->getId() : null));

            if ($location->getLatitude() && $location->getLongitude()) {
                $document = $this->addField($document, AdSolrFieldMapping::STORE, $location->getLatitude().','.$location->getLongitude());
            }
        }

        return $document;
    }

    /**
     * Add field to solr document.
     *
     * @param object $document Solr document object.
     * @param string $field    Field to index or store.
     * @param string $value    Value of field.
     *
     * @return object
     */
    private function addField($document, $field, $value)
    {
        if ($value != null) {
            $document->addField($field, $value);
        }

        return $document;
    }

    /**
     * Find the location by ad id.
     *
     * @param integer $adId                 Ad id.
     * @param boolean $withRefIds           Need result array with referece ids.
     * @param boolean $adIdAsKeyWithZipFlag Ad id as key.
     *
     * @return array
     */
    public function findByAdId($adId, $withRefIds = false, $adIdAsKeyWithZipFlag = false)
    {
        $qb = $this->getBaseQueryBuilder();

        if (!is_array($adId)) {
            $adId = array($adId);
        }

        $qb->andWhere(self::ALIAS.'.ad IN (:adId)');
        $qb->setParameter('adId', $adId);

        $resultArray = $qb->getQuery()->getArrayResult();

        if ($withRefIds) {
            $result = $qb->getQuery()->getResult();
            foreach ($result as $key => $location) {
                if (isset($resultArray[$key])) {
                    $arrayKey = ($adIdAsKeyWithZipFlag ? $location->getAd()->getId() : $key);
                    $resultArray[$arrayKey]['town_id']     = $location->getLocationTown() ? $location->getLocationTown()->getId() : null;
                    $resultArray[$arrayKey]['domicile_id'] = $location->getLocationDomicile() ? $location->getLocationDomicile()->getId() : null;
                    $resultArray[$arrayKey]['locality_id'] = $location->getLocality() ? $location->getLocality()->getId() : null;
                    if ($adIdAsKeyWithZipFlag) {
                        $resultArray[$arrayKey]['postcode'] = $location->getPostcode();
                    }
                }
            }
        }

        return $resultArray;
    }

    /**
     * Update data from moderation.
     *
     * @param array $data Data from moderation.
     */
    public function updateDataFromModeration($data)
    {
        foreach ($data as $element) {
            if (isset($element['id'])) {
                $object = $this->findOneBy(array('id' => $element['id']));
            } else {
                $object = $this->findOneBy(array('ad' => $element['ad_id']));
            }

            if (!$object) {
                continue;
            }

            foreach ($element as $field => $value) {
                $methodName = 'set'.str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));
                if (method_exists($object, $methodName) === true) {
                    $object->$methodName($value);
                }

                if ($field == 'domicile_id') {
                    if ($value) {
                        $locationDomicile = $this->_em->getRepository('FaEntityBundle:Location')->findOneBy(array('id' => $value));
                        $object->setLocationDomicile($locationDomicile);
                    } else {
                        $object->setLocationDomicile(null);
                    }
                } elseif ($field == 'town_id') {
                    if ($value) {
                        $locationTown = $this->_em->getRepository('FaEntityBundle:Location')->findOneBy(array('id' => $value));
                        $object->setLocationTown($locationTown);
                    } else {
                        $object->setLocationTown(null);
                    }
                } elseif ($field == 'locality_id') {
                    if ($value) {
                        $locality = $this->_em->getRepository('FaEntityBundle:Locality')->findOneBy(array('id' => $value));
                        $object->setLocality($locality);
                    } else {
                        $object->setLocality(null);
                    }
                }
            }

            $this->_em->persist($object);
            $this->_em->flush($object);
        }
    }

    /**
     * Get latest location based on id.
     *
     * @param integer $adId Ad id.
     *
     * @return Doctrine_Object
     */
    public function getLatestLocation($adId)
    {
        $qb = $this->createQueryBuilder(self::ALIAS)
        ->leftJoin(self::ALIAS.'.location_domicile', 'ld')
        ->andWhere(self::ALIAS.'.ad = :adId')
        ->setParameter('adId', $adId)
        ->addOrderBy(self::ALIAS.'.id', 'DESC')
        ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Get location group from ad.
     *
     * @param integer $adId              Ad id.
     * @param boolean $isSendStatusAllow Send moderation status allow or not.
     *
     * @return mixed
     */
    public function getLocationGroupIdForAd($adId, $isSendStatusAllow = false)
    {
        $townIds         = array();
        $moderationValue = array();

        $moderationQueueStatus = array(AdModerateRepository::MODERATION_QUEUE_STATUS_SENT, AdModerateRepository::MODERATION_QUEUE_STATUS_MANUAL_MODERATION);

        // For expired/sold ad edit we add entry on ad moderation but not send before payment,
        // So status will be 0 and we are showing package based on ad moderation data if available, in this case we have status 0 in databse
        // So need to consider 0 status too.
        if ($isSendStatusAllow) {
            array_push($moderationQueueStatus, AdModerateRepository::MODERATION_QUEUE_STATUS_SEND);
        }

        $adModerate = $this->_em->getRepository('FaAdBundle:AdModerate')->findByAdIdAndModerationQueueFilter($adId, $moderationQueueStatus);
        if ($adModerate && $adModerate->getValue()) {
            $moderationValue = unserialize($adModerate->getValue());
        }

        // get location from moderation
        if (count($moderationValue) && isset($moderationValue['locations']) && count($moderationValue['locations']) && isset($moderationValue['locations'][0]['town_id'])) {
            $townIds[] = $moderationValue['locations'][0]['town_id'];
            return $this->_em->getRepository('FaEntityBundle:LocationGroupLocation')->getLocationGroupIdByTownDomicile($townIds);
        } else {
            $qb = $this->createQueryBuilder(self::ALIAS)
            ->andWhere(self::ALIAS.'.ad = :adId')
            ->setParameter('adId', $adId)
            ->addOrderBy(self::ALIAS.'.id', 'DESC')
            ->setMaxResults(1);

            $adLocations = $qb->getQuery()->getResult();
            if ($adLocations) {
                foreach ($adLocations as $adLocation) {
                    if ($adLocation->getLocationTown()) {
                        $townIds[] = $adLocation->getLocationTown()->getId();
                    }
                }

                return $this->_em->getRepository('FaEntityBundle:LocationGroupLocation')->getLocationGroupIdByTownDomicile($townIds);
            } else {
                return null;
            }
        }
    }

    /**
     * Set data on object from moderation.
     *
     * @param array $element Element from moderation.
     *
     * @return object
     */
    public function setObjectFromModerationData($element, $adId = null)
    {
        if (isset($element['id'])) {
            $object = $this->findOneBy(array('id' => $element['id']));
        } else {
            $object = $this->findOneBy(array('ad' => $element['ad_id']));
        }

        if (!$object && $adId) {
            $object = $this->findOneBy(array('ad' => $adId));
        }

        foreach ($element as $field => $value) {
            $methodName = 'set'.str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));
            if (method_exists($object, $methodName) === true) {
                $object->$methodName($value);
            }

            if ($field == 'domicile_id') {
                if ($value) {
                    $locationDomicile = $this->_em->getRepository('FaEntityBundle:Location')->findOneBy(array('id' => $value));
                    $object->setLocationDomicile($locationDomicile);
                } else {
                    $object->setLocationDomicile(null);
                }
            } elseif ($field == 'town_id') {
                if ($value) {
                    $locationTown = $this->_em->getRepository('FaEntityBundle:Location')->findOneBy(array('id' => $value));
                    $object->setLocationTown($locationTown);
                } else {
                    $object->setLocationTown(null);
                }
            } elseif ($field == 'locality_id') {
                if ($value) {
                    $locality = $this->_em->getRepository('FaEntityBundle:Locality')->findOneBy(array('id' => $value));
                    $object->setLocality($locality);
                } else {
                    $object->setLocality(null);
                }
            }
        }

        return $object;
    }

    /**
     * Find the location by ad id.
     *
     * @param integer $adId Ad id.
     *
     * @return array
     */
    public function findLocationByAdId($adId)
    {
        $qb = $this->getBaseQueryBuilder();

        if (!is_array($adId)) {
            $adId = array($adId);
        }

        $qb->andWhere(self::ALIAS.'.ad IN (:adId)');
        $qb->setParameter('adId', $adId);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get ad location data array.
     *
     * @param object $adId Ad id.
     *
     * @return array
     */
    public function getAdLocationDataArray($adId)
    {
        $adLocationData = array();
        $adLocations    = $this->findLocationByAdId($adId);

        if ($adLocations && count($adLocations)) {
            $count = 0;
            foreach ($adLocations as $adLocation) {
                $adLocationData[$count]['country_id']  = $adLocation->getLocationCountry() ? $adLocation->getLocationCountry()->getId() : null;
                $adLocationData[$count]['domicile_id'] = $adLocation->getLocationDomicile() ? $adLocation->getLocationDomicile()->getId() : null;
                $adLocationData[$count]['town_id']     = $adLocation->getLocationTown() ? $adLocation->getLocationTown()->getId() : null;
                $adLocationData[$count]['locality_id'] = $adLocation->getLocality() ? $adLocation->getLocality()->getId() : null;
                $adLocationData[$count]['postcode']    = $adLocation->getPostcode();
                $adLocationData[$count]['latitude']    = $adLocation->getLatitude();
                $adLocationData[$count]['longitude']   = $adLocation->getLongitude();
                $adLocationData[$count]['trans_id']    = $adLocation->getTransId();
                $adLocationData[$count]['update_type'] = $adLocation->getUpdateType();
                $count++;
            }
        }

        $adLocationArray = array();
        foreach ($adLocationData as $count => $locationArray) {
            $adLocationArray[$count] = array_filter($locationArray, 'strlen');
        }

        return $adLocationArray;
    }

    /**
     * Remove ad locations by ad id.
     *
     * @param integer $adId Ad id.
     */
    public function removeByAdId($adId)
    {
        $adLocations = $this->getBaseQueryBuilder()
                            ->andWhere(self::ALIAS.'.ad = :adId')
                            ->setParameter('adId', $adId)
                            ->getQuery()
                            ->getResult();

        //remove print ad from table.
        if ($adLocations) {
            foreach ($adLocations as $adLocation) {
                $this->_em->remove($adLocation);
            }
            $this->_em->flush();
        }
    }

    /**
     * Get ad location data array.
     *
     * @param object $adId Ad id.
     *
     * @return array
     */
    public function getAdLocationDataForLog($adId)
    {
        $adLocationData = array();
        $adLocations    = $this->findLocationByAdId($adId);

        if ($adLocations && count($adLocations)) {
            foreach ($adLocations as $adLocation) {
                $adLocationData[]['country_id']  = $adLocation->getLocationCountry() ? $adLocation->getLocationCountry()->getId() : null;
                $adLocationData[]['domicile_id'] = $adLocation->getLocationDomicile() ? $adLocation->getLocationDomicile()->getId() : null;
                $adLocationData[]['town_id']     = $adLocation->getLocationTown() ? $adLocation->getLocationTown()->getId() : null;
                $adLocationData[]['locality_id'] = $adLocation->getLocality() ? $adLocation->getLocality()->getId() : null;
                $adLocationData[]['postcode']    = $adLocation->getPostcode();
                $adLocationData[]['latitude']    = $adLocation->getLatitude();
                $adLocationData[]['longitude']   = $adLocation->getLongitude();
            }
        }

        return $adLocationData;
    }
    
    /**
     * Find the town by ad id.
     *
     * @param integer $adId Ad id.
     *
     * @return array
     */
    public function findLastAdLocationById($adId)
    {
    	$qb = $this->getBaseQueryBuilder();
    	$qb->andWhere(self::ALIAS.'.ad = :adId');
    	$qb->setParameter('adId', $adId);
    	$qb->addOrderBy(self::ALIAS.'.id', 'DESC');
    	$qb->setMaxResults(1);
    	
    	$adTown = $qb->getQuery()->getOneOrNullResult();
    	
    	if ($adTown && $adTown->getLocationTown()->getId() != NULL) {
    		return $adTown->getLocationTown()->getId();
    	} else {
    		return null;
    	}
    }
}
