<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\DotMailerBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\DotMailerBundle\Entity\Dotmailer;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\EntityBundle\Repository\LocationGroupRepository;
use Fa\Bundle\EntityBundle\Repository\LocationGroupLocationRepository;

/**
 * Dotmailer repository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Sagar Lotiya <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class DotmailerRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'dt';

    const TOUCH_POINT_PAA = 'paa';

    const TOUCH_POINT_ENQUIRY = 'enquiry';

    const OPTINTYPE = 'single';
    
    const TOUCH_POINT_CREATE_ALERT = 'create_alert';

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
     * Add opted in filter to existing query object.
     *
     * @param mixed $optIn Opted in.
     */
    protected function addOptInFilter($optIn = 1)
    {
        $this->addWhereInFilter('opt_in', $optIn);
    }

    /**
     * Add is_suppressed in filter to existing query object.
     *
     * @param mixed $isSuppressed Suppressed.
     */
    protected function addIsSuppressedFilter($isSuppressed = 0)
    {
        $this->addWhereInFilter('is_suppressed', $isSuppressed);
    }

    /**
     * Add dotmailer newsletter unsubscribe filter.
     *
     * @param mixed $optIn Opted in.
     */
    protected function addDotmailerNewsletterUnsubscribeFilter($unsubscribe)
    {
        $this->addWhereInFilter('dotmailer_newsletter_unsubscribe', $unsubscribe);
    }

    /**
     * Add newsletter types filter to existing query object.
     *
     * @param mixed $typeId Newsletter types Ids.
     */
    protected function addDotmailerNewsletterTypeIdFilter($typeId = null)
    {
        if ($typeId && count($typeId)) {
            $typeIds = implode('|', $typeId);
            $pattern = '^('.$typeIds.')$|(^'.$typeIds.',)|(,'.$typeIds.'$)|(,'.$typeIds.',)';

            $this->queryBuilder->andWhere("regexp(".$this->getRepositoryAlias().".dotmailer_newsletter_type_id, '".$pattern."') != false");
        }
    }

    /**
     * Add town filter to existing query object.
     *
     * @param mixed $townId Town id.
     */
    protected function addTownIdFilter($townId = null)
    {
        if ($townId) {
            if (!is_array($townId)) {
                $townId = explode(',', $townId);
            }
            $this->addWhereInFilter('town_id', $townId);
        }
    }

    /**
     * Add county filter to existing query object.
     *
     * @param mixed $countyId County id.
     */
    protected function addCountyIdFilter($countyId = null)
    {
        if ($countyId) {
            if (!is_array($countyId)) {
                $countyId = explode(',', $countyId);
            }
            $this->addWhereInFilter('county_id', $countyId);
        }
    }

    /**
     * Add enquiry town filter to existing query object.
     *
     * @param mixed $townId Enquiry town id.
     */
    protected function addEnquiryTownIdFilter($townId = null)
    {
        if ($townId) {
            if (!is_array($townId)) {
                $townId = explode(',', $townId);
            }
            $this->addWhereInFilter('enquiry_town_id', $townId);
        }
    }

    /**
     * Add enquiry county filter to existing query object.
     *
     * @param mixed $countyId Enquiry county id.
     */
    protected function addEnquiryCountyIdFilter($countyId = null)
    {
        if ($countyId) {
            if (!is_array($countyId)) {
                $countyId = explode(',', $countyId);
            }
            $this->addWhereInFilter('enquiry_county_id', $countyId);
        }
    }

    /**
     * Add user type filter to existing query object.
     *
     * @param mixed $roleId Role id.
     */
    protected function addRoleIdFilter($roleId = null)
    {
        if ($roleId == '-1') {
            $this->addWhereInFilter('is_half_account', 1);
        } else {
            $this->addWhereInFilter('role_id', $roleId);
        }
    }

    /**
     * Add business category filter to existing query object.
     *
     * @param mixed $roleId Role id.
     */
    protected function addBusinessCategoryIdFilter($businessCategoryId = null)
    {
        $this->addWhereInFilter('business_category_id', $businessCategoryId);
    }

    /**
     * Add print edition filter to existing query object.
     *
     * @param mixed $printEditionId Print edition id.
     */
    protected function addPrintEditionIdFilter($printEditionId = null)
    {
        if ($printEditionId == 'any-area') {
            $this->addAnyAreaFilter();
        } elseif ($printEditionId == 'non-print-area') {
            $this->addNonPrintEditionIdFilter();
        } elseif ($printEditionId == 'all-print-area') {
            $this->addAllPrintEditionIdFilter();
        } else {
            $this->addWhereInFilter('print_edition_id', $printEditionId);
        }
    }

    /**
     * Add any area (all print area + no print area) filter to existing query object.
     */
    protected function addAnyAreaFilter()
    {
        $printEditionArray = $this->getEntityManager()->getRepository('FaAdBundle:PrintEdition')->getActivePrintEditionArray();
        $this->queryBuilder->leftJoin('Fa\Bundle\EntityBundle\Entity\LocationGroupLocation', LocationGroupLocationRepository::ALIAS, 'WITH', $this->getRepositoryAlias().'.town_id = '.LocationGroupLocationRepository::ALIAS.'.location_town');
        $this->queryBuilder->andWhere('('.$this->getRepositoryAlias().'.print_edition_id IN(:print_edition_id) OR '.LocationGroupLocationRepository::ALIAS.'.location_group = :location_group_id)')
                           ->setParameter('print_edition_id', array_keys($printEditionArray))
                           ->setParameter('location_group_id', LocationGroupRepository::NON_PRINT_LOCATION_GROUP_ID);
        ;
    }

    /**
     * Add all print editions filter to existing query object.
     */
    protected function addAllPrintEditionIdFilter()
    {
        $printEditionArray = $this->getEntityManager()->getRepository('FaAdBundle:PrintEdition')->getActivePrintEditionArray();
        $this->addWhereInFilter('print_edition_id', array_keys($printEditionArray));
    }

    /**
     * Add non print edition filter to existing query object.
     */
    protected function addNonPrintEditionIdFilter()
    {
        $this->queryBuilder->leftJoin('Fa\Bundle\EntityBundle\Entity\LocationGroupLocation', LocationGroupLocationRepository::ALIAS, 'WITH', $this->getRepositoryAlias().'.town_id = '.LocationGroupLocationRepository::ALIAS.'.location_town');
        $this->queryBuilder->andWhere(LocationGroupLocationRepository::ALIAS.'.location_group = :location_group_id');
        $this->queryBuilder->setParameter('location_group_id', LocationGroupRepository::NON_PRINT_LOCATION_GROUP_ID);
    }

    /**
     * Add last paid at from to filter to existing query object
     *
     * @param string $fromTo from value | to value (pipe saperated)
     *
     * @return void
     */
    protected function addLastPaidAtFromToFilter($fromTo = null)
    {
        list($from, $to) = explode('|', $fromTo);

        if (!is_numeric($from)) {
            $from = CommonManager::getTimeStampFromStartDate($from);
        }

        if (!is_numeric($to)) {
            $to   = CommonManager::getTimeStampFromEndDate($to);
        }

        $this->addFromToFilter('last_paid_at', $from, $to);
    }

    /**
     * Add last paa at from to filter to existing query object
     *
     * @param string $fromTo from value | to value (pipe saperated)
     *
     * @return void
     */
    protected function addLastPaaAtFromToFilter($fromTo = null)
    {
        list($from, $to) = explode('|', $fromTo);

        if (!is_numeric($from)) {
            $from = CommonManager::getTimeStampFromStartDate($from);
        }

        if (!is_numeric($to)) {
            $to   = CommonManager::getTimeStampFromEndDate($to);
        }

        $this->addFromToFilter('last_paa_at', $from, $to);
    }

    /**
     * Add last enquiry at from to filter to existing query object
     *
     * @param string $fromTo from value | to value (pipe saperated)
     *
     * @return void
     */
    protected function addLastEnquiryAtFromToFilter($fromTo = null)
    {
        list($from, $to) = explode('|', $fromTo);

        if (!is_numeric($from)) {
            $from = CommonManager::getTimeStampFromStartDate($from);
        }

        if (!is_numeric($to)) {
            $to   = CommonManager::getTimeStampFromEndDate($to);
        }

        $this->addFromToFilter('last_enquiry_at', $from, $to);
    }

    /**
     * Add newsletter signup at from to filter to existing query object
     *
     * @param string $fromTo from value | to value (pipe saperated)
     *
     * @return void
     */
    protected function addNewsletterSignupAtFromToFilter($fromTo = null)
    {
        list($from, $to) = explode('|', $fromTo);

        if (!is_numeric($from)) {
            $from = CommonManager::getTimeStampFromStartDate($from);
        }

        if (!is_numeric($to)) {
            $to   = CommonManager::getTimeStampFromEndDate($to);
        }

        $this->addFromToFilter('newsletter_signup_at', $from, $to);
    }

    /**
     * Generate guid.
     *
     * @param integer $userId
     * @param string  $email
     *
     * @return string.
     */
    public function generateGuid($userId, $email)
    {
        return md5('@#$'.$email.'$#@'.'GUID');
    }

    /**
     * Get emails count based on filters.
     *
     * @param array  $searchParams Saved query filters.
     * @param object $container    Container object.
     *
     * @return integer
     */
    public function getResultCountBasedOnFilters($searchParams = array(), $container = null)
    {
        $tmpsearchParams = array();
        if (isset($searchParams['dotmailer__fad_user'])) {
            unset($searchParams['dotmailer__fad_user']);
            $tmpsearchParams['dotmailer__fad_user'] = 1;
        }
        if (isset($searchParams['dotmailer__ti_user'])) {
            unset($searchParams['dotmailer__ti_user']);
            $tmpsearchParams['dotmailer__ti_user'] = 1;
        }

        $count                 = 0;
        $data['search_filter'] = $searchParams;

        $container->get('fa.searchfilters.manager')->init($this, $this->getEntityManager()->getClassMetadata('FaDotMailerBundle:Dotmailer')->getTableName(), 'search_filter', $data);
        $data = $container->get('fa.searchfilters.manager')->getFiltersData();
        $data['query_filters']['dotmailer']['opt_in'] = 1;
        $data['query_filters']['dotmailer']['is_suppressed'] = 0;

        $container->get('fa.sqlsearch.manager')->init($this, $data);
        $queryBuilder = $container->get('fa.sqlsearch.manager')->getQueryBuilder();

        if (isset($tmpsearchParams['dotmailer__fad_user']) && isset($tmpsearchParams['dotmailer__ti_user'])) {
            $queryBuilder->andWhere(DotmailerRepository::ALIAS.'.fad_user = 1 OR '.DotmailerRepository::ALIAS.'.ti_user = 1');
        } elseif (isset($tmpsearchParams['dotmailer__fad_user'])) {
            $queryBuilder->andWhere(DotmailerRepository::ALIAS.'.fad_user = 1');
        } elseif (isset($tmpsearchParams['dotmailer__ti_user'])) {
            $queryBuilder->andWhere(DotmailerRepository::ALIAS.'.ti_user = 1');
        }

        return $container->get('fa.sqlsearch.manager')->getDistinctResultCount($queryBuilder);
    }

    /**
     * Touch point entry in dotmailer from front side.
     *
     * @param integer $adId
     * @param integer $userId
     * @param string  $touchPoint
     * @param object  $container
     */
    public function doTouchPointEntry($userId, $adId, $touchPoint, $container = null)
    {
        $isNewToDotmailer = false;
        $user      = $this->getEntityManager()->getRepository('FaUserBundle:User')->findOneBy(array('id' => $userId));
        $dotmailer = null;
        if ($user && $user->getEmail()) {
            $dotmailer = $this->findOneBy(array('email' => $user->getEmail()));
        }

        if (is_object($user)) {
            if (!$dotmailer) {
                $isNewToDotmailer = true;
                $dotmailer = new Dotmailer();
                $dotmailer->setOptIn(1);
                $dotmailer->setFadUser(1);
                $dotmailer->setDotmailerNewsletterUnsubscribe(0);
            } else {
                // handle opted In
                $dotmailer = $this->handleOptedIn($dotmailer, $touchPoint, $user->getIsEmailAlertEnabled());
            }

            // Save business details
            $dotmailer->setEmail($user->getEmail());
            $dotmailer->setGuid(CommonManager::generateGuid($user->getEmail()));
            //$dotmailer->setOptIn(1);
            $dotmailer->setOptInType(self::OPTINTYPE);
            $dotmailer->setFirstName($user->getFirstName());
            $dotmailer->setLastName($user->getLastName());
            $dotmailer->setBusinessName($user->getBusinessName());
            if ($user->getRole()) {
                $dotmailer->setRoleId($user->getRole()->getId());
            }
            $dotmailer->setPhone($user->getPhone());

            // update last_paid_at
            if (!$dotmailer->getLastPaidAt()) {
                $lastPaidAt = $this->getEntityManager()->getRepository('FaPaymentBundle:Payment')->getLastPaidAt($user->getId());
                if ($lastPaidAt && isset($lastPaidAt['created_at'])) {
                    $dotmailer->setLastPaidAt($lastPaidAt['created_at']);
                }
            }

            // newsletter type id
            if ($touchPoint == DotmailerRepository::TOUCH_POINT_PAA || ($touchPoint == DotmailerRepository::TOUCH_POINT_ENQUIRY && $user->getIsEmailAlertEnabled() == 1)) {
                $dotmailer = $this->doTouchPointEntryForNewsletterType($dotmailer, $adId, $touchPoint, $user->getIsEmailAlertEnabled(), $user->getIsThirdPartyEmailAlertEnabled(), $container);
            }

            // other fields
            $dotmailer = $this->doTouchPointEntryForOtherFields($dotmailer, $adId, $touchPoint, $container);

            $this->getEntityManager()->persist($dotmailer);
            $this->getEntityManager()->flush($dotmailer);

            // check for user touch point
            if ($touchPoint == DotmailerRepository::TOUCH_POINT_PAA && $dotmailer->getOptIn() == 1 && $user->getIsEmailAlertEnabled() != 1) {
                $user->setIsEmailAlertEnabled(1);
                $this->getEntityManager()->persist($user);
                $this->getEntityManager()->flush($user);
            }

            $this->getEntityManager()->getRepository('FaDotMailerBundle:DotmailerInfo')->doTouchPointEntry($dotmailer->getId(), $adId, $touchPoint);

            //send to dotmailer instantly.
            if ($isNewToDotmailer) {
                //exec('nohup'.' '.$container->getParameter('fa.php.path').' '.$container->get('kernel')->getRootDir().'/console fa:dotmailer:subscribe-contact --id='.$dotmailer->getId().' >/dev/null &');
                $response = $this->sendOneContactToDotmailerRequest($dotmailer, $container);
            }
        }
    }

    /**
     * Touch point entry in dotmailer from front side.
     *
     * @param object  $dotmailer
     * @param string  $touchPoint
     * @param boolean $isEmailAlertEnabled
     *
     * @return object
     */
    public function handleOptedIn($dotmailer, $touchPoint, $isEmailAlertEnabled)
    {
        if ($touchPoint == DotmailerRepository::TOUCH_POINT_PAA) {
            if ($dotmailer->getOptIn() !== 0) {
                $dotmailer->setOptIn(1);
                $dotmailer->setDotmailerNewsletterUnsubscribe(0);
            }
        } elseif ($touchPoint == DotmailerRepository::TOUCH_POINT_ENQUIRY) {
            if ($dotmailer->getOptIn() !== 0 || ($dotmailer->getOptIn() === 0 && $isEmailAlertEnabled)) {
                $dotmailer->setOptIn(1);
                $dotmailer->setDotmailerNewsletterUnsubscribe(0);
            }
        }

        return $dotmailer;
    }

    /**
     * Touch point entry in dotmailer from front side.
     *
     * @param integer $adId
     * @param integer $userId
     * @param string  $touchPoint
     * @param boolean $isEmailAlertEnabled
     * @param boolean $isThirdPartyEmailAlertEnabled
     * @param object  $container
     *
     * @return object
     */
    public function doTouchPointEntryForNewsletterType($dotmailer, $adId, $touchPoint, $isEmailAlertEnabled, $isThirdPartyEmailAlertEnabled, $container = null)
    {
        $newsletterTypeIds = null;

        $ad         = $this->getEntityManager()->getRepository('FaAdBundle:Ad')->findOneBy(array('id' => $adId));
        $categoryId = $ad->getCategory()->getId();

        $categoryPathArray = $this->getEntityManager()->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId);
        $categoryPathArray = array_keys($categoryPathArray);

        $newsletterTypeIds = $this->getEntityManager()->getRepository('FaDotMailerBundle:DotmailerNewsletterType')->getNewsletterTypeIds($categoryPathArray);
        // manually added third party email alert
        if ($isThirdPartyEmailAlertEnabled == 1) {
            $newsletterTypeIds[] = 48;
        }

        if ($touchPoint == DotmailerRepository::TOUCH_POINT_PAA || ($touchPoint == DotmailerRepository::TOUCH_POINT_ENQUIRY && $isEmailAlertEnabled != 1)) {
            if ($dotmailer->getDotmailerNewsletterTypeOptoutId()) {
                $newsletterTypeIds = array_diff($newsletterTypeIds, $dotmailer->getDotmailerNewsletterTypeOptoutId());
            }
        } elseif ($touchPoint == DotmailerRepository::TOUCH_POINT_ENQUIRY && $isEmailAlertEnabled == 1) {
            if ($dotmailer->getDotmailerNewsletterTypeOptoutId()) {
                $modifiedOptedOutId = array_diff($dotmailer->getDotmailerNewsletterTypeOptoutId(), $newsletterTypeIds);
                $modifiedOptedOutId = array_unique($modifiedOptedOutId);
                $dotmailer->setDotmailerNewsletterTypeOptoutId($modifiedOptedOutId);
            }
            $dotmailer->setNewsletterSignupAt(time());
        }

        if (is_array($newsletterTypeIds) && count($newsletterTypeIds) > 0) {
            if ($dotmailer->getDotmailerNewsletterTypeId()) {
                $newsletterTypeIds = array_merge($newsletterTypeIds, $dotmailer->getDotmailerNewsletterTypeId());
                $newsletterTypeIds = array_unique($newsletterTypeIds);
            }

            $dotmailer->setDotmailerNewsletterTypeId($newsletterTypeIds);
        }

        return $dotmailer;
    }

    /**
     * Touch point entry in dotmailer from front side.
     *
     * @param object  $dotmailer
     * @param integer $adId
     * @param string  $touchPoint
     * @param object  $container
     */
    public function doTouchPointEntryForOtherFields($dotmailer, $adId, $touchPoint, $container = null)
    {
        $townId   = null;
        $countyId = null;

        $ad       = $this->getEntityManager()->getRepository('FaAdBundle:Ad')->findOneBy(array('id' => $adId));
        $location = $this->getEntityManager()->getRepository('FaAdBundle:AdLocation')->getLatestLocation($adId);

        if ($location && $location->getLocationTown()) {
            $townId = $location->getLocationTown()->getId();
        }

        if ($location && $location->getLocationDomicile()) {
            $countyId = $location->getLocationDomicile()->getId();
        }

        if ($touchPoint == DotmailerRepository::TOUCH_POINT_PAA) {
            $dotmailer->setTownId($townId);
            $dotmailer->setCountyId($countyId);
            $dotmailer->setLastPaaAt($ad->getPublishedAt());

            $printEditionId = $this->getEntityManager()->getRepository('FaAdBundle:PrintEdition')->getPrintEditionColumnByTownId($townId, $container, 'id');
            if ($printEditionId) {
                $dotmailer->setPrintEditionId($printEditionId);
            }
        } elseif ($touchPoint == DotmailerRepository::TOUCH_POINT_ENQUIRY) {
            $dotmailer->setEnquiryTownId($townId);
            $dotmailer->setEnquiryCountyId($countyId);
            $dotmailer->setLastEnquiryAt(time());
        }

        return $dotmailer;
    }

    /**
     * Generate array to send it to dotmailer.
     *
     * @param object $dotmailer
     * @param object $container
     */
    public function generateDotmailerBulkImportArray($dotmailer, $container)
    {
        $data = array();

        $userTypes = RoleRepository::getUserTypes();
        $userObj = $this->_em->getRepository('FaUserBundle:User')->findOneBy(array('email' => $dotmailer->getEmail()));
        $townId = ($dotmailer->getTownId() ? $dotmailer->getTownId() : ($userObj && $userObj->getLocationTown() ? $userObj->getLocationTown()->getId() : ''));
        $countyId = ($dotmailer->getCountyId() ? $dotmailer->getCountyId() : ($userObj && $userObj->getLocationDomicile() ? $userObj->getLocationDomicile()->getId() : ''));

        $data[] = $dotmailer->getEmail(); // email
        $data[] = $dotmailer->getGuid(); // guid
        $data[] = $dotmailer->getOptIn();  // opt_in
        $data[] = $dotmailer->getOptInType(); // opt_in_type
        $data[] = $dotmailer->getFirstName(); //first_name
        $data[] = $dotmailer->getLastName(); // last_name
        $data[] = $dotmailer->getBusinessName(); // business_name
        $data[] = ($dotmailer->getPostcode() ? $dotmailer->getPostcode() : ($userObj && $userObj->getZip() ? $userObj->getZip() : '')); // postcode
        $data[] = $container->get('fa.entity.cache.manager')->getEntityNameById('FaEntityBundle:Location', $townId); // town
        $data[] = $container->get('fa.entity.cache.manager')->getEntityNameById('FaEntityBundle:Location', $countyId);
        $data[] = $container->get('fa.entity.cache.manager')->getEntityNameById('FaAdBundle:PrintEdition', $dotmailer->getPrintEditionId());
        $data[] = (isset($userTypes[$dotmailer->getRoleId()]) ? $userTypes[$dotmailer->getRoleId()] : null); //user type
        $data[] = $dotmailer->getPhone();
        $data[] = ($dotmailer->getLastPaidAt()!='')?date('d M Y', $dotmailer->getLastPaidAt()):''; //date
        $data[] = ($dotmailer->getLastPaaAt()!='')?date('d M Y', $dotmailer->getLastPaaAt()):'';// date
        $data[] = $dotmailer->getEnqPostcode(); // enquiry_postcode
        $data[] = $container->get('fa.entity.cache.manager')->getEntityNameById('FaEntityBundle:Location', $dotmailer->getEnquiryTownId());
        $data[] = $container->get('fa.entity.cache.manager')->getEntityNameById('FaEntityBundle:Location', $dotmailer->getEnquiryCountyId());
        $data[] = ($dotmailer->getNewsletterSignupAt()!='')?date('d M Y', $dotmailer->getNewsletterSignupAt()):''; // date
        $data[] = ($dotmailer->getLastEnquiryAt()!='')?date('d M Y', $dotmailer->getLastEnquiryAt()):''; // date
        $data[] = sha1(strtolower($dotmailer->getEmail())); // acxiom
        $data[] = ($dotmailer->getFadUser() ? 'Yes' : 'No'); // fad_user
        $data[] = ($dotmailer->getTiUser() ? 'Yes' : 'No');// ti_user
        $userObj = $this->_em->getRepository('FaUserBundle:User')->findOneBy(array('email' => $dotmailer->getEmail()));
        if ($userObj) {
            $data[] = $container->get('fa.entity.cache.manager')->getEntityNameById('FaEntityBundle:Category', $userObj->getBusinessCategoryId()); // business category
        } else {
            $data[] = ''; // business category
        }

        // dotmailer newsletter info fields
        $dotmailerNewsletterTypeId = $dotmailer->getDotmailerNewsletterTypeId();
        $newsletterTypes = $this->getEntityManager()->getRepository('FaDotMailerBundle:DotmailerNewsletterType')->getKeyValueArray($container, 'name');
        foreach ($newsletterTypes as $key => $value) {
            if (is_array($dotmailerNewsletterTypeId) && count($dotmailerNewsletterTypeId) > 0 && in_array($key, $dotmailerNewsletterTypeId)) {
                $data[] = 1;
            } else {
                $data[] = 0;
            }
        }

        return $data;
    }


    /**
     * Update field by email
     *
     * @param string $dotmailer
     * @param string $container
     */
    public function updateFieldByEmail($email, $field)
    {
        $dotmailer = $this->findOneBy(array('email' => $email));
        if ($dotmailer) {
            $methodName = 'set'.str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));
            $dotmailer->$methodName(time());
            $this->getEntityManager()->persist($dotmailer);
            $this->getEntityManager()->flush($dotmailer);
        }
    }

    /**
     * Generate array to send it to dotmailer.
     *
     * @param object $container
     */
    public function generateDotmailerBulkImportLabelArray($container)
    {
        $data = array();

        $data[] = 'Email';
        $data[] = 'guid';
        $data[] = 'optin';
        $data[] = 'opentype';
        $data[] = 'first_name';
        $data[] = 'last_name';
        $data[] = 'business_name';
        $data[] = 'postcode';
        $data[] = 'town';
        $data[] = 'county';
        $data[] = 'print_edition';
        $data[] = 'user_type';
        $data[] = 'phone_number';
        $data[] = 'last_paid';
        $data[] = 'last_paa';
        $data[] = 'enquiry_postcode';
        $data[] = 'enquiry_town';
        $data[] = 'enquiry_county';
        $data[] = 'newsletter_signup';
        $data[] = 'last_enquiry';
        $data[] = 'acxiom';
        $data[] = 'fad_user';
        $data[] = 'ti_user';
        $data[] = 'business_category';

        // dotmailer newsletter info fields
        $newsletterTypes = $this->getEntityManager()->getRepository('FaDotMailerBundle:DotmailerNewsletterType')->getKeyValueArray($container, 'name');
        foreach ($newsletterTypes as $key => $value) {
            $data[] = $value;
        }

        return $data;
    }

    /**
     * Touch point entry in dotmailer from front side.
     *
     * @param integer $adId
     * @param string  $touchPoint
     * @param object  $container
     */
    public function doTouchPointEntryByUser($userId, $touchPoint, $container = null)
    {
        $isNewToDotmailer = false;
        $user = $this->getEntityManager()->getRepository('FaUserBundle:User')->findOneBy(array('id' => $userId));
        $touchPointOpted = ($touchPoint== self::TOUCH_POINT_CREATE_ALERT)?$touchPoint:self::OPTINTYPE;
        
        if (!$user->getIsEmailAlertEnabled()) {
            return;
        }

        $dotmailer = null;
        if ($user && $user->getEmail()) {
            $dotmailer = $this->findOneBy(array('email' => $user->getEmail()));
        }

        if (is_object($user)) {
            if (!$dotmailer) {
                $isNewToDotmailer = true;
                $dotmailer = new Dotmailer();
                $dotmailer->setFadUser(1);
            }

            $dotmailer->setOptIn(1);
            $dotmailer->setDotmailerNewsletterUnsubscribe(0);

            // Save business details
            $dotmailer->setEmail($user->getEmail());
            $dotmailer->setGuid(CommonManager::generateGuid($user->getEmail()));
            $dotmailer->setOptInType($touchPointOpted);
            $dotmailer->setFirstName($user->getFirstName());
            $dotmailer->setLastName($user->getLastName());
            $dotmailer->setBusinessName($user->getBusinessName());
            if ($user->getRole()) {
                $dotmailer->setRoleId($user->getRole()->getId());
            }
            $dotmailer->setPhone($user->getPhone());

            // update last_paid_at
            if (!$dotmailer->getLastPaidAt()) {
                $lastPaidAt = $this->getEntityManager()->getRepository('FaPaymentBundle:Payment')->getLastPaidAt($user->getId());
                if ($lastPaidAt && isset($lastPaidAt['created_at'])) {
                    $dotmailer->setLastPaidAt($lastPaidAt['created_at']);
                }
            }

            $this->getEntityManager()->persist($dotmailer);
            $this->getEntityManager()->flush($dotmailer);

            //send to dotmailer instantly.
            if ($isNewToDotmailer) {
                //exec('nohup'.' '.$container->getParameter('fa.php.path').' '.$container->get('kernel')->getRootDir().'/console fa:dotmailer:subscribe-contact --id='.$dotmailer->getId().' >/dev/null &');
                $response = $this->sendOneContactToDotmailerRequest($dotmailer, $container);
            }
        }
    }

    /**
     * Send request to dotmailer.
     *
     * @param object $dotmailer
     * @param object $container
     *
     * @return boolean
     */
    public function sendOneContactToDotmailerRequest($dotmailer, $container)
    {
        $masterId = $container->getParameter('fa.dotmailer.master.addressbook.id');
        $url = $container->getParameter('fa.dotmailer.api.url').'/'.$container->getParameter('fa.dotmailer.api.version').'/';

        // build url by appending resource to it.
        https://api.dotmailer.com/v2/address-books/[addressBookId]/contacts
        $url = $url.'address-books/'.$masterId.'/contacts';

        $username = $container->getParameter('fa.dotmailer.api.username');
        $password = $container->getParameter('fa.dotmailer.api.password');

        $dataLabels = $this->generateDotmailerBulkImportLabelArray($container);
        $dataValues = $this->generateDotmailerBulkImportArray($dotmailer, $container);
        $data = array();
        $data['email'] = $dataValues[0];
        $data['emailType'] = 'Html';
        $data['optInType'] = 'Single';
        unset($dataLabels[0]);
        foreach ($dataLabels as $index => $dataLabel) {
            $data['dataFields'][] = array(
                'key' => $dataLabel,
                'value' => $dataValues[$index],
            );
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'Accept: application/json',
                'Content-Type: application/json'
            )
        );
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLAUTH_BASIC, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        return $response;
    }

    /**
     * Send request to dotmailer.
     *
     * @param object $container Container object.
     *
     * @return array
     */
    public function sendUnsubscribeUserFromDotmailerRequest($dotmailer, $container)
    {
        $url = $container->getParameter('fa.dotmailer.api.url').'/'.$container->getParameter('fa.dotmailer.api.version').'/';

        // build url by appending resource to it.
        //https://api.dotmailer.com/v2/contacts/unsubscribe
        $url = $url.'contacts/unsubscribe';

        $content = array(
            'email' => $dotmailer->getEmail(),
        );

        $username = $container->getParameter('fa.dotmailer.api.username');
        $password = $container->getParameter('fa.dotmailer.api.password');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'Accept: application/json',
                'Content-Type: application/json'
            )
            );
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLAUTH_BASIC, CURLAUTH_DIGEST);
        curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($content));

        $response = json_decode(curl_exec($ch), true);

        curl_close($ch);

        return $response;
    }

    /**
     * Send request to dotmailer.
     *
     * @param integer $dotmailerId Dotmailer id.
     * @param object  $container   Container object.
     *
     * @return array
     */
    public function sendUserForDotmailerEnrollmentProgramRequest($dotmailerId, $container)
    {
        $url = $container->getParameter('fa.dotmailer.api.url').'/'.$container->getParameter('fa.dotmailer.api.version').'/';

        // build url by appending resource to it.
        //https://api.dotmailer.com/v2/programs/enrolments
        $url = $url.'programs/enrolments';
        $dotmailerProgramId = $this->_em->getRepository('FaCoreBundle:ConfigRule')->getDotmailerEnrollmentProgramId($container);

        if ($dotmailerProgramId) {
            $content = array(
                'ProgramId' => $dotmailerProgramId,
                'Contacts' => array($dotmailerId),
            );

            $username = $container->getParameter('fa.dotmailer.api.username');
            $password = $container->getParameter('fa.dotmailer.api.password');

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                array(
                    'Accept: application/json',
                    'Content-Type: application/json'
                )
                );
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLAUTH_BASIC, CURLAUTH_DIGEST);
            curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($content));

            $response = json_decode(curl_exec($ch), true);

            curl_close($ch);

            return $response;
        }

        return false;
    }
}
