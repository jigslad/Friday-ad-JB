<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\PrintApi;

use Fa\Bundle\AdBundle\Entity\Ad;
use Fa\Bundle\AdBundle\Entity\AdPrint;
use Fa\Bundle\AdBundle\PrintApi\AdPrintFieldMappingInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
// use Symfony\Component\Locale\Stub\DateFormat;
use Symfony\Component\Intl\DateFormatter\DateFormat;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\AdBundle\Solr\AdSolrFieldMapping;
use Fa\Bundle\AdBundle\Manager\AdRoutingManager;

/**
 * This controller is used for content Print.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class AdPrintResponseBuild
{
    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Print api response.
     *
     * @var array
     */
    private $printApiResponse = array();

    /**
     * Constructor.
     *
     * @param object $container Container instance.
     *
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Init.
     *
     * @param AdPrint $adPrint
     * @param array   $adSolrObjs
     *
     * @return mixed
     */
    public function init(AdPrint $adPrint, $adSolrObjs)
    {
        $ad = $adPrint->getAd();

        $this->buildBasicFieldArray($adPrint, $ad, $adSolrObjs);

        $this->buildClassificationArray($ad);

        $this->buildImageArray($ad, $adSolrObjs);

        $this->buildAdditionalFieldArray($adPrint, $ad);

        $this->buildUserFieldArray($ad);

        return $this->printApiResponse;
    }

    /**
     * Build basic field array.
     *
     * @param AdPrint $adPrint
     * @param Ad      $ad
     * @param array   $adSolrObjs
     */
    protected function buildBasicFieldArray(AdPrint $adPrint, Ad $ad, $adSolrObjs)
    {
        $entityCacheManager = $this->container->get('fa.entity.cache.manager');
        $em                 = $this->container->get('doctrine')->getManager();
        $baseUrl = $this->container->getParameter('base_url');
        
        if (isset($adSolrObjs[$ad->getId()])) {
            $adSolrObj = $adSolrObjs[$ad->getId()];
            $townId = (isset($adSolrObj[AdSolrFieldMapping::MAIN_TOWN_ID]) ? $adSolrObj[AdSolrFieldMapping::MAIN_TOWN_ID] : null);
            $postcode = (isset($adSolrObj[AdSolrFieldMapping::POSTCODE]) ? $adSolrObj[AdSolrFieldMapping::POSTCODE][0] : null);

            $this->printApiResponse[AdPrintFieldMappingInterface::TOWN] = ($townId ? $entityCacheManager->getEntityNameById('FaEntityBundle:Location', $townId) : null);

            $this->printApiResponse[AdPrintFieldMappingInterface::POSTCODE] = $postcode;
        } else {
            $location = $em->getRepository('FaAdBundle:AdLocation')->getLatestLocation($ad->getId());
            $this->printApiResponse[AdPrintFieldMappingInterface::TOWN] = (!empty($location) && !empty($location->getLocationTown())) ? $entityCacheManager->getEntityNameById('FaEntityBundle:Location', $location->getLocationTown()->getId()) : null;
            $this->printApiResponse[AdPrintFieldMappingInterface::POSTCODE] = !empty($location) ? $location->getPostcode() : null;
        }

        $this->printApiResponse[AdPrintFieldMappingInterface::ADREF] = ($ad->getId() ? $ad->getId() : null);

        $this->printApiResponse[AdPrintFieldMappingInterface::DESCRIPTION] = ($ad->getDescription() ? strip_tags($ad->getDescription()) : null);

        $this->printApiResponse[AdPrintFieldMappingInterface::TITLE] = ($ad->getTitle() ? $ad->getTitle() : null);

        $this->printApiResponse[AdPrintFieldMappingInterface::EDITION_CODE] = ($adPrint->getPrintEdition() ? $adPrint->getPrintEdition()->getCode() : null);

        $this->printApiResponse[AdPrintFieldMappingInterface::INSERT_DATE] = ($adPrint->getInsertDate() ? $this->getDate($adPrint->getInsertDate()) : null);

        $this->printApiResponse[AdPrintFieldMappingInterface::PRICE] = ($ad->getPrice() ? $ad->getPrice() : null);

        $this->printApiResponse[AdPrintFieldMappingInterface::CURRENCY] = CommonManager::getCurrencyCode($this->container);

        $this->printApiResponse[AdPrintFieldMappingInterface::DATE_CREATED] = ($ad->getCreatedAt() ? $this->getDate($ad->getCreatedAt()) : null);

        $this->printApiResponse[AdPrintFieldMappingInterface::DATE_MODIFIED] = ($ad->getUpdatedAt() ? $this->getDate($ad->getUpdatedAt()) : null);
        
        $this->printApiResponse[AdPrintFieldMappingInterface::ADVERT_DETAILS_URL] = $baseUrl.$this->container->get('fa_ad.manager.ad_routing')->getDetailUrl($ad);
        
        $upsellArray = array();
        $upsells     = $em->getRepository('FaAdBundle:AdUserPackageUpsell')->getLatestAdPackageUpsell($ad->getId());
        if (count($upsells)) {
            foreach ($upsells as $upsellId => $upsellName) {
                $upsellArray[] = array('ID' => $upsellId, 'Name' => $upsellName);
            }
        }
        $this->printApiResponse[AdPrintFieldMappingInterface::UPSELLS] = $upsellArray;
    }

    /**
     * BuildUser field array.
     *
     * @param Ad $ad
     */
    protected function buildUserFieldArray(Ad $ad)
    {
        $this->printApiResponse[AdPrintFieldMappingInterface::ADVERTISER_ID] = ($ad->getUser() ? $ad->getUser()->getId() : null);

        $this->printApiResponse[AdPrintFieldMappingInterface::EMAIL] = ($ad->getUser() ? $ad->getUser()->getEmail() : null);
        
        if($ad->getUser()->getContactThroughPhone()) {
            $this->printApiResponse[AdPrintFieldMappingInterface::PHONE_NUMBER] = ($ad->getBusinessPhone() ? $ad->getBusinessPhone() : ($ad->getPrivacyNumber() ? $ad->getPrivacyNumber() : ($ad->getUser() ? $ad->getUser()->getPhone() : null)));
        }
   }

    /**
     * Build classification array.
     *
     * @param Ad $ad
     */
    protected function buildClassificationArray(Ad $ad)
    {
        $categoryId = $ad->getCategory()->getId();

        $categoryArray  = $this->container->get('doctrine')->getManager()->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId, false, $this->container);
        $classification = array();

        $i = 0;
        foreach ($categoryArray as $id => $title) {
            $classification[$i]['id']    = $id;
            $classification[$i]['name'] = $title;
            $i++;
        }

        $this->printApiResponse[AdPrintFieldMappingInterface::CATEGORY]       = $classification;
        //$this->printApiResponse[AdPrintFieldMappingInterface::CLASSIFICATION] = $classification;
    }

    /**
     * Build image array.
     *
     * @param Ad    $ad
     * @param array $adSolrObjs
     *
     */
    protected function buildImageArray(Ad $ad, $adSolrObjs)
    {
        $this->printApiResponse[AdPrintFieldMappingInterface::IMAGES] = array();
        if (isset($adSolrObjs[$ad->getId()])) {
            if (isset($adSolrObjs[$ad->getId()][AdSolrFieldMapping::ORD]) && count($adSolrObjs[$ad->getId()][AdSolrFieldMapping::ORD])) {
                foreach ($adSolrObjs[$ad->getId()][AdSolrFieldMapping::ORD] as $index => $val) {
                    $imageUrl = CommonManager::getAdImageUrl($this->container, $adSolrObjs[$ad->getId()][AdSolrFieldMapping::ID], (isset($adSolrObjs[$ad->getId()][AdSolrFieldMapping::PATH][$index]))?$adSolrObjs[$ad->getId()][AdSolrFieldMapping::PATH][$index]:'', (isset($adSolrObjs[$ad->getId()][AdSolrFieldMapping::HASH][$index]))?$adSolrObjs[$ad->getId()][AdSolrFieldMapping::HASH][$index]:'', '800X600', ((isset($adSolrObjs[$ad->getId()][AdSolrFieldMapping::AWS]) && ($adSolrObjs[$ad->getId()][AdSolrFieldMapping::AWS][$index])) ? $adSolrObjs[$ad->getId()][AdSolrFieldMapping::AWS][$index] : 0), (isset($adSolrObjs[$ad->getId()][AdSolrFieldMapping::IMAGE_NAME]) && isset($adSolrObjs[$ad->getId()][AdSolrFieldMapping::IMAGE_NAME][$index])) ? $adSolrObjs[$ad->getId()][AdSolrFieldMapping::IMAGE_NAME][$index] : null);
                    if (!preg_match("~^(?:ht)tps?://~i", $imageUrl)) {
                        $imageUrl = str_replace('//', 'http://', $imageUrl);
                    }
                    $this->printApiResponse[AdPrintFieldMappingInterface::IMAGES][] = $imageUrl;
                }
            }
        } else {
            $images = $this->container->get('doctrine')->getManager()->getRepository('FaAdBundle:AdImage')->getAdImages($ad->getId(), 1);

            if (count($images)) {
                foreach ($images as $image) {
                    $imageUrl = CommonManager::getAdImageUrl($this->container, $ad->getId(), $image->getPath(), $image->getHash(), '800X600', $image->getAws(), $image->getImageName());
                    if (!preg_match("~^(?:ht)tps?://~i", $imageUrl)) {
                        $imageUrl = str_replace('//', 'http://', $imageUrl);
                    }
                    $this->printApiResponse[AdPrintFieldMappingInterface::IMAGES][] = $imageUrl;
                }
            }
        }
    }

    /**
     * Build additional field array.
     *
     * @param AdPrint $adPrint
     * @param Ad      $ad
     */
    protected function buildAdditionalFieldArray(AdPrint $adPrint, Ad $ad)
    {
        $entityCacheManager = $this->container->get('fa.entity.cache.manager');
        $categoryId     = $ad->getCategory()->getId();
        $typeId         = ($ad->getType() ? $ad->getType()->getId() : null);
        $object         = null;
        $rootCategoryId = $this->container->get('doctrine')->getManager()->getRepository('FaEntityBundle:Category')->getRootCategoryId($categoryId, $this->container);
        $className      = CommonManager::getCategoryClassNameById($rootCategoryId, true);
        $repository     = $this->container->get('doctrine')->getManager()->getRepository('FaAdBundle:'.'Ad'.$className);
        $object         = $repository->findOneBy(array('ad' => $ad->getId()));

        $this->printApiResponse[AdPrintFieldMappingInterface::ADDITIONAL_FIELDS] = array();

        if ($object) {
            $metaData = ($object->getMetaData() ? unserialize($object->getMetaData()) : null);
            $paaFields = $this->container->get('doctrine')->getManager()->getRepository('FaAdBundle:PaaField')->getDimensionPaaFieldsWithLabel($categoryId, $this->container);
            $key = 0;
            foreach ($paaFields as $field => $label) {
                $value = $this->container->get('doctrine')->getManager()->getRepository('FaAdBundle:PaaField')->getPaaFieldValue($field, $object, $metaData, $this->container, $className);
                if ($value != null) {
                    $this->printApiResponse[AdPrintFieldMappingInterface::ADDITIONAL_FIELDS][] = array('fieldName' => $label, 'fieldValue' => $value);
                }
            }
        }
        if ($typeId) {
            $this->printApiResponse[AdPrintFieldMappingInterface::ADDITIONAL_FIELDS][] = array('fieldName' => 'Ad type', 'fieldValue' => $entityCacheManager->getEntityNameById('FaEntityBundle:Entity', $typeId));
        }

        //check for private advert
        $isPrivate = false;
        if ($ad->getUser()) {
            $userRole = $this->container->get('doctrine')->getManager()->getRepository('FaUserBundle:User')->getUserRole($ad->getUser()->getId(), $this->container);
            if ($userRole == RoleRepository::ROLE_SELLER) {
                $isPrivate = true;
            }
        }

        $this->printApiResponse[AdPrintFieldMappingInterface::IS_PRIVATE_ADVERT] = $isPrivate;
        $this->printApiResponse[AdPrintFieldMappingInterface::PAID_INSERT]       = $adPrint->getIsPaid();
    }

    /**
     * Get date.
     *
     * @param integer $timestamp
     *
     * @return string
     */
    protected function getDate($timestamp)
    {
        //$date = new \DateTime($timestamp);
        //return $date->format('Y-m-dTH:i:s.uZ');

        return date('Y-m-d H:i:s', $timestamp);
    }
}
