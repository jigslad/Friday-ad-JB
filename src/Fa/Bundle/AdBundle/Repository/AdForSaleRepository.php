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
use Fa\Bundle\AdBundle\Solr\AdForSaleSolrFieldMapping;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Repository\EntityRepository as BaseEntityRepository;
use Fa\Bundle\AdBundle\Entity\AdForSale;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Fa\Bundle\AdBundle\Solr\AdSolrFieldMapping;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;

/**
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd.
 * @version v1.0
 */
class AdForSaleRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'afs';

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
     * Get ad forsale fields.
     *
     * @return array
     */
    public function getAdForSaleFields()
    {
        return array(
                   'condition_id',
                   'net_profit_min',
                   'net_profit_max',
                   'turnover_min',
                   'turnover_max',
                   'dimensions_length',
                   'dimensions_width',
                   'dimensions_height',
                   'dimensions_unit',
                   'waist_id',
                   'leg_id',
                   'neck_id',
                   'power',
                   'event_date',
                   'age_range_id',
                   'brand_id',
                   'brand_clothing_id',
                   'business_type_id',
                   'colour_id',
                   'main_colour_id',
                   'size_id',
                   'colour',
                   'brand',
                   'brand_clothing',
                   'main_colour'
               );
    }

    /**
     * Get ad not-inexed forsale fields.
     *
     * @return array
     */
    public function getNotIndexedAdForSaleFields()
    {
        return array(
                   'net_profit_min',
                   'net_profit_max',
                   'turnover_min',
                   'turnover_max',
                   'dimensions_length',
                   'dimensions_width',
                   'dimensions_height',
                   'dimensions_unit',
                   'waist_id',
                   'leg_id',
                   'neck_id',
                   'power',
                   'event_date',
                   'colour',
                   'brand',
                   'brand_clothing',
                   'main_colour'
               );
    }

    /**
     * Get ad inexed forsale fields.
     *
     * @return array
     */
    public function getIndexedAdForSaleFields()
    {
        return array(
                   'condition_id',
                   'age_range_id',
                   'brand_id',
                   'brand_clothing_id',
                   'business_type_id',
                   'colour_id',
                   'main_colour_id',
                   'size_id',
               );
    }

    /**
     * Get ad forsale fields.
     *
     * @return array
     */
    public function getAllFields()
    {
        return $this->getAdForSaleFields();
    }

    /**
     * Get ad not-inexed forsale fields.
     *
     * @return array
     */
    public function getNotIndexedFields()
    {
        return $this->getNotIndexedAdForSaleFields();
    }

    /**
     * Get ad inexed forsale fields.
     *
     * @return array
     */
    public function getIndexedFields()
    {
        return $this->getIndexedAdForSaleFields();
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
        $logoURL  = null;

        $document = $this->_em->getRepository('FaAdBundle:Ad')->getSolrDocument($ad, $document, $container);

        $categoryId = ($ad->getCategory() ? $ad->getCategory()->getId() : null);
        // get for sale object
        $adForSale = $this->findOneBy(array('ad' => $ad->getId()));

        if ($adForSale) {
            $entityManager = $container->get('fa.entity.cache.manager');
            $conditionId   = $adForSale->getConditionId();
            $brandId       = $adForSale->getBrandId();
            $conditionVal  = ($conditionId == BaseEntityRepository::CONDITION_NEW_ID ? '1' : ($conditionId == BaseEntityRepository::CONDITION_POOR_ID ? '0' : null));

            $document = $this->addField($document, AdForSaleSolrFieldMapping::CONDITION_ID, $conditionId);
            $document = $this->addField($document, AdForSaleSolrFieldMapping::AGE_RANGE_ID, $adForSale->getAgeRangeId());
            $document = $this->addField($document, AdForSaleSolrFieldMapping::BRAND_ID, $brandId);
            $document = $this->addField($document, AdForSaleSolrFieldMapping::BRAND_CLOTHING_ID, $adForSale->getBrandClothingId());
            $document = $this->addField($document, AdForSaleSolrFieldMapping::BUSINESS_TYPE_ID, $adForSale->getBusinessTypeId());
            $document = $this->addField($document, AdForSaleSolrFieldMapping::COLOUR_ID, $adForSale->getColourId());
            $document = $this->addField($document, AdForSaleSolrFieldMapping::MAIN_COLOUR_ID, $adForSale->getMainColourId());
            $document = $this->addField($document, AdForSaleSolrFieldMapping::SIZE_ID, $adForSale->getSizeId());
            $document = $this->addField($document, AdForSaleSolrFieldMapping::META_DATA, $adForSale->getMetaData());
            //$document = $this->addField($document, AdForSaleSolrFieldMapping::CONDITION_NEW, $conditionVal);
            //$document = $this->addField($document, AdForSaleSolrFieldMapping::BRAND_NAME, $entityManager->getEntityNameById('FaEntityBundle:Entity', $brandId));

            //for business advertiser only.
            if ($ad->getIsTradeAd() && $ad->getUser()) {
                $logoURL = CommonManager::getUserLogoByUserId($container, $ad->getUser()->getId(), false, true);
            }

            $document = $this->addField($document, AdForSaleSolrFieldMapping::HAS_USER_LOGO, ($logoURL ? true : false));
        }

        // update keyword search fields.
        $keywordSearch = $this->_em->getRepository('FaAdBundle:Ad')->getKeywordSearchArray($ad, $categoryId, $adForSale, $container);
        if (count($keywordSearch)) {
            $document = $this->addField($document, AdForSaleSolrFieldMapping::KEYWORD_SEARCH, implode(',', $keywordSearch));
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
        $adForSale = $this->findOneBy(array('ad' => $ad->getId()));

        if ($adForSale) {
            $listingDimensions = $this->getAdListingFields();
            $entityRepository = $this->_em->getRepository('FaEntityBundle:Entity');
            $conditionId   = $adForSale->getConditionId();
            $brandId       = $adForSale->getBrandId();
            $adRepository  = $this->_em->getRepository('FaAdBundle:Ad');

            $document = $this->addField($document, $adRepository->getSolrFieldName($listingDimensions, 'condition'), $entityRepository->getCachedEntityById($container, $conditionId));
            $document = $this->addField($document, $adRepository->getSolrFieldName($listingDimensions, 'age_range'), $entityRepository->getCachedEntityById($container, $adForSale->getAgeRangeId()));
            $document = $this->addField($document, $adRepository->getSolrFieldName($listingDimensions, 'brand'), $entityRepository->getCachedEntityById($container, $brandId));
            $document = $this->addField($document, $adRepository->getSolrFieldName($listingDimensions, 'brand_clothing'), $entityRepository->getCachedEntityById($container, $adForSale->getBrandClothingId()));
            $document = $this->addField($document, $adRepository->getSolrFieldName($listingDimensions, 'business_type'), $entityRepository->getCachedEntityById($container, $adForSale->getBusinessTypeId()));
            $document = $this->addField($document, $adRepository->getSolrFieldName($listingDimensions, 'colour'), $entityRepository->getCachedEntityById($container, $adForSale->getColourId()));
            $document = $this->addField($document, $adRepository->getSolrFieldName($listingDimensions, 'main_colour'), $entityRepository->getCachedEntityById($container, $adForSale->getMainColourId()));
            $document = $this->addField($document, $adRepository->getSolrFieldName($listingDimensions, 'size'), $entityRepository->getCachedEntityById($container, $adForSale->getSizeId()));
            $document = $this->addField($document, 'meta_values', $adForSale->getMetaData());

            //for business advertiser only.
            $logoURL = NULL;
            if ($ad->getIsTradeAd() && $ad->getUser()) {
                $logoURL = CommonManager::getUserLogoByUserId($container, $ad->getUser()->getId(), false, true);
            }

            $document = $this->addField($document, 'has_logo_url', ($logoURL ? true : false));
        }

        // update keyword search fields.
        $keywordSearch = $this->_em->getRepository('FaAdBundle:Ad')->getKeywordSearchArray($ad, $categoryId, $adForSale, $container);
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
                $object = new AdForSale();
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
     * Get common sorting array.
     *
     * @param object $container Container identifier.
     *
     * @return array
     */
    public function getSortingArrayOld($container)
    {
        $translator                                      = CommonManager::getTranslator($container);
        $sortingArray['ad_for_sale__is_new|desc']        = $translator->trans('New items first');
        $sortingArray['ad_for_sale__is_new|asc']         = $translator->trans('Used items first');
        $sortingArray['ad_for_sale__condition_new|desc'] = $translator->trans('Condition: New with tags first');
        $sortingArray['ad_for_sale__condition_new|asc']  = $translator->trans('Condition: Poor first');
        $sortingArray['ad_for_sale__brand_name|asc']     = $translator->trans('Brand');

        return $sortingArray;
    }

    /**
     * Get dimension fields.
     *
     * @return array
     */
    public function getAdDetailTabFields()
    {
        $adDimensionFields['dimension'][] = 'DIMENSIONS_LENGTH';
        $adDimensionFields['dimension'][] = 'DIMENSIONS_WIDTH';
        $adDimensionFields['dimension'][] = 'DIMENSIONS_HEIGHT';

        return $adDimensionFields;
    }

    /**
     * Get ad listing fields.
     *
     * @return array
     */
    public function getAdListingFields()
    {
        $adListingFields['IS_NEW']                             = 'IS_NEW';
        $adListingFields['CONDITION_ID|FaEntityBundle:Entity'] = 'CONDITION_ID';
        $adListingFields['DELIVERY_METHOD_OPTION_ID']          = 'DELIVERY_METHOD_OPTION_ID';

        return $adListingFields;
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
     * Get popular shops.
     *
     * @param object  $container     Container identifier.
     * @param array   $searchParams  Search parameters.
     *
     * @return array
     */
    public function getPopularShops($container, $searchParams)
    {
        $forSalePopularShops = $this->_em->getRepository('FaUserBundle:UserUpsell')->getUserArrayWithPopularShopUpsell($container);
        $forSalePopularShopIds = array();
        $popularShops = array();
        if (count($forSalePopularShops)) {
            shuffle($forSalePopularShops);
            for ($i = 0; $i < 12; $i++) {
                if (isset($forSalePopularShops[$i])) {
                    $popularShops[] = $forSalePopularShops[$i];
                    $forSalePopularShopIds[] = $forSalePopularShops[$i]['id'];
                }
            }
        }

        $totalPopularShopsCount = count($popularShops);
        if ($totalPopularShopsCount < 12) {
            $popularShopUsers = $this->getPopularShopsForSale($container, $searchParams, (count($forSalePopularShopIds) ? ' AND -'.AdSolrFieldMapping::USER_ID.': ("'.implode('" "', $forSalePopularShopIds).'")' : null), false, true);
            for ($i = 0; $i < (12 - $totalPopularShopsCount); $i++) {
                if (isset($popularShopUsers[$i])) {
                    $popularShops[$i+$totalPopularShopsCount] = $popularShopUsers[$i];
                }
            }
        }

        return $popularShops;
    }

    /**
     * Get popular shops.
     *
     * @param object  $container     Container identifier.
     * @param array   $searchParams  Search parameters.
     * @param string  $staticFilters Static filters.
     * @param boolean $randomSort    Boolean true / false.
     * @param boolean $hasUserLogo   Boolean true / false.
     *
     * @return array
     */
    public function getPopularShopsForSale($container, $searchParams, $staticFilters = null, $randomSort = false, $hasUserLogo = false)
    {
        // If ad owner has logo
        if ($hasUserLogo) {
            $searchParams = $searchParams + array('ad_for_sale__has_user_logo' => 1);
        }

        $searchParams = $searchParams + array('ad__ad_user_business_category_id' => CategoryRepository::FOR_SALE_ID);

        $data           = array();
        $data['search'] = $searchParams;
        $data['search']['item__status_id'] = BaseEntityRepository::AD_STATUS_LIVE_ID;

        $container->get('fa.searchfilters.manager')->init($this->_em->getRepository('FaAdBundle:Ad'), $this->_em->getClassMetadata('FaAdBundle:Ad'), 'search', $data);
        $data = $container->get('fa.searchfilters.manager')->getFiltersData();

        $getDefaultRadius = '';
        $getDefaultRadius = $this->_em->getRepository('FaEntityBundle:Category')->getDefaultRadiusBySearchParams($searchParams, $container);

        if (isset($data['search']['item__location'])) {
            $data['query_filters']['item']['location'] = $data['search']['item__location'].'|'.$getDefaultRadius;
        }

        $data['query_sorter'] = array();
        if ($randomSort) {
            $data['query_sorter']['item']['random'] = array('sort_ord' => 'desc', 'field_ord' => 1);
        } else {
            $data['query_sorter']['item']['weekly_refresh_published_at'] = array('sort_ord' => 'desc', 'field_ord' => 1);
        }

        // List no affliate
        $data['query_filters']['item']['is_affiliate_ad'] = 0;

        $data['select_fields']  = array('item' => array('user_id'));
        $data['group_fields'] = array(
            AdSolrFieldMapping::USER_ID => array('limit' => 1),
        );
        if ($staticFilters) {
            $data['static_filters'] = $staticFilters;
        }
        // initialize solr search manager service and fetch data based of above prepared search options
        $solrSearchManager = $container->get('fa.solrsearch.manager');
        $solrSearchManager->init('ad', null, $data, 1, 12, 0, true);
        $solrResponse = $solrSearchManager->getSolrResponse();
        $facetResult = $container->get('fa.solrsearch.manager')->getSolrResponseGroupFields($solrResponse);
        $userDetails = array();
        if (isset($facetResult[AdSolrFieldMapping::USER_ID]) && isset($facetResult[AdSolrFieldMapping::USER_ID]['groups']) && count($facetResult[AdSolrFieldMapping::USER_ID]['groups'])) {
            $adUsers = $facetResult[AdSolrFieldMapping::USER_ID]['groups'];
            foreach ($adUsers as $userCnt => $adUser) {
                $adUser = get_object_vars($adUser);
                if (isset($adUser['doclist']['docs']) && count($adUser['doclist']['docs'])) {
                    if (isset($adUser['doclist']['docs'][0][AdSolrFieldMapping::USER_ID]) && $adUser['doclist']['docs'][0][AdSolrFieldMapping::USER_ID]) {
                        $userDetails[] = array(
                            'id' => $adUser['doclist']['docs'][0][AdSolrFieldMapping::USER_ID],
                            'shop_name' => $this->_em->getRepository('FaUserBundle:User')->getUserProfileName($adUser['doclist']['docs'][0][AdSolrFieldMapping::USER_ID], $container),
                        );
                    }
                }
            }
        }

        return $userDetails;
    }
}
