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
use Fa\Bundle\UserBundle\Entity\User;
use Fa\Bundle\PromotionBundle\Entity\Package;
use Fa\Bundle\UserBundle\Entity\UserPackage;
use Fa\Bundle\PromotionBundle\Repository\PackageRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\PaymentBundle\Repository\PaymentRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\AdBundle\Solr\AdSolrFieldMapping;

/**
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserPackageRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'up';

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
     * Get package table name.
     *
     * @return Ambigous <string, multitype:>
     */
    private function getUserPackageTableName()
    {
        return $this->_em->getClassMetadata('FaUserBundle:UserPackage')->getTableName();
    }

    /**
     * Assign free shop package to user for it selected category.
     *
     * @param User    $user      User object
     * @param string  $remark    remark
     * @param object  $container Container object.
     * @param boolean $ad_update update solr index for ad
     */
    public function assignFreePackageToUser(User $user, $remark = null, $container = null, $ad_update = true)
    {
        $package = $this->_em->getRepository('FaPromotionBundle:Package')->getFreeShopPackageByCategory($user->getBusinessCategoryId());
        if ($package) {
            $this->assignPackageToUser($user, $package, $remark, null, false, $container, $ad_update);
        }
    }

    /**
     * Assign package to user.
     *
     * @param User    $user      User object
     * @param Package $package   Package object
     * @param string  $remark    Remark
     * @param integer $paymentId Payment id.
     * @param boolean $expired   Is expired flag.
     * @param object  $container Container object.
     */
    public function assignPackageToUser(User $user, Package $package, $remark = null, $paymentId = null, $expired = false, $container = null, $ad_update = true)
    {
        $userPackage = $this->checkAlreadyAssignSamePackage($user, $package);

        if (!$userPackage) {
            $this->closeActivePackage($user);

            $userPackage = new UserPackage();
            $userPackage->setUser($user);
            $userPackage->setPackage($package);
            $userPackage->setStatus('A');
            $userPackage->setCreatedAt(time());

            if ($paymentId != null) {
                $userPackage->setPayment($this->getEntityManager()->getReference('FaPaymentBundle:Payment', $paymentId));
            }

            if ($package->getPrice() == 0 || $expired) {
                $userPackage->setExpiresAt(null);
            } else {
                $userPackage->setExpiresAt(CommonManager::getTimeFromDuration($package->getDuration(), time(), '+'));
            }
            if ($expired==1) {
                $userPackage->setIsAutoRenew(1);
            } else {
                $userPackage->setIsAutoRenew(0);
            }
            if ($user->getFreeTrialEnable() && $package->getTrail()) {
                $user->setFreeTrialEnable(0);
                $userPackage->setTrial(1);
            }

            if ($package->getPrice() > 0 && $user->getFreeTrialEnable()) {
                $user->setFreeTrialEnable(0);
            }

            $userPackage->setRemark($remark);

            $user->setBusinessCategoryId($package->getShopCategory()->getId());
            $this->_em->persist($userPackage);
            $this->_em->persist($user);
            $this->_em->flush();

            $this->_em->getRepository('FaUserBundle:UserUpsell')->setPackageUpsellForUser($user, $package);
        } else {
            if ($package->getPrice() == 0 || $expired) {
                $userPackage->setExpiresAt(null);
            } else {
                if ($userPackage->getExpiresAt() > time()) {
                    $userPackage->setExpiresAt(CommonManager::getTimeFromDuration($package->getDuration(), $userPackage->getExpiresAt(), '+'));
                } else {
                    $userPackage->setExpiresAt(CommonManager::getTimeFromDuration($package->getDuration(), time(), '+'));
                }
            }
            
            if ($expired==1) {
                $userPackage->setIsAutoRenew(1);
            } else {
                $userPackage->setIsAutoRenew(0);
            }
            
            if ($remark == 'package-renew-thourgh-recurring') {
                $userPackage->setRenewedAt(time());
                $userPackage->setIsRenewalMailSent(1);
            }
            
            $userPackage->setUpdatedAt(time());
            $userPackage->setBoostOveride(null);
            $userPackage->setRemark($remark);
            $this->_em->persist($userPackage);
            $this->_em->flush();
        }
        

        if ($user && $user->getBusinessCategoryId() && $container && $ad_update) {
            if (in_array($user->getBusinessCategoryId(), array(CategoryRepository::ADULT_ID, CategoryRepository::SERVICES_ID))) {
                exec('nohup'.' '.$container->getParameter('fa.php.path').' '.$container->getParameter('project_path').'/console fa:update:user-shop-detail-solr-index --id='.$user->getId().' delete >/dev/null &');
                exec('nohup'.' '.$container->getParameter('fa.php.path').' '.$container->getParameter('project_path').'/console fa:update:user-shop-detail-solr-index --id='.$user->getId().' >/dev/null &');
            } elseif (!in_array($user->getBusinessCategoryId(), array(CategoryRepository::ADULT_ID, CategoryRepository::SERVICES_ID))) {
                exec('nohup'.' '.$container->getParameter('fa.php.path').' '.$container->getParameter('project_path').'/console fa:update:user-ad-shop-detail --user_id='.$user->getId().' >/dev/null &');
            }
            if ($user->getBusinessCategoryId() == CategoryRepository::JOBS_ID) {
                $culture = CommonManager::getCurrentCulture($container);
                CommonManager::removeCache($container, 'user_upsell|getUserArrayWithFeaturedEmployerUpsell|'.$culture);
            }
            if ($user->getBusinessCategoryId() == CategoryRepository::FOR_SALE_ID) {
                $culture = CommonManager::getCurrentCulture($container);
                CommonManager::removeCache($container, 'user_upsell|getUserArrayWithPopularShopUpsell|'.$culture);

                if ($package && $package->getPrice() > 0) {
                    CommonManager::removeCache($container, 'user_package|getPaidUserIdsByCategoryId|'.$user->getBusinessCategoryId().'_'.$culture);
                }
            }
            if ($user->getBusinessCategoryId() == CategoryRepository::ADULT_ID) {
                $culture = CommonManager::getCurrentCulture($container);
                CommonManager::removeCache($container, 'user_upsell|getUserArrayWithFeaturedAdultBusinessUpsell|'.$culture);
            }
        }

        //assign credits to user if package has
        if ($package && $user) {
            $this->_em->getRepository('FaAdBundle:BoostedAd')->unboostAdByUserId($user->getId());
            $this->_em->getRepository('FaUserBundle:UserCredit')->addUserCredit($user, $package);
        }

        return $userPackage;
    }

    /**
     * Close active package.
     *
     * @param User $user
     * @param Package $package
     */
    public function closeActivePackage(User $user)
    {
        $qb = $this->createQueryBuilder(self::ALIAS)
        ->andWhere(self::ALIAS.'.user = :user')
        ->andWhere(self::ALIAS.'.status = :status')
        ->setParameter('user', $user->getId())
        ->setParameter('status', 'A');

        $userPackages = $qb->getQuery()->getResult();

        foreach ($userPackages as $userPackage) {
            $userPackage->setStatus('C');
            $userPackage->setClosedAt(time());
            $userPackage->setUpdatedAt(time());
            $userPackage->setBoostOveride('0');
            $this->_em->persist($userPackage);
            $this->_em->flush();
        }
    }

    public function checkAlreadyAssignSamePackage(User $user, Package $package)
    {
        $userPackage = $this->findOneBy(array('package' => $package, 'user' => $user, 'status' => 'A'));

        if ($userPackage) {
            return $userPackage;
        }

        return null;
    }

    /**
     * Renew package.
     *
     * @param User $user
     * @param Package $package
     */
    public function renewPackage(User $user, Package $package)
    {
    }

    /**
     * Get current active package.
     *
     * @param User $user
     *
     * @return Ambigous <object, NULL>
     */
    public function getCurrentActivePackage(User $user)
    {
        //return $this->findOneBy(array('user' => $user, 'status' => 'A'), array('id' => 'DESC'));
        $qb = $this->createQueryBuilder(self::ALIAS)
            ->select(self::ALIAS, PackageRepository::ALIAS)
            ->leftJoin(self::ALIAS.'.package', PackageRepository::ALIAS)
            ->andWhere(self::ALIAS.'.user = :user')
            ->andWhere(self::ALIAS.'.status = :status')
            ->setParameter('user', $user->getId())
            ->setParameter('status', 'A')
            ->orderBy(self::ALIAS.'.id', 'DESC')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }


    /**
     * send billing receipt email
     *
     * @param User $user       user object
     * @param Package $package payment object
     * @param string $emailType email type
     */
    public function sendUserPackageBillingEmail(User $user, Package $package, UserPackage $userPackage, $cartCode, $subscriptionId, $emailType, $container)
    {
        $entityCache  = $container->get('fa.entity.cache.manager');
        $dashboardURL = $container->get('router')->generate('dashboard_home', array(), true);

        $text_billing_date             = CommonManager::formatDate(time(), $container);
        $text_next_billing_date        = CommonManager::formatDate($this->getNextPaymentDueDateFromExpiresAt($userPackage->getExpiresAt(), $container), $container);
        $user_site                     = $this->_em->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $user));
        $company_address               = $user_site ? $user_site->getCompanyAddress() : null;
        $text_package_cost_with_vat    = CommonManager::formatCurrency($package->getPrice(), $container);
        $text_package_cost_without_vat = CommonManager::formatCurrency(($package->getPrice()/(1+(20/100))), $container);
        $text_package_cost_vat         = CommonManager::formatCurrency(($package->getPrice() - ($package->getPrice()/(1+(20/100)))), $container);
        $token                         = $this->_em->getRepository('FaPaymentBundle:PaymentTokenization')->getTokenBySubscriptionId($user->getId(), $subscriptionId);
        $text_payment_source           = $token ? '***'.$token->getCardNumber().' '.$token->getCardHolderName() : PaymentRepository::PAYMENT_METHOD_CYBERSOURCE;

        $parameters = array(
                'user_first_name'               => $user->getFirstName(),
                'user_last_name'                => $user->getLastName(),
                'text_package_name'             => $package->getTitle(),
                'business_name'                 => $user->getBusinessName(),
                'business_category'             => $entityCache->getEntityNameById('FaEntityBundle:Category', $user->getBusinessCategoryId()),
                'url_account_dashboard'         => $dashboardURL,
                'text_billing_date'             => $text_billing_date,
                'text_next_billing_date'        => $text_next_billing_date,
                'text_transaction_id'           => $cartCode,
                'text_payment_source'           => $text_payment_source,
                'text_business_address'         => $company_address,
                'text_package_cost_without_vat' => $text_package_cost_without_vat,
                'text_package_cost_with_vat'    => $text_package_cost_with_vat,
                'text_package_cost_vat'         => $text_package_cost_vat
        );

        if ($emailType != 'upgraded_to_profile_package_welcome') {
            $parameters['text_package_price'] = CommonManager::formatCurrency($package->getPrice(), $container);
        }

        $container->get('fa.mail.manager')->send($user->getEmail(), $emailType, $parameters, CommonManager::getCurrentCulture($container));
    }

    /**
     * send billing receipt email
     *
     * @param User $user       user object
     * @param Package $package payment object
     * @param string $emailType email type
     */
    public function sendUserPackageEmail(User $user, Package $package, $emailType, $container, $subscriptionId = null)
    {
        $dashboardURL = $container->get('router')->generate('dashboard_home', array(), true);
        $entityCache = $container->get('fa.entity.cache.manager');
        $text_billing_date = CommonManager::formatDate(time(), $container);
        $token                         = $this->_em->getRepository('FaPaymentBundle:PaymentTokenization')->getTokenBySubscriptionId($user->getId(), $subscriptionId);
        $text_payment_source           = $token ? '***'.$token->getCardNumber().' '.$token->getCardHolderName() : PaymentRepository::PAYMENT_METHOD_CYBERSOURCE;

        $parameters = array(
                'user_first_name' => $user->getFirstName(),
                'user_last_name' => $user->getLastName(),
                'text_package_name' => $package->getTitle(),
                'business_name' => $user->getBusinessName(),
                'text_payment_source' => $text_payment_source,
                'text_billing_date'  => $text_billing_date,
                'business_category' => $entityCache->getEntityNameById('FaEntityBundle:Category', $user->getBusinessCategoryId()),
                'url_account_dashboard' => $dashboardURL,
        );

        if ($emailType != 'upgraded_to_profile_package_welcome') {
            $parameters['text_package_price'] = CommonManager::formatCurrency($package->getPrice(), $container);
        }

        $container->get('fa.mail.manager')->send($user->getEmail(), $emailType, $parameters, CommonManager::getCurrentCulture($container));
    }

    /**
     * Get current active package detail.
     *
     * @param integer $userId User id.
     *
     * @return object
     */
    public function getCurrentActivePackageDetail($userId)
    {
        $qb = $this->createQueryBuilder(self::ALIAS)
            ->select(self::ALIAS, 'u'.PackageRepository::ALIAS.'d', PaymentRepository::ALIAS)
            ->leftJoin(self::ALIAS.'.package', 'u'.PackageRepository::ALIAS.'d')
            ->leftJoin(self::ALIAS.'.payment', PaymentRepository::ALIAS)
        ->andWhere(self::ALIAS.'.user = :user')
        ->andWhere(self::ALIAS.'.status = :status')
        ->setParameter('user', $userId)
        ->setParameter('status', 'A')
        ->orderBy(self::ALIAS.'.id', 'DESC')
        ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Get next payment due date.
     *
     * @param integer $expiresAt
     * @param object $container
     *
     * @return integer
     */
    public function getNextPaymentDueDateFromExpiresAt($expiresAt, $container)
    {
        $day = date('d', $expiresAt);
        if ($day <= 27) {
            return strtotime('-1 day', $expiresAt);
        } elseif ($day > 27) {
            $date = date('Y-m', $expiresAt).'-27';
            return strtotime($date);
        }
    }

    /**
     * Get shop user detail by user ids.
     *
     * @param array $userId USer id array.
     *
     * @return array
     */
    public function getShopPackageDetailByUserIdForAdReport($userId = array())
    {
        if (!is_array($userId)) {
            $userId = array($userId);
        }

        $qb = $this->createQueryBuilder(self::ALIAS)
        ->select('IDENTITY('.self::ALIAS.'.user) as user_id', PackageRepository::ALIAS.'.id as package_id', PackageRepository::ALIAS.'.package_sr_no', PackageRepository::ALIAS.'.price', PackageRepository::ALIAS.'.package_text')
        ->leftJoin(self::ALIAS.'.package', PackageRepository::ALIAS)
        ->andWhere(self::ALIAS.'.user IN (:userId)')
        ->andWhere(self::ALIAS.'.status = :status')
        ->setParameter('userId', $userId)
        ->setParameter('status', 'A');

        $userPackages    = $qb->getQuery()->getArrayResult();
        $userPackageArr = array();
        if (count($userPackages)) {
            foreach ($userPackages as $userPackage) {
                $userPackageArr[$userPackage['user_id']] = array(
                    'package_id' => $userPackage['package_id'],
                    'package_sr_no' => $userPackage['package_sr_no'],
                    'package_text' => $userPackage['package_text'],
                    'price' => $userPackage['price'],
                );
            }
        }

        return $userPackageArr;
    }

    /**
     * Get paid shop user ids array.
     *
     * @param integer $rootCategoryId Category id.
     * @param object  $container      Container object.
     *
     * @return array
     */
    public function getPaidUserIdsByCategoryId($rootCategoryId, $container)
    {
        if ($container) {
            $culture     = CommonManager::getCurrentCulture($container);
            $tableName   = $this->getUserPackageTableName();
            $cacheKey    = $tableName.'|'.__FUNCTION__.'|'.$rootCategoryId.'_'.$culture;
            $cachedValue = CommonManager::getCacheVersion($container, $cacheKey);

            if ($cachedValue !== false) {
                return $cachedValue;
            }
        }

        $paidPackageIdsArray = array_keys($this->_em->getRepository('FaPromotionBundle:Package')->getPaidShopPackagesByCategory($rootCategoryId, $container));
        $paidUserIdsArray = array();

        if (count($paidPackageIdsArray)) {
            $qb = $this->createQueryBuilder(self::ALIAS)
            ->select('IDENTITY('.self::ALIAS.'.user) as user_id')
            ->innerJoin(self::ALIAS.'.package', PackageRepository::ALIAS)
            ->andWhere(self::ALIAS.'.status = :status')
            ->setParameter('status', 'A')
            ->andWhere(PackageRepository::ALIAS.'.id IN (:packageIds)')
            ->setParameter('packageIds', $paidPackageIdsArray);

            $paidUserPackages = $qb->getQuery()->getResult();

            foreach ($paidUserPackages as $paidUserPackage) {
                $paidUserIdsArray[] = $paidUserPackage['user_id'];
            }
        }

        if (count($paidUserIdsArray)) {
            $paidUserIdsArray = array_unique($paidUserIdsArray);
        }

        if ($container) {
            CommonManager::setCacheVersion($container, $cacheKey, $paidUserIdsArray);
        }

        return $paidUserIdsArray;
    }

    /**
     * Get paid shop user ids array.
     *
     * @param integer $categoryId Category id.
     * @param object  $container  Container object.
     *
     * @return array
     */
    public function getRelatedBusinesses($categoryId, $container, $limit = 4)
    {
        $relatedBusinessesIds = array();
        $relatedBusinesses = array();
        $parentCategories = array_keys($this->_em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId, false, $container));
        $rootCategoryId = (isset($parentCategories[0]) ? $parentCategories[0] : null);
        $parentCategories = array_reverse($parentCategories);
        $paidUserIdsArray = $this->getPaidUserIdsByCategoryId($rootCategoryId, $container);

        if (count($paidUserIdsArray)) {
            foreach ($parentCategories as $parentCategory) {
                if (count($relatedBusinesses) < $limit) {
                    $searchParams['item__user_id'] = $paidUserIdsArray;
                    $searchParams['item__category_id'] = $parentCategory;
                    $paidRelatedBusinesses = $this->getRelatedBusinessesByAd($container, $searchParams, (count($relatedBusinessesIds) ? ' AND -'.AdSolrFieldMapping::USER_ID.': ("'.implode('" "', $relatedBusinessesIds).'")' : null), true);
                    if (count($paidRelatedBusinesses) && count($relatedBusinesses) < $limit) {
                        shuffle($paidRelatedBusinesses);
                        for ($i = 0; $i < $limit; $i++) {
                            if (isset($paidRelatedBusinesses[$i])) {
                                $relatedBusinesses[] = $paidRelatedBusinesses[$i];
                                $relatedBusinessesIds[] = $paidRelatedBusinesses[$i]['id'];
                                if (count($relatedBusinessesIds) > $limit) {
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }

        $totalRelatedBusinessesCount = count($relatedBusinesses);
        if ($totalRelatedBusinessesCount < $limit) {
            foreach ($parentCategories as $parentCategory) {
                if (count($relatedBusinesses) < $limit) {
                    $searchParams['item__category_id'] = $parentCategory;
                    $unPaidRelatedBusinesses = $this->getRelatedBusinessesByAd($container, $searchParams, (count($relatedBusinessesIds) ? ' AND -'.AdSolrFieldMapping::USER_ID.': ("'.implode('" "', $relatedBusinessesIds).'")' : null), true);
                    if (count($unPaidRelatedBusinesses) && count($relatedBusinesses) < $limit) {
                        shuffle($unPaidRelatedBusinesses);
                        for ($i = 0; $i < ($limit - $totalRelatedBusinessesCount); $i++) {
                            if (isset($unPaidRelatedBusinesses[$i])) {
                                $relatedBusinesses[$i+$totalRelatedBusinessesCount] = $unPaidRelatedBusinesses[$i];
                            }
                            if (count($relatedBusinessesIds) > $limit) {
                                break;
                            }
                        }
                    }
                }
            }
        }

        $relatedBusinesses = array_slice($relatedBusinesses, 0, $limit, true);
        shuffle($relatedBusinesses);

        return $relatedBusinesses;
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
    public function getRelatedBusinessesByAd($container, $searchParams, $staticFilters = null, $randomSort = false)
    {
        $data           = array();
        $data['search'] = $searchParams;
        $data['search']['item__status_id'] = \Fa\Bundle\EntityBundle\Repository\EntityRepository::AD_STATUS_LIVE_ID;

        $container->get('fa.searchfilters.manager')->init($this->_em->getRepository('FaAdBundle:Ad'), $this->_em->getClassMetadata('FaAdBundle:Ad'), 'search', $data);
        $data = $container->get('fa.searchfilters.manager')->getFiltersData();

        $data['query_sorter'] = array();
        if ($randomSort) {
            $data['query_sorter']['item']['random'] = array('sort_ord' => 'desc', 'field_ord' => 1);
        }

        // List no affliate
        $data['query_filters']['item']['is_affiliate_ad'] = 0;
        $data['query_filters']['item']['is_trade_ad'] = 1;

        $data['select_fields']  = array('item' => array('user_id'));
        $data['group_fields'] = array(
            AdSolrFieldMapping::USER_ID => array('limit' => 1),
        );
        if ($staticFilters) {
            $data['static_filters'] = $staticFilters;
        }
        // initialize solr search manager service and fetch data based of above prepared search options
        $solrSearchManager = $container->get('fa.solrsearch.manager');
        $solrSearchManager->init('ad', null, $data, 1, 8, 0, true);
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
                        );
                    }
                }
            }
        }

        return $userDetails;
    }

    /**
     * Add package filter to query object.
     *
     * @param string $email User email.
     */
    protected function addPackageIdFilter($packageId = null)
    {
        $this->queryBuilder->andWhere(self::ALIAS.'.package = '.$packageId);
    }

    /**
     * Get account purchases for police report.
     *
     * @param integer $userId User id integer.
     *
     * @return array
     */
    public function getAccountPurchases($userId)
    {
        $qb = $this->createQueryBuilder(self::ALIAS)
                    ->select(PackageRepository::ALIAS.'.title', PaymentRepository::ALIAS.'1.cart_code', PaymentRepository::ALIAS.'1.amount', self::ALIAS.'.created_at')
                    ->innerJoin(self::ALIAS.'.package', PackageRepository::ALIAS)
                    ->leftJoin(self::ALIAS.'.payment', PaymentRepository::ALIAS.'1')
                    ->where(self::ALIAS.'.user = :userId')
                    ->setParameter('userId', $userId)
                    ->orderBy(self::ALIAS.'.created_at');

        $result = $qb->getQuery()->getResult();

        return $result;
    }

    /**
     * Get is Auto-renew by user id.
     *
     * @param integer $userId User id integer
     *
     * @return boolean
     */
    public function checkIsAutoRenewedPackage($userId)
    {
        $qb = $this->createQueryBuilder(self::ALIAS)->select(self::ALIAS.'.is_auto_renew')->andWhere(self::ALIAS.'.status = :status')->andWhere(self::ALIAS.'.user = :userId')
        ->setParameter('status', 'A')->setParameter('userId', $userId)->setParameter('status', 'A')->setParameter('userId', $userId)->orderBy(self::ALIAS.'.id', 'DESC')
        ->setMaxResults(1);;
        $result = $qb->getQuery()->getOneOrNullResult();
        return $result['is_auto_renew'];
    }

    /**
     * Check User Has Boost Package by userId.
     *
     * @param integer $userId User id integer
     *
     * @return boolean
    */
    public function checkUserHasBoostPackage($userId)
    {
        $qb = $this->createQueryBuilder(self::ALIAS)->select(self::ALIAS.'.id', self::ALIAS.'.boost_overide', PackageRepository::ALIAS.'.monthly_boost_count')->innerJoin(self::ALIAS.'.package', PackageRepository::ALIAS)->andWhere(self::ALIAS.'.status = :status')->andWhere(self::ALIAS.'.user = :userId')->andWhere(PackageRepository::ALIAS.'.boost_ad_enabled = :boost_ad_enabled')->andWhere(PackageRepository::ALIAS.'.monthly_boost_count IS NOT NULL')->andWhere(PackageRepository::ALIAS.'.price > 0')->andWhere(PackageRepository::ALIAS.'.price IS NOT NULL')->setParameter('status', 'A')->setParameter('userId', $userId)->setParameter('boost_ad_enabled', '1');

        $result = $qb->getQuery()->getResult();
        return $result;
    }
}
