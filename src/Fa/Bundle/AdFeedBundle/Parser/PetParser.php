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
use Fa\Bundle\AdBundle\Entity\AdAnimals;

/**
 * Pet parser.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class PetParser extends AdParser
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
        $this->advert['feed_type'] = 'PetAdvert';
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

        $this->advert['category_id'] = $this->getCategoryId($adArray['Details']['AnimalType']);

        if (!isset($this->advert['category_id'])) {
            $this->setRejectAd();
            $this->setRejectedReason('Category not found '.$adArray['Details']['AnimalType']);
        }

        $this->advert['ad_type_id']  = 2620;
        $this->advert['user']['role'] = RoleRepository::ROLE_SELLER;
        $this->advert['user']['business_category_id'] = CategoryRepository::ANIMALS_ID;

        $description = array();


        foreach ($adArray['Descriptions'] as $d) {
            $description[] = $d['Text'];
        }

        $this->advert['description'] = implode('\n', $description);



        if (isset($adArray['Details']['Gender']) && $adArray['Details']['Gender'] != '') {
            $this->advert['animals']['gender_id'] = $this->getEntityId($adArray['Details']['Gender'], 192);

            if (!$this->advert['animals']['gender_id']) {
                $this->advert['animals']['meta_data']['gender'] = $adArray['Details']['Gender'];
            }
        }

        if (isset($adArray['Details']['Colour']) && $adArray['Details']['Colour'] != '') {
            $this->advert['animals']['colour_id'] = $this->getEntityId($adArray['Details']['Colour'], 195);

            if (!$this->advert['animals']['colour_id']) {
                $this->advert['animals']['meta_data']['colour'] = $adArray['Details']['Colour'];
            }
        }

        if (isset($adArray['Details']['Breed']) && $adArray['Details']['Breed'] != '') {
            $dimension_id = $this->getBreedDimensionId($this->advert['category_id']);
            $this->advert['animals']['breed_id'] = null;
            if ($dimension_id) {
                $this->advert['animals']['breed_id'] = $this->getEntityId($adArray['Details']['Breed'], $dimension_id);
            }

            if (!$this->advert['animals']['breed_id']) {
                if ($this->advert['category_id'] == CategoryRepository::BIRDS) {
                    $this->advert['animals']['meta_data']['species'] = $adArray['Details']['Breed'];
                } else {
                    $this->advert['animals']['meta_data']['breed'] = $adArray['Details']['Breed'];
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

        if (!$feedAd && $adArray['EndDate'] != '0001-01-01T00:00:00Z') {
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

        $ad             = $this->getAdByRef($this->advert['unique_id']);
        $feedAd         = $this->getFeedAdByRef($this->advert['unique_id'], $ad_feed_site_download->getAdFeedSite()->getId());

        if (($this->advert['set_user'] === true) && $this->advert['user']['email'] == '') {
            $this->setRejectAd();
            $this->setRejectedReason('user information: missing ');
        }

        if ($this->advert['set_user'] === true) {
            if (!$user || !$feedAd || (md5(serialize($this->advert['user'])) != $feedAd->getUserHash())) {
                if ($this->advert['user']['email'] != '') {
                    $user = $this->setUser($user);
                }
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
     * get matched string
     *
     * @param string text
     *
     * @return string
     */
    private function getMatchedText($text)
    {
        $mapping = $this->em->getRepository('FaAdFeedBundle:AdFeedMapping')->findOneBy(array('text' => $text));

        if ($mapping) {
            return $mapping->getTarget();
        } else {
            $mapping = new AdFeedMapping();
            $mapping->setText($text);
            $this->em->persist($mapping);
            $this->em->flush();
        }
    }


    /**
     * add data in child table
     *
     * @see \Fa\Bundle\AdFeedBundle\Parser\AdParser::addChildData()
     */
    public function addChildData($ad)
    {
        $ad_animal = $this->em->getRepository('FaAdBundle:AdAnimals')->findOneBy(array('ad' => $ad->getId()));

        if (!$ad_animal) {
            $ad_animal = new AdAnimals();
        }

        $ad_animal->setAd($ad);

        if (isset($this->advert['animals']['breed_id'])) {
            if ($this->advert['category_id'] == CategoryRepository::BIRDS) {
                $ad_animal->setSpeciesId($this->advert['animals']['breed_id']);
            } else {
                $ad_animal->setBreedId($this->advert['animals']['breed_id']);
            }
        } else {
            if ($this->advert['category_id'] == CategoryRepository::BIRDS) {
                $ad_animal->setSpeciesId(null);
            } else {
                $ad_animal->setBreedId(null);
            }
        }

        if (isset($this->advert['animals']['gender_id'])) {
            $ad_animal->setGenderId($this->advert['animals']['gender_id']);
        } else {
            $ad_animal->setGenderId(null);
        }

        if (isset($this->advert['animals']['colour_id'])) {
            $ad_animal->setColourId($this->advert['animals']['colour_id']);
        } else {
            $ad_animal->setColourId(null);
        }

        if (isset($this->advert['animals']['meta_data'])) {
            $ad_animal->setMetaData(serialize($this->advert['animals']['meta_data']));
        } else {
            $ad_animal->setMetaData(null);
        }

        $this->em->persist($ad_animal);
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
        $cat_name = strtolower($cat_name);

        if ($cat_name == 'aquatic') {
            return 734;
        } elseif ($cat_name == 'bird' || $cat_name == 'birds') {
            return CategoryRepository::BIRDS;
        } elseif ($cat_name == 'cat' || $cat_name == 'cats and kittens') {
            return CategoryRepository::CATS_AND_KITTENS;
        } elseif ($cat_name == 'dog' || $cat_name == 'dogs and puppies') {
            return CategoryRepository::DOGS_AND_PUPPIES;
        } elseif ($cat_name == 'rabbit') {
            return CategoryRepository::RABBITS;
        } elseif ($cat_name == 'small pets') {
            return 737;
        }
    }

    /**
     * get breed dimension id by category
     *
     * @param integer $category_id
     *
     * @return integer
     */
    private function getBreedDimensionId($category_id)
    {
        if ($category_id == CategoryRepository::BIRDS) {
            return 197;
        } elseif ($category_id == CategoryRepository::CATS_AND_KITTENS) {
            return 198;
        } elseif ($category_id == CategoryRepository::DOGS_AND_PUPPIES) {
            return 199;
        }
    }
}
