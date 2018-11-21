<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Search;

use Fa\Bundle\CoreBundle\Search\SolrSearch;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;

/**
 * This file is used to add filters, sorting for ad solr fields.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdSolrSearch extends SolrSearch
{
    /**
     * Get current repository.
     *
     * @return object
     */
    public function getTableName()
    {
        return 'ad';
    }

    /**
     * Add id filter to solr query.
     *
     * @param integer $id User id.
     *
     */
    protected function addIdFilter($id = null)
    {
        if (!is_array($id)) {
            $id = array($id);
        }

        $id = array_filter($id);

        if (count($id) && defined($this->getSolrFieldMappingClass().'::ID')) {
            $query = constant($this->getSolrFieldMappingClass().'::ID').':'.implode(' OR '.constant($this->getSolrFieldMappingClass().'::ID').':', $id);
            $query = ' AND ('.$query.')';

            $this->query .= $query;
        }
    }

    /**
     * Add user id filter to solr query.
     *
     * @param integer $id User id.
     *
     */
    protected function addUserIdFilter($userId = null)
    {
        if (!is_array($userId)) {
            $userId = array($userId);
        }
        $tmpUseIds = array();
        foreach ($userId as $id) {
            $tmpUseIds[] = '"'.$id.'"';
        }
        $userId = $tmpUseIds;

        if (defined($this->getSolrFieldMappingClass().'::USER_ID')) {
            $query = constant($this->getSolrFieldMappingClass().'::USER_ID').':('.implode(' OR ', $userId).')';
            $query = ' AND ('.$query.')';

            $this->query .= $query;
        }
    }

    /**
     * Add category id filter to solr query.
     *
     * @param integer $id Category id.
     */
    protected function addCategoryIdFilter($id = null)
    {
        if ($id) {
            if (!is_array($id)) {
                $id = array($id);
            }

            if (defined($this->getSolrFieldMappingClass().'::CATEGORY_ID')) {
                $id = array_filter($id);
                if (count($id)) {
                    $query = constant($this->getSolrFieldMappingClass().'::CATEGORY_ID').':('.implode(' OR ', $id).')';

                    // check with all parent category ids
                    $totalLevel = 6;
                    for ($i = 1; $i <= $totalLevel; $i++) {
                        $query .= ' OR a_parent_category_lvl_'.$i.'_id_i'.':('.implode(' OR ', $id).')';
                    }

                    $query = ' AND ('.$query.')';
                    $this->query .= $query;
                }
            }
        }
    }

    /**
     * Add type id filter to solr query.
     *
     * @param integer $id Ad type id.
     */
    protected function addAdTypeIdFilter($id = null)
    {
        if (!is_array($id)) {
            $id = array($id);
        }

        $id = array_filter($id);

        if (count($id)) {
            if (defined($this->getSolrFieldMappingClass().'::TYPE_ID')) {
                $query = constant($this->getSolrFieldMappingClass().'::TYPE_ID').':'.implode(' OR '.constant($this->getSolrFieldMappingClass().'::TYPE_ID').':', $id);
                $query = ' AND ('.$query.')';

                $this->query .= $query;
            }
        }
    }

    /**
     * Add type id filter to solr query.
     *
     * @param integer $id Ad type id.
     */
    protected function addTypeIdFilter($id = null)
    {
        if (!is_array($id)) {
            $id = array($id);
        }

        $id = array_filter($id);

        if (count($id)) {
            if (defined($this->getSolrFieldMappingClass().'::TYPE_ID')) {
                $query = constant($this->getSolrFieldMappingClass().'::TYPE_ID').':'.implode(' OR '.constant($this->getSolrFieldMappingClass().'::TYPE_ID').':', $id);
                $query = ' AND ('.$query.')';

                $this->query .= $query;
            }
        }
    }

    /**
     * Add status id filter to solr query.
     *
     * @param integer $id Ad status id.
     */
    protected function addStatusIdFilter($id = null)
    {
        if (!is_array($id)) {
            $id = array($id);
        }

        $id = array_filter($id);

        if (count($id)) {
            if (defined($this->getSolrFieldMappingClass().'::STATUS_ID')) {
                $query = constant($this->getSolrFieldMappingClass().'::STATUS_ID').':'.implode(' OR '.constant($this->getSolrFieldMappingClass().'::STATUS_ID').':', $id);
                $query = ' AND ('.$query.')';

                $this->query .= $query;
            }
        }
    }

    /**
     * Add price_from_to filter to existing solr query.
     *
     * @param string $fromTo
     */
    protected function addPriceFromToFilter($fromTo = null)
    {
        list($from, $to) = explode('|', $fromTo);

        $from = preg_replace("/[,\s]/", '', $from);
        $to   = preg_replace("/[,\s]/", '', $to);

        if (!is_numeric($from)) {
            $from = null;
        }

        if (!is_numeric($to)) {
            $to = null;
        }

        $this->addFromToFilter('price', $from, $to);
    }

    /**
     * Add price_from_to filter to existing solr query.
     *
     * @param string $fromTo
     */
    protected function addPublishedAtFromToFilter($fromTo = null)
    {
        list($from, $to) = explode('|', $fromTo);

        $from = preg_replace("/[,\s]/", '', $from);
        $to   = preg_replace("/[,\s]/", '', $to);

        if (!is_numeric($from)) {
            $from = null;
        }

        if (!is_numeric($to)) {
            $to = null;
        }

        $this->addFromToFilter('published_at', $from, $to);
    }

    /**
     * Add town id filter to solr query.
     *
     * @param integer $id Location id.
     */
    protected function addTownIdFilter($id = null)
    {
        if ($id) {
            if (!is_array($id)) {
                $id = array($id);
            }

            if (defined($this->getSolrFieldMappingClass().'::TOWN_ID')) {
                $id = array_filter($id);
                if (count($id)) {
                    $query = constant($this->getSolrFieldMappingClass().'::TOWN_ID').':'.implode(' OR '.constant($this->getSolrFieldMappingClass().'::TOWN_ID').':', $id);
                    $query = ' AND ('.$query.')';

                    $this->query .= $query;
                }
            }
        }
    }

    /**
     * Add county id filter to solr query.
     *
     * @param integer $id Location id.
     */
    protected function addCountyIdFilter($id = null)
    {
        if ($id) {
            if (!is_array($id)) {
                $id = array($id);
            }

            if (defined($this->getSolrFieldMappingClass().'::DOMICILE_ID')) {
                $id = array_filter($id);
                if (count($id)) {
                    $query = constant($this->getSolrFieldMappingClass().'::DOMICILE_ID').':'.implode(' OR '.constant($this->getSolrFieldMappingClass().'::DOMICILE_ID').':', $id);
                    $query = ' AND ('.$query.')';

                    $this->query .= $query;
                }
            }
        }
    }

    /**
     * Add locality id filter to solr query.
     *
     * @param integer $id Location id.
     */
    protected function addLocalityIdFilter($id = null)
    {
        if ($id) {
            if (!is_array($id)) {
                $id = array($id);
            }

            if (defined($this->getSolrFieldMappingClass().'::LOCALITY_ID')) {
                $id = array_filter($id);
                if (count($id)) {
                    $query = constant($this->getSolrFieldMappingClass().'::LOCALITY_ID').':'.implode(' OR '.constant($this->getSolrFieldMappingClass().'::LOCALITY_ID').':', $id);
                    $query = ' AND ('.$query.')';

                    $this->query .= $query;
                }
            }
        }
    }
    
    /**
     * Add area id filter to solr query.
     *
     * @param integer $id Location id.
     */
    protected function addAreaIdFilter($id = null)
    {
        if ($id) {
            if (!is_array($id)) {
                $id = array($id);
            }
            
            if (defined($this->getSolrFieldMappingClass().'::AREA_ID')) {
                $id = array_filter($id);
                if (count($id)) {
                    $query = constant($this->getSolrFieldMappingClass().'::AREA_ID').':'.implode(' OR '.constant($this->getSolrFieldMappingClass().'::AREA_ID').':', $id);
                    $query = ' AND ('.$query.')';
                    
                    $this->query .= $query;
                }
            }
        }
    }
    

    /**
     * Add is top ad filter to solr query.
     *
     * @param boolean $isTopAd Ad has top ad upsell or not.
     */
    protected function addIsTopAdFilter($isTopAd = 1)
    {
        if (defined($this->getSolrFieldMappingClass().'::IS_TOP_AD')) {
            $query        = constant($this->getSolrFieldMappingClass().'::IS_TOP_AD').':'.$isTopAd;
            $this->query .= ' AND ('.$query.')';
        }
    }

    /**
     * Add feature ad to solr query.
     *
     * @param boolean $isHomePageFeatureAd Ad has feature ad upsell or not.
     */
    protected function addIsHomePageFeatureAdFilter($isHomePageFeatureAd = 1)
    {
        if (defined($this->getSolrFieldMappingClass().'::IS_HOMEPAGE_FEATURE_AD')) {
            $query        = constant($this->getSolrFieldMappingClass().'::IS_HOMEPAGE_FEATURE_AD').':'.$isHomePageFeatureAd;
            $this->query .= ' AND ('.$query.')';
        }
    }

    /**
     * Add location filter to solr query.
     *
     * @param string $location
     */
    protected function addLocationFilter($location = null)
    {
        $locationData = explode('|', $location);
        $location     = $locationData[0];

        if (isset($locationData[1]) && $locationData[1]) {
            $distance = $locationData[1];
        } else {
            $distance = 0;
        }

        if (isset($locationData[2]) && $locationData[2]) {
            $startDistance = $locationData[2];
        } else {
            $startDistance = 0;
        }

        if ($distance && $distance > 200 && $distance <= 100000) {
            $location = null;
            $distance = null;
        }

        if ($location && $location != LocationRepository::COUNTY_ID) {
            $postCode    = $this->getEntityManager()->getRepository('FaEntityBundle:Postcode')->getPostCodInfoArrayByLocation($location, $this->getContainer());
            $town        = null;
            $latitude    = null;
            $longitude   = null;
            $townId      = null;
            $countyId    = null;
            $locality    = null;
            $localityId  = null;
            $lvl 		 = null;
            if (!count($postCode) || $postCode['town_id'] == null || $postCode['town_id'] == 0) {
                if (preg_match('/^\d+$/', $location)) {
                    $town = $this->getEntityManager()->getRepository('FaEntityBundle:Location')->getTownInfoArrayById($location, $this->getContainer());
                } elseif (preg_match('/^([\d]+,[\d]+)$/', $location)) {
                    $localityTown = explode(',', $location);
                    $localityId = $localityTown[0];
                    $townId     = $localityTown[1];
                    if ($localityId && $townId) {
                        $locality  = $this->getEntityManager()->getRepository('FaEntityBundle:Locality')->getLocalityInfoArrayById($localityId, $this->getContainer());
                        $town = $this->getEntityManager()->getRepository('FaEntityBundle:Location')->getTownInfoArrayById($townId, $this->getContainer());
                    }
                } else {
                    $town = $this->getEntityManager()->getRepository('FaEntityBundle:Location')->getTownInfoArrayById($location, $this->getContainer(), 'name');
                }
            }
            
            if (!$town) {
                if (preg_match('/^\d+$/', $location)) {
                    $county = $this->getEntityManager()->getRepository('FaEntityBundle:Location')->getCountyInfoArrayById($location, $this->getContainer());
                } else {
                    $county = $this->getEntityManager()->getRepository('FaEntityBundle:Location')->getCountyInfoArrayById($location, $this->getContainer(), 'name');
                }

                if (!$county) {
                    $localityObj = $this->getEntityManager()->getRepository('FaEntityBundle:Locality')->findOneBy(array('name' => $location));
                    if (!$localityObj) {
                        $localityObj = $this->getEntityManager()->getRepository('FaEntityBundle:Locality')->findOneBy(array('url' => $location));
                    }

                    if ($localityObj) {
                        $locality  = $this->getEntityManager()->getRepository('FaEntityBundle:Locality')->getLocalityInfoArrayById($localityObj->getId(), $this->getContainer());
                        $town      = $this->getEntityManager()->getRepository('FaEntityBundle:Location')->getTownInfoArrayById($locality['town_id'], $this->getContainer());
                    }
                }
            }
        
            if (count($postCode)) {
                $latitude  = $postCode['latitude'];
                $longitude = $postCode['longitude'];
                $townId    = $postCode['town_id'];
            } elseif ($locality && is_array($locality) && count($locality)) {
                $latitude   = $locality['latitude'];
                $longitude  = $locality['longitude'];
                $localityId = $locality['locality_id'];
            } elseif ($town && is_array($town) && count($town)) {
                $latitude  = $town['latitude'];
                $longitude = $town['longitude'];
                $townId    = $town['town_id'];
                $lvl	   = $town['lvl'];
            } elseif ($county && is_array($county) && count($county)) {
                $latitude  = $county['latitude'];
                $longitude = $county['longitude'];
                $countyId  = $county['county_id'];
            }

            if ($latitude && $longitude && $distance) {
                $pt = $latitude.','.$longitude;
                $d  = ($distance * 1.60934); // convert milesto km

                //$this->query .= ' AND {!bbox pt='.$pt.' sfield=store d='.$d.'}';
                if($startDistance) {
                    $sd  = ($startDistance * 1.60934);
                    $this->geoDistQuery['fq'] = '{!frange l='.$sd.' u='.$d.'}geodist()';
                } elseif(isset($locationData[2]) && $locationData[2]==0) {
                    $sd  = 0.81;
                    $this->geoDistQuery['fq'] = '{!frange l='.$sd.' u='.$d.'}geodist()';
                } else {
                    $this->geoDistQuery['fq'] = '{!frange l=0 u='.$d.'}geodist()';
                }
            } elseif ($latitude && $longitude && (isset($locationData[1]) && $locationData[1]==0 && $locationData[1]!='')) {
                $pt = $latitude.','.$longitude;
                $d  = 0.8000; // convert miles to km
                
                if($startDistance) {
                    $sd  = ($startDistance * 1.60934);
                    $this->geoDistQuery['fq'] = '{!frange l='.$sd.' u='.$d.'}geodist()';
                } elseif(isset($locationData[2]) && $locationData[2]==0) {
                    $sd  = 0.81;
                    $this->geoDistQuery['fq'] = '{!frange l='.$sd.' u='.$d.'}geodist()';
                } else {
                    $this->geoDistQuery['fq'] = '{!frange l=0 u='.$d.'}geodist()';
                }
            } elseif ($localityId) {
                $this->addLocalityIdFilter($localityId);
            } elseif ($townId && $lvl != '4') {
                $this->addTownIdFilter($townId);
            } elseif ($townId && $lvl == '4') {
                $this->addAreaIdFilter($townId);
            } elseif ($countyId) {
                $this->addCountyIdFilter($countyId);
            }
            
            if ($latitude && $longitude) {
                $this->geoDistQuery['sfield'] = 'store';
                $this->geoDistQuery['pt'] = $latitude.','.$longitude;
            }
        }
    }

    /**
     * Add is trage ad filter to solr query.
     *
     * @param boolean $isTradeAd Ad is posted by seller or dealer
     */
    protected function addIsTradeAdFilter($isTradeAd = 1)
    {
        if (defined($this->getSolrFieldMappingClass().'::IS_TRADE_AD')) {
            $query        = constant($this->getSolrFieldMappingClass().'::IS_TRADE_AD').':'.$isTradeAd;
            $this->query .= ' AND ('.$query.')';
        }
    }

    /**
     * Add is trage ad filter to solr query.
     *
     * @param boolean $isAffiliateAd Ad is posted by seller or dealer
     */
    protected function addIsAffiliateAdFilter($isAffiliateAd = 1)
    {
        if (defined($this->getSolrFieldMappingClass().'::IS_AFFILIATE_AD')) {
            $query        = constant($this->getSolrFieldMappingClass().'::IS_AFFILIATE_AD').':'.$isAffiliateAd;
            $this->query .= ' AND ('.$query.')';
        }
    }

    /**
     * Add total images count filter to existing solr query.
     *
     * @param string $fromTo
     */
    protected function addTotalImagesFromToFilter($fromTo = null)
    {
        list($from, $to) = explode('|', $fromTo);

        if (!is_numeric($from)) {
            $from = null;
        }

        if (!is_numeric($to)) {
            $to = null;
        }

        $this->addFromToFilter('total_images', $from, $to);
    }

    /**
     * Add is feed ad filter to solr query.
     *
     * @param boolean $isFeeDAd Ad is posted by seller or dealer
     */
    protected function addIsFeedAdFilter($isFeedAd = 1)
    {
        if (defined($this->getSolrFieldMappingClass().'::IS_FEED_AD')) {
            $query        = constant($this->getSolrFieldMappingClass().'::IS_FEED_AD').':'.$isFeedAd;
            $this->query .= ' AND ('.$query.')';
        }
    }

    /**
     * Add updated_at_from_to filter to existing solr query.
     *
     * @param string $fromTo
     */
    protected function addUpdatedAtFromToFilter($fromTo = null)
    {
        list($from, $to) = explode('|', $fromTo);

        $from = preg_replace("/[,\s]/", '', $from);
        $to   = preg_replace("/[,\s]/", '', $to);

        if (!is_numeric($from)) {
            $from = null;
        }

        if (!is_numeric($to)) {
            $to = null;
        }

        $this->addFromToFilter('updated_at', $from, $to);
    }

    /**
     * Add category level filter to solr query.
     *
     * @param integer $level category level.
     */
    protected function addCategoryLevelFilter($level = null)
    {
        if ($level) {
            if (!is_array($level)) {
                $level = array($level);
            }

            if (defined($this->getSolrFieldMappingClass().'::CATEGORY_LEVEL')) {
                $level = array_filter($level);
                if (count($level)) {
                    $query = constant($this->getSolrFieldMappingClass().'::CATEGORY_LEVEL').':'.implode(' OR '.constant($this->getSolrFieldMappingClass().'::CATEGORY_LEVEL').':', $level);
                    $query = ' AND ('.$query.')';

                    $this->query .= $query;
                }
            }
        }
    }

    /**
     * Add category id filter to solr query.
     *
     * @param integer $id Category id.
     */
    protected function addAdUserBusinessCategoryIdFilter($id = null)
    {
        if ($id) {
            if (!is_array($id)) {
                $id = array($id);
            }

            if (defined($this->getSolrFieldMappingClass().'::AD_USER_BUSINESS_CATEGORY_ID')) {
                $id = array_filter($id);
                if (count($id)) {
                    $query = constant($this->getSolrFieldMappingClass().'::AD_USER_BUSINESS_CATEGORY_ID').':'.implode(' OR '.constant($this->getSolrFieldMappingClass().'::AD_USER_BUSINESS_CATEGORY_ID').':', $id);
                    $query = ' AND ('.$query.')';

                    $this->query .= $query;
                }
            }
        }
    }

    /**
     * Add image count filter.
     *
     * @param integer $id Ad status id.
     */
    protected function addImageCountFilter($fromTo = null)
    {
        list($from, $to) = explode('|', $fromTo);

        $from = preg_replace("/[,\s]/", '', $from);
        $to   = preg_replace("/[,\s]/", '', $to);

        if (!is_numeric($from)) {
            $from = null;
        }

        if (!is_numeric($to)) {
            $to = null;
        }

        $this->addFromToFilter('total_images', $from, $to);
    }
}
