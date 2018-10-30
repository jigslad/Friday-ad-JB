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
use Fa\Bundle\AdBundle\Entity\AdIpAddress;

/**
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdIpAddressRepository extends EntityRepository
{
    const ALIAS = 'aip';

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
     * PrepareQueryBuilder.
     *
     * @param object $objAd     Object of Ad.
     * @param string $ipAddress Ip address.
     *
     * @return object.
     */
    public function checkAndLogIpAddress($objAd, $ipAddress)
    {
        $objAdIpAddress = null;
        if (strlen($ipAddress)) {
            $objAdIpAddress = $this->findOneBy(array('ad' => $objAd->getId(), 'ip_address' => $ipAddress));

            if (!$objAdIpAddress) {
                $objAdIpAddress = new AdIpAddress();
                $objAdIpAddress->setAd($objAd);
                $objAdIpAddress->setIpAddress($ipAddress);
                $this->_em->persist($objAdIpAddress);
                $this->_em->flush();
            }
        }

        return $objAdIpAddress;
    }

    /**
     * PrepareQueryBuilder.
     *
     * @param array $adIds Ad ids array.
     *
     * @return array.
     */
    public function getIpAddressesByAdIds($adIds)
    {
        $ipAddressArray = array();

        if (is_array($adIds)) {
            $qb = $this->getBaseQueryBuilder()
                       ->select('IDENTITY('.self::ALIAS.'.ad) As ad_id', "group_concat(".self::ALIAS.".ip_address, ', ') as ip_addresses")
                       ->where(self::ALIAS.'.ad IN (:adIds)')
                       ->setParameter('adIds', $adIds)
                       ->groupBy(self::ALIAS.'.ad');

            $result = $qb->getQuery()->execute();

            if ($result && count($result) > 0) {
                foreach ($result as $key => $valueArray) {
                    $ipAddressArray[$valueArray['ad_id']] = $valueArray['ip_addresses'];
                }
            }
        }

        return $ipAddressArray;
    }

    /**
     * PrepareQueryBuilder.
     *
     * @param array $adIds Ad ids array.
     *
     * @return boolean.
     */
    public function deleteRecordsByAdIds($adIds)
    {
        $result = false;
        if (is_array($adIds)) {
            $qb = $this->getBaseQueryBuilder()
                       ->delete()
                       ->where(self::ALIAS.'.ad IN (:adIds)')
                       ->setParameter('adIds', $adIds);

            $result = $qb->getQuery()->execute();
        }

        return $result;
    }
}
