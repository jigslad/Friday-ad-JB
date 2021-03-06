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
use Fa\Bundle\AdBundle\Entity\AdReport;

/**
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdReportRepository extends EntityRepository
{
    const ALIAS = 'ar';

    const AD_MODERATE_STATUS_SENT = 0;

    const AD_MODERATE_STATUS_SUCCESS = 1;

    const AD_MODERATE_STATUS_FAILURE = 2;

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
     * Check ad report for user.
     *
     * @param integer $adId   Ad id.
     * @param integer $userId User id.
     *
     * @return mixed
     */
    public function checkAdReportForUser($adId, $userId)
    {
        $query = $this->createQueryBuilder(self::ALIAS)
            ->andWhere(self::ALIAS.'.ad = :adId')
            ->setParameter('adId', $adId)
            ->andWhere(self::ALIAS.'.user = :userId')
            ->setParameter('userId', $userId)
            ->setMaxResults(1);

        return $query->getQuery()->getOneOrNullResult();
    }

    /**
     * Get ad report rejected count.
     *
     * @param integer $adId   Ad id.
     *
     * @return integer
     */
    public function getAdReportRejectedCount($adId)
    {
        $adReportCount = 0;
        $query = $this->createQueryBuilder(self::ALIAS)
            ->select('COUNT('.self::ALIAS.'.id) as report_count')
            ->andWhere(self::ALIAS.'.ad = :adId')
            ->setParameter('adId', $adId)
            ->andWhere(self::ALIAS.'.ad_moderate_status = '.self::AD_MODERATE_STATUS_FAILURE)
            ->addGroupBy(self::ALIAS.'.ad');

        $adReportResult = $query->getQuery()->getOneOrNullResult();
        if (isset($adReportResult['report_count'])) {
            $adReportCount = $adReportResult['report_count'];
        }
        return $adReportCount;
    }

    /**
     * Remove ad from solr.
     *
     * @param integer $adId      Ad id.
     * @param object  $container Container identifier.
     *
     * return boolean
     */
    public function removeAdFromSolr($adId, $container)
    {
        $solrClient = $container->get('fa.solr.client.ad');
        if (!$solrClient->ping()) {
            return false;
        }

        $solr = $solrClient->connect();
        $solr->deleteById($adId);
        $solr->commit(true);

        $solrClientNew = $this->getContainer()->get('fa.solr.client.ad.new');
        if (!$solrClientNew->ping()) {
            return false;
        }
        $solrNew = $solrClientNew->connect();
        $solrNew->deleteById($adId);
        $solrNew->commit(true);

        return true;
    }

    /**
     * Add ad report.
     *
     * @param object  $ad        Ad object.
     * @param integer $userId    User id.
     * @param string  $ipAddress Ip address.
     * @param object  $container Container indentifier.
     *
     * @return mixed
     */
    public function addAdReport($ad, $userId, $ipAddress, $container)
    {
        //add ad to moderation if ad is not moderation
        $query = $this->createQueryBuilder(self::ALIAS)
            ->andWhere(self::ALIAS.'.ad = :adId')
            ->setParameter('adId', $ad->getId())
            ->andWhere(self::ALIAS.'.ad_moderate_status = '.self::AD_MODERATE_STATUS_SENT)
            ->setMaxResults(1);
        $adReported = $query->getQuery()->getOneOrNullResult();

        $adReport = new AdReport();
        $adReport->setAd($ad);
        if ($userId) {
            $adReport->setUser($this->_em->getReference('FaUserBundle:User', $userId));
        }
        $adReport->setAdModerateStatus(self::AD_MODERATE_STATUS_SENT);
        $adReport->setIp($ipAddress);
        $this->_em->persist($adReport);
        $this->_em->flush($adReport);

        if (!$adReported) {
            $this->_em->getRepository('FaPaymentBundle:Payment')->handleAdModerate($ad);
            $adModerate = $this->_em->getRepository('FaAdBundle:AdModerate')->findOneBy(array('ad' => $ad->getId()));

            if ($adModerate) {
                $buildRequest      = $container->get('fa_ad.moderation.request_build');

                $moderationRequest = $buildRequest->init($ad, $adModerate->getValue(), 1, true, 'manual moderation');

                $moderationRequest = json_encode($moderationRequest);

                $sentForModeration = $buildRequest->sendRequest($moderationRequest);
            }
        }
    }

    /**
     * Update ad moderation status.
     *
     * @param array  $moderationResult Moderation result array.
     * @param object $container        Container indentifier.
     *
     * @return boolean
     */
    public function updateAdModerationStatus($moderationResult, $container)
    {
        $adModerateStatus = '';
        $adId             = '';
        $moderationResult = array_change_key_case($moderationResult, CASE_LOWER);

        if (isset($moderationResult['adref'])) {
            $adId = $moderationResult['adref'];
        }

        //check ad is reported.
        $query = $this->createQueryBuilder(self::ALIAS)
        ->andWhere(self::ALIAS.'.ad = :adId')
        ->setParameter('adId', $adId)
        ->andWhere(self::ALIAS.'.ad_moderate_status = '.self::AD_MODERATE_STATUS_SENT)
        ->setMaxResults(1);
        $adReported = $query->getQuery()->getOneOrNullResult();

        //if ad is reported then update ad moderate status.
        if ($adReported) {
            if (count($moderationResult) > 0 && $adId) {
                $adModerate = $this->_em->getRepository('FaAdBundle:AdModerate')->findOneBy(array('ad' => $adId));

                if ($adModerate) {
                    if (isset($moderationResult['moderationresult']) && $moderationResult['moderationresult'] == AdModerateRepository::MODERATION_RESULT_OKEY) {
                        $adModerateStatus = self::AD_MODERATE_STATUS_SUCCESS;
                    } elseif (isset($moderationResult['moderationresult']) && in_array($moderationResult['moderationresult'], array(AdModerateRepository::MODERATION_RESULT_REJECTED, AdModerateRepository::MODERATION_RESULT_SCAM))) {
                        $adModerateStatus = self::AD_MODERATE_STATUS_FAILURE;
                    } elseif (isset($moderationResult['moderationresult']) && $moderationResult['moderationresult'] == AdModerateRepository::MODERATION_RESULT_MANUAL_MODERATION) {
                        $adModerateStatus = self::AD_MODERATE_STATUS_SENT;
                    }
                }

                if ($adModerateStatus) {
                    $updateQuery = $this->createQueryBuilder(self::ALIAS)
                        ->update()
                        ->set(self::ALIAS.'.ad_moderate_status', $adModerateStatus)
                        ->andwhere(self::ALIAS.'.ad = '.$adId)
                        ->andwhere(self::ALIAS.'.ad_moderate_status = '.self::AD_MODERATE_STATUS_SENT);
                    $updateQuery->getQuery()->execute();
                }

                $adReportCount = $this->getAdReportRejectedCount($adId);
                //check for ad count report.
                if ($adReportCount >= $container->getParameter('fa.ad.report.limit')) {
                    $this->removeAdFromSolr($adId, $container);
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Remove ad from report by ad id.
     *
     * @param integer $adId Ad id.
     */
    public function removeByAdId($adId)
    {
        $reportAds = $this->getBaseQueryBuilder()
                            ->andWhere(self::ALIAS.'.ad = :adId')
                            ->setParameter('adId', $adId)
                            ->getQuery()
                            ->getResult();

        if ($reportAds) {
            foreach ($reportAds as $reportAd) {
                $this->_em->remove($reportAd);
            }
            $this->_em->flush();
        }
    }
}
