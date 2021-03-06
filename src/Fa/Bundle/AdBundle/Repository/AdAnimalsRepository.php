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
use Fa\Bundle\AdBundle\Solr\AdAnimalsSolrFieldMapping;
use Fa\Bundle\AdBundle\Entity\AdAnimals;

/**
 * AdAnimalsRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdAnimalsRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'afa';

    const AD_TYPE_FOR_SALE_ID_PETS      = 2620;
    const AD_TYPE_FOR_SALE_ID_HORSES    = 2763;
    const AD_TYPE_FOR_SALE_ID_LIVESTOCK = 2891;

    /**
     * prepareQueryBuilder.
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
     * @param object $ad        Ad object.
     * @param object $container Container identifier.
     *
     * @return Apache_Solr_Document
     */
    public function getSolrDocument($ad, $container)
    {
        $document = new \SolrInputDocument($ad);

        $document = $this->_em->getRepository('FaAdBundle:Ad')->getSolrDocument($ad, $document, $container);

        $categoryId = ($ad->getCategory() ? $ad->getCategory()->getId() : null);
        // get for sale object
        $adAnimal = $this->findOneBy(array('ad' => $ad->getId()));

        if ($adAnimal) {
            $document = $this->addField($document, AdAnimalsSolrFieldMapping::BREED_ID, $adAnimal->getBreedId());

            if ($adAnimal->getGenderId()) {
                $genderIds = explode(',', $adAnimal->getGenderId());
                foreach ($genderIds as $genderId) {
                    $document = $this->addField($document, AdAnimalsSolrFieldMapping::GENDER_ID, $genderId);
                }
            }

            $document = $this->addField($document, AdAnimalsSolrFieldMapping::COLOUR_ID, $adAnimal->getColourId());
            $document = $this->addField($document, AdAnimalsSolrFieldMapping::SPECIES_ID, $adAnimal->getSpeciesId());
            $document = $this->addField($document, AdAnimalsSolrFieldMapping::META_DATA, $adAnimal->getMetaData());

            // unserialize meta data
            $metaData = unserialize($adAnimal->getMetaData());

            if (isset($metaData['age_id'])) {
                $document = $this->addField($document, AdAnimalsSolrFieldMapping::AGE_ID, $metaData['age_id']);
            }

            if (isset($metaData['condition_id'])) {
                $document = $this->addField($document, AdAnimalsSolrFieldMapping::CONDITION_ID, $metaData['condition_id']);
            }

            if (isset($metaData['height_id'])) {
                $document = $this->addField($document, AdAnimalsSolrFieldMapping::HEIGHT_ID, $metaData['height_id']);
            }
        }

        // update keyword search fields.
        $keywordSearch = $this->_em->getRepository('FaAdBundle:Ad')->getKeywordSearchArray($ad, $categoryId, $adAnimal, $container);
        if (count($keywordSearch)) {
            $document = $this->addField($document, AdAnimalsSolrFieldMapping::KEYWORD_SEARCH, implode(',', $keywordSearch));
        }

        return $document;
    }

    /**
     * @param $ad
     * @param $container
     * @return object|\SolrInputDocument
     */
    public function getSolrDocumentNew($ad, $container)
    {
        $document = new \SolrInputDocument($ad);

        $document = $this->_em->getRepository('FaAdBundle:Ad')->getSolrDocumentNew($ad, $document, $container);

        $categoryId = ($ad->getCategory() ? $ad->getCategory()->getId() : null);
        // get for sale object
        $adAnimal = $this->findOneBy(array('ad' => $ad->getId()));

        if ($adAnimal) {
            $listingDimensions = $this->getAdListingFields();
            $entityRepository = $this->_em->getRepository('FaEntityBundle:Entity');
            $adRepository = $this->_em->getRepository('FaAdBundle:Ad');

            $document = $this->addField($document, $adRepository->getSolrFieldName($listingDimensions, 'breed'), $entityRepository->getCachedEntityById($container, $adAnimal->getBreedId()));

            if ($adAnimal->getGenderId()) {
                $genderIds = explode(',', $adAnimal->getGenderId());

                $genders = [];
                foreach ($genderIds as $genderId) {
                    $genders[] = $entityRepository->getCachedEntityById($container, $genderId);
                }

                $document = $this->addField($document, $adRepository->getSolrFieldName($listingDimensions, 'gender'), $genders);
            }

            $document = $this->addField($document, $adRepository->getSolrFieldName($listingDimensions, 'colour'), $entityRepository->getCachedEntityById($container, $adAnimal->getColourId()));

            $document = $this->addField($document, $adRepository->getSolrFieldName($listingDimensions, 'species'), $entityRepository->getCachedEntityById($container, $adAnimal->getSpeciesId()));

            $document = $this->addField($document, 'meta_values', $adAnimal->getMetaData());

            // unserialize meta data
            $metaData = unserialize($adAnimal->getMetaData());

            if (isset($metaData['age_id'])) {
                $document = $this->addField($document, $adRepository->getSolrFieldName($listingDimensions, 'age'), $entityRepository->getCachedEntityById($container, $metaData['age_id']));
            }

            if (isset($metaData['condition_id'])) {
                $document = $this->addField($document, $adRepository->getSolrFieldName($listingDimensions, 'condition'), $entityRepository->getCachedEntityById($container, $metaData['condition_id']));
            }

            if (isset($metaData['height_id'])) {
                $document = $this->addField($document, $adRepository->getSolrFieldName($listingDimensions, 'height'), $entityRepository->getCachedEntityById($container, $metaData['height_id']));
            }
        }

        // update keyword search fields.
        $keywordSearch = $this->_em->getRepository('FaAdBundle:Ad')->getKeywordSearchArray($ad, $categoryId, $adAnimal, $container);
        if (count($keywordSearch)) {
            $document = $this->addField($document, 'keyword_search', implode(',', $keywordSearch));
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
            if (is_array($value)) {
                $value = (string) json_encode($value);
            }

            if (!is_string($value)) {
                $value = (string) $value;
            }

            $document->addField($field, $value);
        }

        return $document;
    }

    /**
     * Get ad forsale fields.
     *
     * @return array
     */
    public function getAllFields()
    {
        return array(
            'gender_id',
            'colour_id',
            'breed_id',
            'species_id',
            'age_id',
            'quantity__how_many_are_available_id',
            'height_id',
            'condition_id',
            'colour',
            'breed',
            'species'
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
            'age_id',
            'quantity__how_many_are_available_id',
            'height_id',
            'condition_id',
            'colour',
            'breed',
            'species'
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
            'gender_id',
            'colour_id',
            'breed_id',
            'species_id'
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
                $object = new AdAnimals();
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
     * Get ad listing fields.
     *
     * @return array
     */
    public function getAdListingFields()
    {
        $adListingFields['CATEGORY_ID|FaEntityBundle:Category'] = 'CATEGORY_ID';
        $adListingFields['AGE_ID|FaEntityBundle:Entity']        = 'AGE_ID';
        $adListingFields['BREED_ID|FaEntityBundle:Entity']      = 'BREED_ID';
        $adListingFields['SPECIES_ID|FaEntityBundle:Entity']      = 'SPECIES_ID';

        return $adListingFields;
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
}
