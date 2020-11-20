<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Fa\Bundle\AdBundle\Entity\Ad;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;

/**
 * This class is used to call LifecycleEvent of doctrine.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class AdListener
{
    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Constructor.
     *
     * @param object $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Pre remove.
     *
     * @param Ad                 $ad
     * @param LifecycleEventArgs $event
     */
    public function preRemove(Ad $ad, LifecycleEventArgs $event)
    {
        try {
            // solr
            //$this->removeAdFromSolr($ad);
        } catch (\Exception $e) {
        }
    }

    /**
     * Post persist.
     *
     * @param Ad                 $ad
     * @param LifecycleEventArgs $event
     */
    public function postPersist(Ad $ad, LifecycleEventArgs $event)
    {
        try {
            // solr
            //$this->handleSolr($ad);
        } catch (\Exception $e) {
        }
    }

    /**
     * Post update.
     *
     * @param Ad                 $ad
     * @param LifecycleEventArgs $event
     */
    public function postUpdate(Ad $ad, LifecycleEventArgs $event)
    {
        try {
            // solr
            $this->handleSolr($ad);
        } catch (\Exception $e) {
        }
    }

    /**
     * Handle solr.
     *
     * @param Ad $ad
     *
     * return boolean
     */
    public function handleSolr(Ad $ad)
    {
        if ($ad->getSkipSolr() != 1) {
            if (($ad->getStatus()->getId() == EntityRepository::AD_STATUS_LIVE_ID ||
                $ad->getStatus()->getId() == EntityRepository::AD_STATUS_SOLD_ID ||
                $ad->getStatus()->getId() == EntityRepository::AD_STATUS_EXPIRED_ID) &&
                $ad->getIsBlockedAd() != 1
            ) {
                $this->updateAdToSolr($ad);
            } else {
                $this->removeAdFromSolr($ad);
            }
        }
    }

    /**
     * Update solr index.
     *
     * @param Ad $ad
     *
     * return boolean
     */
    public function updateAdToSolr(Ad $ad)
    {
        $solrClient = $this->container->get('fa.solr.client.ad');
        if (!$solrClient->ping()) {
            return false;
        }

        $adSolrIndex = $this->container->get('fa.ad.solrindex');
        $adSolrIndex->update($solrClient, $ad, $this->container, false);

        $solrClientNew = $this->container->get('fa.solr.client.ad.new');
        if (!$solrClientNew->ping()) {
            return false;
        }

        return $adSolrIndex->updateNew($solrClientNew, $ad, $this->container, false);
    }

    /**
     * Remove ad from solr.
     *
     * @param Ad $ad
     *
     * return boolean
     */
    private function removeAdFromSolr(Ad $ad)
    {
        $solrClient = $this->container->get('fa.solr.client.ad');
        if (!$solrClient->ping()) {
            return false;
        }

        $solr = $solrClient->connect();
        $solr->deleteById($ad->getId());
        $solr->commit(true);

        $solrClientNew = $this->container->get('fa.solr.client.ad.new');
        if (!$solrClientNew->ping()) {
            return false;
        }

        $solrNew = $solrClientNew->connect();
        $solrNew->deleteById($ad->getId());
        $solrNew->commit(true);

        return true;
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaAdBundle:Ad')->getTableName();
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getCategoryTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaEntityBundle:Category')->getTableName();
    }
}
