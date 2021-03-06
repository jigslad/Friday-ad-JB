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
class AdSolrIndex implements AdSolrFieldMapping
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
        $solrClientNew = $container->get('fa.solr.client.ad.new');
        if (!$solrClientNew->ping()) {
            return false;
        }

        $this->updateNew($solrClientNew, $ad, $container, $isBatchUpdate);

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

    /**
     * Update solr index by add/update ad document to solr.
     *
     * @param object $solrClient Solr client.
     * @param object $ad Ad object.
     * @param object $container Container
     * @param boolean $isBatchUpdate Used to identify batch update.
     *
     * @return Apache_Solr_Document or boolean
     */
    public function updateNew($solrClient, $ad, $container, $isBatchUpdate = false)
    {
        $solr = null;
        if ($solrClient !== false) {
            $solr = $solrClient->connect();
        }

        if ($solr && $ad && $ad->getCategory()) {
            $root = $container->get('doctrine')->getManager()->getRepository('FaEntityBundle:Category')->getRootNodeByCategory($ad->getCategory()->getId());

            try {
                $repository = $container->get('doctrine')->getManager()->getRepository('FaAdBundle:'.'Ad'.str_replace(' ', '', $root->getName()));

                $document       = $repository->getSolrDocumentNew($ad, $container);
                if (empty($document)) {
                    return false;
                }

                $updateResponse = $solr->addDocument($document);

                if (!$isBatchUpdate) {
                    $updateResponse = $solr->commit(true);
                    return $updateResponse;
                } else {
                    $solr->commit(true);
                }

                return true;
            } catch (\Exception $e) {
                //echo $e->getMessage();
                file_put_contents('/var/www/html/newfriday-ad/web/uploads/indexing-logs.log', $e->getMessage().PHP_EOL, FILE_APPEND);
                return false;
            }
        }

        return false;
    }
}
