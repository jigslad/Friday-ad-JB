<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EntityBundle\Repository;

use Doctrine\ORM\EntityRepository as BaseEntityRepository;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class PostcodeRepository extends BaseEntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'pc';
    const CACHE_TTL = 259200; // 3 days

    /**
     * Prepare query builder.
     *
     * @param array $data array of data
     *
     * @return Doctrine\ORM\QueryBuilder The query builder
     */
    public function getBaseQueryBuilder()
    {
        return $this->createQueryBuilder(self::ALIAS);
    }

    /**
     * Get postcode from location.
     *
     * @param string $location postcode or town name
     *
     * @return object
     */
    public function getPostCodByLocation($location = null, $partial_match = false)
    {
        $location = str_replace(' ', '', $location);
        $qb = $this->getBaseQueryBuilder();

        if ($partial_match) {
            $qb->where(self::ALIAS.".post_code_c LIKE :post_code or ".self::ALIAS.".post_code LIKE :post_code");
            $qb->setParameter('post_code', $location.'%');
            $qb->setMaxResults(1);
        } else {
            $qb->where(self::ALIAS.".post_code_c = :post_code or ".self::ALIAS.".post_code = :post_code");
            $qb->setParameter('post_code', $location);
        }

        $postcode = $qb->getQuery()->getOneOrNullResult();

        return $postcode;
    }

    /**
     * Get postcode text by location.
     *
     * @param string  $location  Postcode.
     * @param object  $container Container interface.
     * @param boolean $town_id       return only town id
     * @param boolean $partial_match allow partial match
     *
     * @return string
     */
    public function getPostCodTextByLocation($location, $container = null, $town_id = false, $partial_match = false)
    {
        $location = str_replace(array(' ', '-'), '', $location);
        if ($container) {
            $culture     = CommonManager::getCurrentCulture($container);
            $cacheKey    = $this->getTableName().'|'.__FUNCTION__.'|'.$location.'_'.$town_id.'_'.$partial_match.'_'.$culture;
            $cachedValue = CommonManager::getCacheVersion($container, $cacheKey);

            if ($cachedValue !== false) {
                return $cachedValue;
            }
        }

        $postCodeText = null;
        $qb = $this->getBaseQueryBuilder();

        if ($partial_match) {
            $qb->where(self::ALIAS.".post_code_c LIKE :post_code");
            $qb->setParameter('post_code', $location.'%');
            $qb->setMaxResults(1);
        } else {
            $qb->where(self::ALIAS.".post_code_c = :post_code");
            $qb->setParameter('post_code', $location);
        }

        $postcode = $qb->getQuery()->getOneOrNullResult();

        if ($postcode && $postcode->getPostCodeC()) {
            if ($town_id && $postcode->getLocalityId() && $postcode->getTownId()) {
                $postCodeText = $postcode->getLocalityId().','.$postcode->getTownId();
            } elseif ($town_id && $postcode->getTownId()) {
                $postCodeText = $postcode->getTownId();
            } else {
                $postCodeText = strtolower($postcode->getPostCodeC());
            }
        }

        if ($container) {
            CommonManager::setCacheVersion($container, $cacheKey, $postCodeText, self::CACHE_TTL);
        }

        return $postCodeText;
    }

    /**
     * Get postcode information array by location.
     *
     * @param string $location  Postcode.
     * @param object $container Container interface.
     *
     * @return array
     */
    public function getPostCodInfoArrayByLocation($location, $container = null, $partial_match = false)
    {
        $location = str_replace(' ', '', $location);
        if ($container) {
            $culture     = CommonManager::getCurrentCulture($container);
            $cacheKey    = $this->getTableName().'|'.__FUNCTION__.'|'.$location.'_'.$culture.'_'.$partial_match;
            $cachedValue = CommonManager::getCacheVersion($container, $cacheKey);

            if ($cachedValue !== false) {
                return $cachedValue;
            }
        }

        $postCodeArray = array();
        $postcode = $this->getPostCodByLocation($location, $partial_match);

        if ($postcode) {
            $postCodeArray['locality_id'] = $postcode->getLocalityId();
            $postCodeArray['town_id']     = $postcode->getTownId();
            $postCodeArray['county_id']   = $postcode->getCountyId();
            $postCodeArray['latitude']    = $postcode->getLatitude();
            $postCodeArray['longitude']   = $postcode->getLongitude();
            $postCodeArray['postcode']    = $postcode->getPostCode();
            $postCodeArray['postcode_c']  = $postcode->getPostCodeC();
        }

        if ($container) {
            CommonManager::setCacheVersion($container, $cacheKey, $postCodeArray, self::CACHE_TTL);
        }

        return $postCodeArray;
    }

    /**
     * Get Table Name.
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->_em->getClassMetadata('FaEntityBundle:Postcode')->getTableName();
    }

    /**
     * Get postcode text by latitude and longitude.
     *
     * @param string  $latitude  Lattitude.
     * @param string  $longitude Longitude.
     *
     * @return string
     */
    public function getPostCodTextByLatLong($latitude, $longitude)
    {
        

        //$qd = "SELECT `post_code`, (CASE WHEN (`latitude` - ".$latitude." ) > 0 THEN `latitude` - ".$latitude." ELSE (`latitude` - ".$latitude.") * -1 END) + (CASE WHEN (`longitude` - ".$longitude.") > 0 THEN `longitude` - ".$longitude." ELSE (`longitude` - ".$longitude.") * -1 END) AS latlondiff FROM `postcode` ORDER BY latlondiff ASC LIMIT 0,1";
        $query = $this->_em->createQueryBuilder()->select("pc.post_code")->addSelect('((CASE WHEN (pc.latitude - '.$latitude.' ) > 0 THEN pc.latitude - '.$latitude.' ELSE (pc.latitude - '.$latitude.') * -1 END) + (CASE WHEN (pc.longitude - '.$longitude.') > 0 THEN pc.longitude - '.$longitude.' ELSE (pc.longitude - '.$longitude.') * -1 END)) as latlondiff')
                ->from('FaEntityBundle:Postcode', 'pc')->orderBy('latlondiff', 'ASC')->setMaxResults(1)->setFirstResult(0);
        $results = $query->getQuery()->getResult();
        
        return $results[0]['post_code'];
    }
}
