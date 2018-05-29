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
use Fa\Bundle\AdBundle\Entity\AdImage;
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
class AdImageListener
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
     * Post persist.
     *
     * @param AdImage            $adImage
     * @param LifecycleEventArgs $event
     */
    public function postPersist(AdImage $adImage, LifecycleEventArgs $event)
    {
        try {
            $this->updateAdImageCounter($adImage->getAd());
        } catch (\Exception $e) {
        }
    }

    /**
     * Post update.
     *
     * @param AdImage            $adImage
     * @param LifecycleEventArgs $event
     */
    public function postUpdate(AdImage $adImage, LifecycleEventArgs $event)
    {
        try {
            $this->updateAdImageCounter($adImage->getAd());
        } catch (\Exception $e) {
        }
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaAdBundle:AdImage')->getTableName();
    }

    /**
     * Update ad image counter.
     *
     * @param Ad $ad
     */
    private function updateAdImageCounter(Ad $ad)
    {
        $adImageCountArray = $this->container->get('doctrine')->getManager()->getRepository('FaAdBundle:AdImage')->getAdImageCountArrayByAdId(array($ad->getId()));
        $ad->setImageCount((isset($adImageCountArray[$ad->getId()]) ? $adImageCountArray[$ad->getId()] : 0));
        $this->container->get('doctrine')->getManager()->persist($ad);
        $this->container->get('doctrine')->getManager()->flush($ad);
    }
}
