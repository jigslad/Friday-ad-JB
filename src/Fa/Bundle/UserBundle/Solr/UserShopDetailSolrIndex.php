<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Solr;

/**
 * This service is used to add/update solr index for users.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserShopDetailSolrIndex implements UserSolrFieldMapping
{
    /**
     * Update solr index by add/update user document to solr.
     *
     * @param object  $solrClient    Solr client.
     * @param object  $user          User object.
     * @param object  $container     Container object.
     * @param boolean $isBatchUpdate Used to identify batch update.
     *
     * @return Apache_Solr_Document|boolean
     */
    public function update($solrClient, $user, $container, $isBatchUpdate = false)
    {
        $solr = null;
        if ($solrClient !== false) {
            $solr = $solrClient->connect();
        }

        if ($solr) {
            try {
                $document       = $container->get('doctrine')->getManager()->getRepository('FaUserBundle:UserSite')->getSolrDocument($user, $container);
                $updateResponse = $solr->addDocument($document);

                if (!$isBatchUpdate) {
                    $updateResponse = $solr->commit();
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
