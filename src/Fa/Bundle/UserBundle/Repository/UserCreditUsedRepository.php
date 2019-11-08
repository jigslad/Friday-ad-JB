<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Fa\Bundle\UserBundle\Entity\UserCreditUsed;

/**
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserCreditUsedRepository extends EntityRepository
{
    const ALIAS = 'ucu';

    /**
     * Prepare query builder.
     *
     * @param array $data Array of data.
     *
     * @return Doctrine\ORM\QueryBuilder The query builder
     */
    public function getBaseQueryBuilder()
    {
        return $this->createQueryBuilder(self::ALIAS);
    }

    /**
     * Get credit used by user credit ids.
     *
     * @param array $userCreditIds User credit ids array.
     *
     * @return array
     */
    public function getCreditUsedByUserCreditIds($userCreditIds)
    {
        $query = $this->createQueryBuilder(self::ALIAS)
            ->select(UserCreditRepository::ALIAS.'.id as user_credit_id, COUNT('.self::ALIAS.'.id) as cnt')
            ->innerJoin(self::ALIAS.'.user_credit', UserCreditRepository::ALIAS, 'WITH', self::ALIAS.'.user_credit = '.UserCreditRepository::ALIAS.'.id');

        if (!is_array($userCreditIds)) {
            $userCreditIds = array($userCreditIds);
        }

        if (count($userCreditIds)) {
            $query->andWhere(self::ALIAS.'.user_credit IN (:userCreditIds)');
            $query->setParameter('userCreditIds', $userCreditIds);
        }

        $objResources = $query->getQuery()->getArrayResult();
        $userCreditCountArr = array();
        if (count($objResources)) {
            for ($i=0; $i<count($objResources); $i++) {
                $userCreditCountArr[$objResources[$i]['user_credit_id']] = $objResources[$i]['cnt'];
            }
        }

        return $userCreditCountArr;
    }

    /**
     * Add credit for user based on packge perchase
     *
     * @param object $user    User object.
     * @param object $package Package object.
     */
    public function addUserCreditUsed($transactionObj, $paymentId)
    {
        $value = unserialize($transactionObj->getValue());

        $userCredit = $this->_em->getRepository('FaUserBundle:UserCredit')->findOneBy(array('id' => $value['user_credit_id']));
        $userCredit->setCredit($userCredit->getCredit() - 1);
        $this->_em->persist($userCredit);
        $this->_em->flush($userCredit);

        $userCreditUsed = new UserCreditUsed();
        $userCreditUsed->setUser($transactionObj->getUser());
        $userCreditUsed->setUserCredit($userCredit);
        $userCreditUsed->setCredit($value['user_credit']);
        $userCreditUsed->setPayment($this->_em->getReference('FaPaymentBundle:Payment', $paymentId));
        $userCreditUsed->setAd($transactionObj->getAd());
        $adPackageId = null;
        foreach ($value['package'] as $packageId => $packageDetail) {
            $adPackageId = $packageId;
        }
        if ($adPackageId) {
            $userCreditUsed->setPackage($this->_em->getReference('FaPromotionBundle:Package', $packageId));
        }
        $this->_em->persist($userCreditUsed);
        $this->_em->flush($userCreditUsed);
    }
    
    public function addCreditUsedByUpsell($userId,$adObj,$upsellObj) {
        $userFeaturedCredits = $this->_em->getRepository('FaUserBundle:UserCredit')->getActiveFeaturedCreditForUser($userId);
        $userObj = $this->_em->getRepository('FaUserBundle:User')->find($userId);        
        
        $userFeaturedCredits->setCredit($userFeaturedCredits->getCredit() - 1);
        $this->_em->persist($userFeaturedCredits);
        $this->_em->flush($userFeaturedCredits);
        
        $userCreditUsed = new UserCreditUsed();
        $userCreditUsed->setUser($userObj);
        $userCreditUsed->setUserCredit($userFeaturedCredits);
        $userCreditUsed->setCredit(1);
        $userCreditUsed->setAd($adObj);
        $userCreditUsed->setUpsell($upsellObj);
        $this->_em->persist($userCreditUsed);
        $this->_em->flush($userCreditUsed);        
    }
    
    public function redeemCreditUsedByUpsell($userId,$adId,$upsellId,$container) {
        $userFeaturedCredits = $this->_em->getRepository('FaUserBundle:UserCredit')->getActiveFeaturedCreditForUser($userId);
        $userObj = $this->_em->getRepository('FaUserBundle:User')->find($userId);
        
        $userFeaturedCredits->setCredit($userFeaturedCredits->getCredit() + 1);
        $this->_em->persist($userFeaturedCredits);
        $this->_em->flush($userFeaturedCredits);
        
        $userCreditUsed = $this->_em->getRepository('FaUserBundle:UserCreditUsed')->findOneBy(array('user' => $userId, 'user_credit' => $userFeaturedCredits->getId(), 'upsell' => $upsellId, 'ad' => $adId));
        
        if(!empty($userCreditUsed)) {
            $this->createQueryBuilder(self::ALIAS)
            ->delete()
            ->andWhere(sprintf('%s.user_credit = %d', self::ALIAS, $userFeaturedCredits->getId()))
            ->andWhere(sprintf('%s.upsell = %d', self::ALIAS, $upsellId))
            ->andWhere(sprintf('%s.ad = %d', self::ALIAS, $adId))
            ->getQuery()
            ->execute();
        }
    }
}
