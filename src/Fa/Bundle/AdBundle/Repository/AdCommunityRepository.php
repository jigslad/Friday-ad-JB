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
use Fa\Bundle\AdBundle\Solr\AdCommunitySolrFieldMapping;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Entity\AdCommunity;

/**
 * AdCommunityRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class AdCommunityRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'afj';

    /**
     * Prepare query builder.
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
     * Find the dimension by ad id.
     *
     * @param integer $adId Ad id.
     *
     * @return array
     */
    public function findByAdId($adId)
    {
        $qb = $this->getBaseQueryBuilder();

        if (!is_array($adId)) {
            $adId = array($adId);
        }

        $qb->andWhere(self::ALIAS.'.ad IN (:adId)');
        $qb->setParameter('adId', $adId);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Returns ad solr document object.
     *
     * @param object $ad Ad object.
     *
     * @return Apache_Solr_Document
     */
    public function getSolrDocument($ad, $container)
    {
        $document = new \SolrInputDocument($ad);

        $document = $this->_em->getRepository('FaAdBundle:Ad')->getSolrDocument($ad, $document, $container);

        $categoryId = ($ad->getCategory() ? $ad->getCategory()->getId() : null);
        // get for sale object
        $adCommunity = $this->findOneBy(array('ad' => $ad->getId()));

        if ($adCommunity) {
            $document = $this->addField($document, AdCommunitySolrFieldMapping::EXPERIENCE_LEVEL_ID, $adCommunity->getExperienceLevelId());
            $document = $this->addField($document, AdCommunitySolrFieldMapping::EDUCATION_LEVEL_ID, $adCommunity->getEducationLevelId());
            $document = $this->addField($document, AdCommunitySolrFieldMapping::CUISINE_TYPE_ID, $adCommunity->getCuisineTypeId());

            $metaData = ($adCommunity->getMetaData() ? unserialize($adCommunity->getMetaData()) : null);
            if ($metaData && count($metaData)) {
                $document = $this->addField($document, AdCommunitySolrFieldMapping::META_DATA, $adCommunity->getMetaData());

                $start = (isset($metaData['event_start']) ? $metaData['event_start'] : '').(isset($metaData['event_start_time']) ?' '.$metaData['event_start_time']: '');
                $end   = (isset($metaData['event_end']) ? $metaData['event_end'] : '').(isset($metaData['event_end_time']) ?' '.$metaData['event_end_time']: '');

                if ($start != '') {
                    $document = $this->addField($document, AdCommunitySolrFieldMapping::EVENT_START, strtotime(str_replace('/', '-', $start)));
                }

                if ($end != '') {
                    $document = $this->addField($document, AdCommunitySolrFieldMapping::EVENT_END, strtotime(str_replace('/', '-', $end)));
                } else {
                    $document = $this->addField($document, AdCommunitySolrFieldMapping::NO_EVENT_END, 1);
                }
            }
        }

        // update keyword search fields.
        $keywordSearch = $this->_em->getRepository('FaAdBundle:Ad')->getKeywordSearchArray($ad, $categoryId, $adCommunity, $container);
        if (count($keywordSearch)) {
            $document = $this->addField($document, AdCommunitySolrFieldMapping::KEYWORD_SEARCH, implode(',', $keywordSearch));
        }

        return $document;
    }

    /**
     * Get ad vertical data array.
     *
     * @param object $adId Ad id.
     *
     * @return array
     */
    public function getAdVerticalDataArray($adId)
    {
        $adVerticalData = $this->findByAdId($adId);
        if (count($adVerticalData)) {
            return array_filter($adVerticalData[0], 'strlen');
        }

        return array();
    }

    /**
     * Remove ad from vertical by ad id.
     *
     * @param integer $adId Ad id.
     */
    public function removeByAdId($adId)
    {
        $adVertical = $this->createQueryBuilder(self::ALIAS)
        ->andWhere(self::ALIAS.'.ad = :adId')
        ->setParameter('adId', $adId)
        ->getQuery()
        ->getOneOrNullResult();

        if ($adVertical) {
            $this->_em->remove($adVertical);
            $this->_em->flush($adVertical);
        }
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
     * Get event date for listing
     *
     * @param integer $eventStartDate Event start date.
     * @param integer $eventEndDate   Event end date.
     * @param object  $container      Continer identifier.
     *
     * @return mixed
     */
    public function getEventDateForListing($eventStartDate, $eventEndDate, $container)
    {
        if (!$eventEndDate) {
            $eventEndDate = $eventStartDate;
        }

        $eventDateString = null;
        $today           = date('Y-m-d', strtotime('today'));
        $tomorrow        = date('Y-m-d', strtotime('tomorrow'));
        $eventStartDate  = date('Y-m-d', $eventStartDate);
        $eventEndDate    = date('Y-m-d', $eventEndDate);

        if ($eventStartDate == $eventEndDate) {
            if ($eventStartDate == $today) {
                $eventDateString = 'Today';
            } elseif ($eventStartDate == $tomorrow) {
                $eventDateString = 'Tomorrow';
            } else {
                $eventDateString = CommonManager::formatDate(strtotime($eventStartDate), $container, null, null, 'dd MMM');
            }
        } elseif ($eventStartDate != $eventEndDate) {
            if ($eventStartDate < $today && $eventEndDate >= $today) {
                $eventDateString = 'Today';
            } else {
                $eventDateString  = CommonManager::formatDate(strtotime($eventStartDate), $container, null, null, 'dd MMM');
                $eventDateString .= ' - '.CommonManager::formatDate(strtotime($eventEndDate), $container, null, null, 'dd MMM');
            }
        }

        return $eventDateString;
    }

    /**
     * Get ad forsale fields.
     *
     * @return array
     */
    public function getAllFields()
    {
        return array(
            'event_start',
            'event_start_time',
            'event_end',
            'event_end_time',
            'include_end_time',
            'venue_name',
            'experience_level_id',
            'education_level_id',
            'class_size_id',
            'equipment_provided_id',
            'availability_id',
            'cuisine_type_id',
            'level_id'
        );
    }

    /**
     * Get ad not-inexed forsale fields.
     *
     * @return array
     */
    public function getNotIndexedFields()
    {
        return array(
            'event_start',
            'event_start_time',
            'event_end',
            'event_end_time',
            'include_end_time',
            'venue_name',
            'class_size_id',
            'equipment_provided_id',
            'availability_id',
            'level_id'
        );
    }

    /**
     * Get ad inexed forsale fields.
     *
     * @return array
     */
    public function getIndexedFields()
    {
        return array(
            'experience_level_id',
            'education_level_id',
            'cuisine_type_id',
        );
    }

    /**
     * Update data from moderation.
     *
     * @param array $data Data from moderation.
     */
    public function updateDataFromModeration($data)
    {
        foreach ($data as $element) {
            $object = null;
            if (isset($element['id'])) {
                $object = $this->findOneBy(array('id' => $element['id']));
            } else {
                $object = $this->findOneBy(array('ad' => $element['ad_id']));
            }

            if (!$object && isset($element['ad_id'])) {
                $object = new AdCommunity();
                $ad = $this->_em->getRepository('FaAdBundle:Ad')->findOneBy(array('id' => $element['ad_id']));
                if ($ad) {
                    $object->setAd($ad);
                }
            }

            foreach ($element as $field => $value) {
                $methodName = 'set'.str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));
                if (method_exists($object, $methodName) === true) {
                    if ($value === '') {
                        $value = null;
                    }
                    $object->$methodName($value);
                }
            }
            if ($object) {
                $this->_em->persist($object);
                $this->_em->flush($object);
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
    public function setObjectFromModerationData($element)
    {
        if (isset($element['id'])) {
            $object = $this->findOneBy(array('id' => $element['id']));
        } else {
            $object = $this->findOneBy(array('ad' => $element['ad_id']));
        }

        foreach ($element as $field => $value) {
            $methodName = 'set'.str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));
            if (method_exists($object, $methodName) === true) {
                $object->$methodName($value);
            }
        }

        return $object;
    }

    /**
     * Get ad listing fields.
     *
     * @return array
     */
    public function getAdListingFields()
    {
        return array();
    }
}
