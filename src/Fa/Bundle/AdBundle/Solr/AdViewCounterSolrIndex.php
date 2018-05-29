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

use Fa\Bundle\AdBundle\Solr\AdViewCounterSolrFieldMapping;

/**
 * This service is used to add/update solr index for ad view counter.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdViewCounterSolrIndex implements AdViewCounterSolrFieldMapping
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

        if ($solr) {
            try {
                $adViewCounterRepository = $container->get('doctrine')->getManager()->getRepository('FaAdBundle:AdViewCounter');
                $document                = $adViewCounterRepository->getSolrDocument($ad, $container);
                $updateResponse          = $solr->addDocument($document);

                if (!$isBatchUpdate) {
                    $updateResponse = $solr->commit();
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
}
