<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ArchiveBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Fa\Bundle\ArchiveBundle\Entity\ArchiveAd;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * Archive ad repository.
 *
 * This class was generated by the Doctrine ORM.
 * Add your own custom repository methods below.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class ArchiveAdRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'aa';

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
     * Move ad to archive.
     *
     * @param object $ad        Ad instance.
     * @param object $container Container object.
     */
    public function moveAdtoArchive($ad, $container = null)
    {
        // Get data from original tables
        $categoryName   = ($ad && $ad->getCategory())?$this->getEntityManager()->getRepository('FaEntityBundle:Category')->getRootCategoryName($ad->getCategory()->getId(), $container, true):'';
        $adData         = $this->getEntityManager()->getRepository('FaAdBundle:Ad')->getAdDataArray($ad);
        $adVerticalData = $this->getEntityManager()->getRepository('FaAdBundle:'.'Ad'.$categoryName)->getAdVerticalDataArray($ad->getId());
        $adLocationData = $this->getEntityManager()->getRepository('FaAdBundle:AdLocation')->getAdLocationDataArray($ad->getId());
        $adModerateData = $this->getEntityManager()->getRepository('FaAdBundle:AdModerate')->getAdModerateDataArray($ad->getId());
        $adViewCounterData  = $this->getEntityManager()->getRepository('FaAdBundle:AdViewCounter')->getAdViewCounterArrayByAdId($ad->getId());

        // Move data to arvhice tables
        $archiveAd = $this->findOneBy(array('ad_main' => $ad->getAdMain()));
        if ($archiveAd) {
            $deleteManager = $container->get('fa.deletemanager');
            try {
                $deleteManager->delete($archiveAd);
            } catch (\Exception $e) {
            }
        }
        $archiveAd = new ArchiveAd();

        $metadata = $this->getEntityManager()->getClassMetaData('Fa\Bundle\ArchiveBundle\Entity\ArchiveAd');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        $archiveAd->setId($ad->getId());
        $archiveAd->setAdData(!empty($adData) ? serialize($adData) : null);
        $archiveAd->setAdVerticalData(!empty($adVerticalData) ? serialize($adVerticalData) : null);
        $archiveAd->setAdLocationData(!empty($adLocationData) ? serialize($adLocationData) : null);
        $archiveAd->setAdModerateData(!empty($adModerateData) ? serialize($adModerateData) : null);
        $archiveAd->setAdViewCounter(isset($adViewCounterData[$ad->getId()]) ? $adViewCounterData[$ad->getId()] : 0);
        $archiveAd->setAdMain($ad->getAdMain());
        $archiveAd->setUserId(($ad->getUser() ? $ad->getUser()->getId() : null));
        $archiveAd->setEmail(($ad->getUser() ? $ad->getUser()->getEmail() : null));
        $archiveAd->setArchivedAt(time());

        $this->getEntityManager()->persist($archiveAd);
        $this->getEntityManager()->flush();

        // move ad images to archive and delete from original table
        if ($archiveAd) {
            $this->getEntityManager()->getRepository('FaArchiveBundle:ArchiveAdImage')->moveAdImagesToArchive($archiveAd, $container);
        }

        // Remove from original tables
        if (!empty($adVerticalData)) {
            $this->getEntityManager()->getRepository('FaAdBundle:'.'Ad'.$categoryName)->removeByAdId($ad->getId());
        }
        // Remove ad locations
        if (!empty($adLocationData)) {
            $this->getEntityManager()->getRepository('FaAdBundle:AdLocation')->removeByAdId($ad->getId());
        }
        // Remove ad moderate
        if (!empty($adModerateData)) {
            $this->getEntityManager()->getRepository('FaAdBundle:AdModerate')->removeByAdId($ad->getId());
        }
        // Remove ad view counter
        $this->getEntityManager()->getRepository('FaAdBundle:AdViewCounter')->removeByAdId($ad->getId());

        // Remove from favorites
        $this->getEntityManager()->getRepository('FaAdBundle:AdFavorite')->removeByAdId($ad->getId());

        // Remove from shortlists
        $this->getEntityManager()->getRepository('FaAdBundle:AdShortlist')->removeByAdId($ad->getId());

        // Remove from ad prints
        $this->getEntityManager()->getRepository('FaAdBundle:AdPrint')->removeByAdId($ad->getId());

        // Remove ad report
        $this->getEntityManager()->getRepository('FaAdBundle:AdReport')->removeByAdId($ad->getId());

        // Remove ad contact
        $this->getEntityManager()->getRepository('FaAdBundle:AdContact')->removeByAdId($ad->getId());

        // Remove ad user messages
        $this->getEntityManager()->getRepository('FaMessageBundle:Message')->removeByAdId($ad->getId());

        // Remove ad user review
        $this->getEntityManager()->getRepository('FaUserBundle:UserReview')->removeByAdId($ad->getId());

        if ($ad) {
            $adId = $ad->getId();
            $this->getEntityManager()->remove($ad);
            $this->getEntityManager()->flush();

            // Remove archive ad from solr.
            //$this->removeArchiveAdFromSolr($adId, $container);
        }
    }

    /**
     * Get ad detail array from archive ad object.
     *
     * @param object/integer $archiveAd
     * @param object         $container
     *
     * @return array
     */
    public function getAdDetailArray($archiveAd, $container)
    {
        $adDetail = null;

        if (!is_object($archiveAd)) {
            $archiveAd =  $this->find($id);
        }

        if (is_object($archiveAd)) {
            $adDetail = unserialize($archiveAd->getAdData());
            $adId     = $archiveAd->getId();

            //set category array
            if (isset($adDetail['category_id']) && $adDetail['category_id']) {
                $categoryPath = $this->_em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($adDetail['category_id'], false, $container);
                $adDetail['category_path'] = $categoryPath;
            }

            //set payment method
            if (isset($adDetail['payment_method_id']) && $adDetail['payment_method_id']) {
                $paymentMethods = $this->_em->getRepository('FaPaymentBundle:Payment')->getPaymentMethodOptionsArray($container);
                if (isset($paymentMethods[$adDetail['payment_method_id']])) {
                    $adDetail['payment_method'] = $paymentMethods[$adDetail['payment_method_id']];
                }
            }
            //ad user detail
            if ($archiveAd->getUserId()) {
                $userDetailArray = $this->_em->getRepository('FaUserBundle:User')->getAdUserDetail($archiveAd->getUserId());
                if (count($userDetailArray)) {
                    $adDetail['user'] = $userDetailArray;
                }
            }

            // set ad images
            $adImages = $this->_em->getRepository('FaArchiveBundle:ArchiveAdImage')->findImagesByAdId($adId);
            if ($adImages) {
                foreach ($adImages as $adImage) {
                    $adDetail['images'][] = array(
                        'path' => $adImage->getPath(),
                        'hash' => $adImage->getHash(),
                        'ord'  => $adImage->getOrd(),
                    );
                }
            }

            // se ad location
            if ($archiveAd->getAdLocationData()) {
                $adLocations = unserialize($archiveAd->getAdLocationData());
                foreach ($adLocations as $adLocation) {
                    if (isset($adLocation['latitude']) && isset($adLocation['longitude'])) {
                        $adDetail['latitude'] = $adLocation['latitude'];
                        $adDetail['longitude'] = $adLocation['longitude'];
                        break;
                    }
                }
            }

            //ad dimensions
            if (isset($adDetail['category_id']) && $adDetail['category_id']) {
                $categoryIds = array_keys($categoryPath);

                $dimensionArray = $this->getAdDimensionByCategoryIdAndAdId($adDetail['category_id'], $adId, $archiveAd, (isset($categoryIds[0]) ? $categoryIds[0] : ''), $adDetail, $container);
                if (count($dimensionArray)) {
                    $adDetail = $adDetail + $dimensionArray;
                }
            }
        }

        return $adDetail;
    }

    /**
     * Get dimension array by category and ad id.
     *
     * @param integer $categoryId     Category id.
     * @param integer $adId           Ad id.
     * @param object  $archiveAd      Object of archive ad.
     * @param string  $rootCategoryId Root category id.
     * @param array   $adDetail       Ad Detail array.
     * @param object  $container      Container identifier.
     *
     * @return array
     */
    public function getAdDimensionByCategoryIdAndAdId($categoryId, $adId, $archiveAd, $rootCategoryId, $adDetail, $container)
    {
        $dimensionFields    = array();
        $repository         = null;
        $className          = null;
        $entityCacheManager = $container->get('fa.entity.cache.manager');
        //get dimension fields categorywise.
        if ($rootCategoryId) {
            $className   = CommonManager::getCategoryClassNameById($rootCategoryId, true);
            $repository  = $this->_em->getRepository('FaAdBundle:Ad'.$className);
            if (method_exists($repository, 'getAdDimensionFields')) {
                $dimensionFields = $repository->getAdDimensionFields();
            }
        }

        //ad dimensions
        $dimensionArray = array();
        $adVerticalDeta = null;
        if ($archiveAd->getAdVerticalData()) {
            $adVerticalDeta = unserialize($archiveAd->getAdVerticalData());
        }

        if ($adVerticalDeta) {
            $metaData = array();
            if (isset($adVerticalDeta['meta_data'])) {
                $metaData = unserialize($adVerticalDeta['meta_data']);
                if (!is_array($metaData)) {
                    $metaData = unserialize($metaData);
                }
            }
            //$metaData = (isset($adVerticalDeta['meta_data']) ? unserialize(unserialize($adVerticalDeta['meta_data'])) : array());
            $paaFields = $this->_em->getRepository('FaAdBundle:PaaField')->getDimensionPaaFieldsWithLabel($categoryId);
            //common fields.
            $adDetailCommonFields['category_id|FaEntityBundle:Category'] = 'Category';
            $adDetailCommonFields['type_id|FaEntityBundle:Entity']       = 'Ad type';
            $adDetailCommonFields['is_new']                              = 'Item';

            //loop through all common fields.
            foreach ($adDetailCommonFields as $adDetailFieldKey => $adDetailFieldLabel) {
                $adDetailFieldKeyResult = explode('|', $adDetailFieldKey);
                $adDetailField          = $adDetailFieldKeyResult[0];
                $repositoryName         = isset($adDetailFieldKeyResult[1]) ? $adDetailFieldKeyResult[1] : '';
                $key                    = (in_array($adDetailField, $dimensionFields) ? 'dimension' : 'detail');

                //check if it is separate field else check in meta data fields.
                if (isset($adDetail[$adDetailField])) {
                    if ($repositoryName) {
                        $adDetailFieldValue = explode(',', $adDetail[$adDetailField]);
                        //check for single and multiple values.
                        if ($adDetailFieldValue && is_array($adDetailFieldValue)) {
                            foreach ($adDetailFieldValue as $adDetailFieldValue) {
                                $dimensionArray[$key][$adDetailFieldLabel][] = $entityCacheManager->getEntityNameById($repositoryName, $adDetailFieldValue);
                            }
                        }
                    } else {
                        $fieldValue = null;
                        switch ($adDetailField) {
                            case 'is_new':
                                $fieldValue = $this->_em->getRepository('FaEntityBundle:Entity')->getIsNewNameById($adDetail[$adDetailField], $container);
                                break;
                        }
                        $dimensionArray[$key][$adDetailFieldLabel] = $fieldValue;
                    }
                }
            }

            foreach ($paaFields as $field => $label) {
                $key   = (in_array($field, $dimensionFields) ? 'dimension' : 'detail');
                $metaData = $metaData + $adVerticalDeta;
                $value = $this->_em->getRepository('FaAdBundle:PaaField')->getPaaFieldValue($field, null, $metaData, $container, $className);
                if ($value != null) {
                    $dimensionArray[$key][$label] = $value;
                }
            }
        }

        return $dimensionArray;
    }

    /**
     * Add ad status filter to existing query object.
     *
     * @param integer $statusId Ad status id.
     */
    protected function addUserIdFilter($userId = null)
    {
        if ($userId) {
            if (!is_array($userId)) {
                $userId = array($userId);
            }

            $userId = array_filter($userId);

            if (count($userId)) {
                $this->queryBuilder->andWhere($this->getRepositoryAlias().'.user_id IN (:'.$this->getRepositoryAlias().'_user_id'.')');
                $this->queryBuilder->setParameter($this->getRepositoryAlias().'_user_id', $userId);
            }
        }
    }

    /**
     * Add user email address filter to existing query object.
     *
     * @param string $email User email.
     */
    protected function addEmailFilter($email = null)
    {
        $this->queryBuilder->andWhere(sprintf('%s.email LIKE \'%%%s%%\'', $this->getRepositoryAlias(), trim($email)));
    }

    /**
     * Remove ad from solr.
     *
     * @param integer $adId Ad id.
     *
     * return boolean
     */
    protected function removeArchiveAdFromSolr($adId, $container = null)
    {
        try {
            $solrClient = $container->get('fa.solr.client.ad');
            if ($solrClient->ping()) {
                $solr = $solrClient->connect();
                $solr->deleteById($adId);
                $solr->commit(true);
            }

            $solrClientNew = $this->getContainer()->get('fa.solr.client.ad.new');
            if ($solrClientNew->ping()) {
                $solrNew = $solrClientNew->connect();
                $solrNew->deleteById($adId);
                $solrNew->commit(true);
            }
        } catch (\Exception $e) {
        }
    }
}
