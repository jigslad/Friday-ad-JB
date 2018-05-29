<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Search;

use Fa\Bundle\CoreBundle\Search\SolrSearch;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;

/**
 * This manager is used to add filters, join and sorting for user entity.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserShopDetailSolrSearch extends SolrSearch
{
    /**
     * Get current repository.
     *
     * @return object
     */
    public function getTableName()
    {
        return 'user_shop_detail';
    }

    /**
     * Add user id filter to existing query object.
     *
     * @param integer $id User id.
     */
    protected function addIdFilter($id = null)
    {
        if (!is_array($id)) {
            $id = array($id);
        }
        $tmpUseIds = array();
        foreach ($id as $userId) {
            $tmpUseIds[] = '"'.$userId.'"';
        }
        $userId = $tmpUseIds;

        if (defined($this->getSolrFieldMappingClass().'::ID')) {
            $query = constant($this->getSolrFieldMappingClass().'::ID').':'.implode('" OR "'.constant($this->getSolrFieldMappingClass().'::ID').':', $userId);
            $query = ' AND ('.$query.')';

            $this->query .= $query;
        }
    }

    /**
     * Add profile exposure category id filter to solr query.
     *
     * @param integer $id Profile exposure category id.
     */
    protected function addProfileExposureCategoryIdFilter($id = null)
    {
        if ($id) {
            if (!is_array($id)) {
                $id = array($id);
            }

            if (defined($this->getSolrFieldMappingClass().'::PROFILE_EXPOSURE_CATEGORY_ID')) {
                $id = array_filter($id);
                if (count($id)) {
                    $query = constant($this->getSolrFieldMappingClass().'::PROFILE_EXPOSURE_CATEGORY_ID').':('.implode(' OR ', $id).')';

                    // check with all parent category ids
                    $totalLevel = 4;
                    for ($i = 1; $i <= $totalLevel; $i++) {
                        $query .= ' OR u_s_d_parent_profile_exposure_category_lvl_'.$i.'_id_i'.':('.implode(' OR ', $id).')';
                    }

                    $query = ' AND ('.$query.')';
                    $this->query .= $query;
                }
            }
        }
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
            } elseif ($town && is_array($town) && count($town)) {
                $latitude  = $town['latitude'];
                $longitude = $town['longitude'];
                $townId    = $town['town_id'];
            } elseif ($county && is_array($county) && count($county)) {
                $latitude  = $county['latitude'];
                $longitude = $county['longitude'];
                $countyId  = $county['county_id'];
            }

            if ($latitude && $longitude && $distance) {
                $pt = $latitude.','.$longitude;
                $d  = ($distance * 1.60934); // convert milesto km

                $this->query .= ' AND {!bbox pt='.$pt.' sfield=store d='.$d.'}';
            } elseif ($townId) {
                $this->addTownIdFilter($townId);
            } elseif ($countyId) {
                $this->addCountyIdFilter($countyId);
            }

            if ($latitude && $longitude) {
                $this->geoDistQuery['sfield'] = 'store';
                $this->geoDistQuery['pt'] = $latitude.','.$longitude;
            }
        }
    }
}
