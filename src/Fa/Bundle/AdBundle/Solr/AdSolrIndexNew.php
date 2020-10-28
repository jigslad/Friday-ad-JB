<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Solr;

use Fa\Bundle\AdBundle\Solr\AdSolrFieldMapping;
use Fa\Bundle\PromotionBundle\Repository\UpsellRepository;

/**
 * This service is used to add/update solr index for ads.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdSolrIndexNew implements AdSolrFieldMapping
{
    /**
     * Update solr index by add/update ad document to solr.
     *
     * @param object  $solrClient    Solr client.
     * @param object  $ad            Ad object.
     * @param boolean $isBatchUpdate Used to identify batch update.
     *
     * @return Apache_Solr_Document or boolean
     */
    public function update($solrClient, $ad, $container, $isBatchUpdate = false)
    {
        $solr = null;
        if ($solrClient !== false) {
            $solr = $solrClient->connect();
        }

        if ($solr && $ad && $ad->getCategory()) {
            $root = $container->get('doctrine')->getManager()->getRepository('FaEntityBundle:Category')->getRootNodeByCategory($ad->getCategory()->getId());

            try {
                $repository = $container->get('doctrine')->getManager()->getRepository('FaAdBundle:'.'Ad'.str_replace(' ', '', $root->getName()));

                $document       = $repository->getSolrDocument($ad, $container);
                $updateResponse = $solr->addDocument($document);

                if (!$isBatchUpdate) {
                    $updateResponse = $solr->commit(true);
                    return $updateResponse;
                }

                return true;
            } catch (\Exception $e) {
                echo $e->getMessage();
                return false;
            }
        }

        return false;
    }

    /**
     * Update solr index by add/update image field to solr.
     *
     * @param object  $solrClient    Solr client.
     * @param object  $ad            Ad object.
     * @param boolean $isBatchUpdate Used to identify batch update.
     *
     * @return Apache_Solr_Document or boolean
     */
    public function updateImage($solrClient, $ad, $container, $isBatchUpdate = false)
    {
        $solr = null;
        if ($solrClient !== false) {
            $solr = $solrClient->connect();
        }

        if ($solr && $ad->getCategory()) {
            try {
                $adUpsellValues = $container->get('doctrine')->getManager()->getRepository('FaAdBundle:AdUserPackageUpsell')->getAdPackageUpsellValueArray($ad->getId(), $ad->getCategory()->getId(), $container);

                $repository = $container->get('doctrine')->getManager()->getRepository('FaAdBundle:AdImage');

                // Index images
                $imageLimit = 0;
                if (isset($adUpsellValues[UpsellRepository::UPSELL_TYPE_ADDITIONAL_PHOTO_VALUE])) {
                    $imageLimit = $adUpsellValues[UpsellRepository::UPSELL_TYPE_ADDITIONAL_PHOTO_VALUE];
                }

                $document       = $repository->getSolrDocument($ad, null, $imageLimit);
                $document->addField(self::ID, $ad->getId());
                $updateResponse = $solr->addDocument($document);

                if (!$isBatchUpdate) {
                    $updateResponse = $solr->commit(true);
                    return $updateResponse;
                }

                return true;
            } catch (\Exception $e) {
                return false;
            }
        }

        return false;
    }
}
