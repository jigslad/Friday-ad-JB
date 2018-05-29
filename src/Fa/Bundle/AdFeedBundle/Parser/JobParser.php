<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdFeedBundle\Parser;

use Fa\Bundle\AdFeedBundle\Parser\AdParser;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Gedmo\Sluggable\Util\Urlizer as Urlizer;
use Fa\Bundle\AdFeedBundle\Entity\AdFeedMapping;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\AdFeedBundle\Entity\AdFeed;
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\AdBundle\Entity\AdJobs;
use \Curl\Curl;
use \Curl\MultiCurl;

/**
 * Job parser.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class JobParser extends AdParser
{

    /**
     * Map ad data.
     *
     * @param array   $adArray Advert array.
     * @param integer $siteID  site id
     */
    public function mapAdData($adArray, $siteID, $ad_feed_site_download = null)
    {
        $this->advert              = array();
        $this->advert['feed_type'] = 'JobAdvert';
        $this->advert['full_data'] = (string) serialize($adArray);
        $this->advert['set_user']  = true;
        $this->advert['status']    = 'A';
        $this->advert['affiliate'] = 0;
        $this->rejectedReason      = null;
        $this->advert['rejected_reason'] = array();

        if (isset($adArray['SiteVisibility']) && is_array($adArray['SiteVisibility'])) {
            foreach ($adArray['SiteVisibility'] as $site) {
                if (isset($site['SiteId']) && $site['SiteId'] == 10) {
                    if ($site['IsMainSite'] === 'false' || $site['IsMainSite'] == false) {
                        $this->advert['affiliate'] = 1;
                        $this->advert['set_user'] = false;
                        $this->advert['track_back_url'] = $adArray['TrackbackUrl'];
                        if ($this->advert['track_back_url'] == '') {
                            $this->setRejectAd();
                            $this->setRejectedReason('track_back_url: not exists for affiliate advert');
                        }
                    }
                }
            }
        }

        $this->setCommonData($adArray, $siteID);

        $this->advert['category_id'] = $this->getCategoryId($adArray['Details']['JobType']);

        if (!$this->advert['category_id']) {
            $this->setRejectAd();
            if (isset($adArray['Details']['Category'])) {
                $this->setRejectedReason('category missing: '.$adArray['Details']['Category']);
            } else {
                $this->setRejectedReason('category not specified');
            }
        }

        if (isset($adArray['Details']['Agency']) && $adArray['Details']['Agency']) {
            $this->advert['user']['role'] = RoleRepository::ROLE_BUSINESS_SELLER;
            $this->advert['user']['business_category_id'] = CategoryRepository::JOBS_ID;
            if (!$this->advert['user']['business_name'] && isset($adArray['Details']['EmployerName'])) {
                $this->advert['user']['business_name'] = $adArray['Details']['EmployerName'];
            }
            $this->advert['is_trade_ad'] = 1;
        } else {
            $this->advert['user']['role'] = RoleRepository::ROLE_SELLER;
            if (isset($adArray['Details']['EmployerName']) && $adArray['Details']['EmployerName']) {
                $this->advert['user']['first_name'] = $adArray['Details']['EmployerName'];
            }
            $this->advert['is_trade_ad'] = 0;
        }

        $description = array();


        foreach ($adArray['Descriptions'] as $d) {
            $description[] = $d['Text'];
        }

        $this->advert['description'] = implode('\n', $description);

        if (isset($adArray['Details']['EmploymentType']) && $adArray['Details']['EmploymentType'] != '') {
            $this->advert['jobs']['contract_type_id'] = $this->getEntityId($adArray['Details']['EmploymentType'], 144);
        }

        if (isset($adArray['Details']['Salary']) && $adArray['Details']['Salary'] != '') {
            $this->advert['jobs']['feed_ad_salary'] = $adArray['Details']['Salary'];
        }

        if (isset($adArray['Currency']) && $adArray['Currency'] == 'GBP' && isset($adArray['Details']['SalaryType']) && strtolower($adArray['Details']['SalaryType']) == 'per annum') {
            $this->advert['jobs']['meta_data']['salary_type_id'] = 2454;

            if (isset($adArray['Details']['maxSalary']) && $adArray['Details']['maxSalary']) {
                $salaryBandArray = $this->em->getRepository('FaEntityBundle:Entity')->getEntitySlugArrayByType(258, $this->container);
                if ($adArray['Details']['maxSalary'] <= 15000) {
                    $key = array_search('10k-15k', $salaryBandArray);
                    if ($key !== false) {
                        $this->advert['jobs']['salary_band_id'] = $key;
                    }
                } elseif ($adArray['Details']['maxSalary'] > 15000 && $adArray['Details']['maxSalary'] <= 20000) {
                    $key = array_search('15k-20k', $salaryBandArray);
                    if ($key !== false) {
                        $this->advert['jobs']['salary_band_id'] = $key;
                    }
                } elseif ($adArray['Details']['maxSalary'] > 20000 && $adArray['Details']['maxSalary'] <= 25000) {
                    $key = array_search('20k-25k', $salaryBandArray);
                    if ($key !== false) {
                        $this->advert['jobs']['salary_band_id'] = $key;
                    }
                } elseif ($adArray['Details']['maxSalary'] > 25000 && $adArray['Details']['maxSalary'] <= 30000) {
                    $key = array_search('25k-30k', $salaryBandArray);
                    if ($key !== false) {
                        $this->advert['jobs']['salary_band_id'] = $key;
                    }
                } elseif ($adArray['Details']['maxSalary'] > 30000 && $adArray['Details']['maxSalary'] <= 40000) {
                    $key = array_search('30k-40k', $salaryBandArray);
                    if ($key !== false) {
                        $this->advert['jobs']['salary_band_id'] = $key;
                    }
                } elseif ($adArray['Details']['maxSalary'] > 40000 && $adArray['Details']['maxSalary'] <= 50000) {
                    $key = array_search('40k-50k', $salaryBandArray);
                    if ($key !== false) {
                        $this->advert['jobs']['salary_band_id'] = $key;
                    }
                } elseif ($adArray['Details']['maxSalary'] > 50000 && $adArray['Details']['maxSalary'] <= 60000) {
                    $key = array_search('50k-60k', $salaryBandArray);
                    if ($key !== false) {
                        $this->advert['jobs']['salary_band_id'] = $key;
                    }
                } elseif ($adArray['Details']['maxSalary'] > 60000 && $adArray['Details']['maxSalary'] <= 70000) {
                    $key = array_search('60k-70k', $salaryBandArray);
                    if ($key !== false) {
                        $this->advert['jobs']['salary_band_id'] = $key;
                    }
                } elseif ($adArray['Details']['maxSalary'] > 70000 && $adArray['Details']['maxSalary'] <= 80000) {
                    $key = array_search('70k-80k', $salaryBandArray);
                    if ($key !== false) {
                        $this->advert['jobs']['salary_band_id'] = $key;
                    }
                } elseif ($adArray['Details']['maxSalary'] > 80000 && $adArray['Details']['maxSalary'] <= 90000) {
                    $key = array_search('80k-90k', $salaryBandArray);
                    if ($key !== false) {
                        $this->advert['jobs']['salary_band_id'] = $key;
                    }
                } elseif ($adArray['Details']['maxSalary'] > 90000 && $adArray['Details']['maxSalary'] <= 100000) {
                    $key = array_search('90k-100k', $salaryBandArray);
                    if ($key !== false) {
                        $this->advert['jobs']['salary_band_id'] = $key;
                    }
                }
            }
            if (isset($adArray['Details']['MinSalary']) && $adArray['Details']['MinSalary'] && $adArray['Details']['MinSalary'] > 100000) {
                $key = array_search('100k', $salaryBandArray);
                if ($key !== false) {
                    $this->advert['jobs']['salary_band_id'] = $key;
                }
            }
        }

        $feedAd = null;

        if ($ad_feed_site_download) {
            $feedAd = $this->getFeedAdByRef($this->advert['unique_id'], $ad_feed_site_download->getAdFeedSite()->getId());
        } else {
            $ad_feed_site = $this->em->getRepository('FaAdFeedBundle:AdFeedSite')->findOneBy(array('type' => $this->advert['feed_type'], 'ref_site_id' => $siteID));
            $feedAd = $this->getFeedAdByRef($this->advert['unique_id'], $ad_feed_site->getId());
        }

        if (!$feedAd && $adArray['EndDate'] != '0001-01-01T00:00:00Z' && strtotime($adArray['EndDate']) < time()) {
            return 'discard';
        }

        if ($this->advert['user']['email'] == '' && $this->advert['set_user'] == true) {
            $this->setRejectAd();
            $this->setRejectedReason('email is blank');
        }

        if ($feedAd) {
            $this->advert['feed_ad_id'] = $feedAd->getId();
        } else {
            $this->addToFeedAd($ad_feed_site_download);
        }

        $adImages = isset($adArray['AdvertImages']) && count($adArray['AdvertImages']) > 0 ? $adArray['AdvertImages'] : array();
        $this->mapAdImages($adImages, $this->advert['affiliate']);
    }

    /**
     * add to feed ad
     *
     * @param object $ad_feed_site_download
     * @return number
     */
    public function addToFeedAd($ad_feed_site_download)
    {
        $ad_feed_site   = $this->em->getRepository('FaAdFeedBundle:AdFeedSite')->findOneBy(array('type' => $this->advert['feed_type'], 'ref_site_id' => $this->advert['ref_site_id']));

        if ($this->advert['set_user'] === true) {
            $user = $this->getUser($this->advert['user']['email']);
        }

        $feedAd         = $this->getFeedAdByRef($this->advert['unique_id'], $ad_feed_site_download->getAdFeedSite()->getId());

        if (($this->advert['set_user'] === true) && $this->advert['user']['email'] == '') {
            $this->setRejectAd();
            $this->setRejectedReason('user information: missing ');
        }

        if ($this->advert['set_user'] === true) {
            if (!$user && $this->advert['user']['email'] != '') {
                $user = $this->setUser($user);
            }
        }

        if (!$feedAd) {
            $feedAd = new AdFeed();
        }
        $feedAd->setTransId($this->advert['trans_id']);
        $feedAd->setUniqueId($this->advert['unique_id']);
        $feedAd->setIsUpdated(1);
        $feedAd->setRefSiteId($ad_feed_site_download->getAdFeedSite()->getId());
        $feedAd->setAdText(serialize($this->advert));
        $feedAd->setLastModified($ad_feed_site_download->getModifiedSince());

        if (isset($this->advert['status']) && $this->advert['status'] == 'R') {
            $feedAd->setStatus('R');
            if (implode(',', $this->advert['rejected_reason']) != '') {
                $feedAd->setRemark(implode(',', $this->advert['rejected_reason']));
            }
        } else {
            $feedAd->setStatus('A');
        }

        $this->em->persist($feedAd);
        $this->em->flush();
        $this->advert['feed_ad_id'] = $feedAd->getId();
    }

    /**
     * add data in child table
     *
     * @see \Fa\Bundle\AdFeedBundle\Parser\AdParser::addChildData()
     */
    public function addChildData($ad)
    {
        $ad_job = $this->em->getRepository('FaAdBundle:AdJobs')->findOneBy(array('ad' => $ad->getId()));

        if (!$ad_job) {
            $ad_job = new AdJobs();
        }

        $ad_job->setAd($ad);

        if (isset($this->advert['jobs']['contract_type_id'])) {
            $ad_job->setContractTypeId($this->advert['jobs']['contract_type_id']);
        } else {
            $ad_job->setContractTypeId(null);
        }

        if (isset($this->advert['jobs']['feed_ad_salary'])) {
            $ad_job->setFeedAdSalary($this->advert['jobs']['feed_ad_salary']);
        } else {
            $ad_job->setFeedAdSalary(null);
        }

        if (isset($this->advert['jobs']['salary_band_id'])) {
            $ad_job->setSalaryBandId($this->advert['jobs']['salary_band_id']);
        } else {
            $ad_job->setSalaryBandId(null);
        }

        if (isset($this->advert['jobs']['meta_data'])) {
            $ad_job->setMetaData(serialize($this->advert['jobs']['meta_data']));
        } else {
            $ad_job->setMetaData(null);
        }

        $this->em->persist($ad_job);
    }

    /**
     * get entity id
     *
     * @param string    $string
     * @param integer $dimension_id
     *
     * @return integer or null
     */
    private function getEntityId($string, $dimension_id)
    {
        return $this->em->getRepository('FaEntityBundle:Entity')->getEntityIdByCategoryDimensionAndName($dimension_id, trim($string), $this->container);
    }

    /**
     * Get category id.
     *
     * @param string $string Category.
     */
    public function getCategoryId($cat_name = null)
    {
        if ($cat_name) {
            $matchedText = null;
            $mapping = $this->em->getRepository('FaAdFeedBundle:AdFeedMapping')->findOneBy(array('text' => $cat_name));

            if ($mapping) {
                $matchedText = $mapping->getTarget();
            } else {
                $mapping = new AdFeedMapping();
                $mapping->setText($cat_name);
                $mapping->setRefSiteId(9);
                $this->em->persist($mapping);
                $this->em->flush();
            }

            if ($matchedText) {
                $categoryDetail = $this->em->getRepository('FaEntityBundle:Category')->getCategoryByFullSlug($matchedText, $this->container);
                if (isset($categoryDetail['id'])) {
                    return $categoryDetail['id'];
                }
            }
        }

        return null;
    }
}
