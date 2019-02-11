<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ContentBundle\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\ContentBundle\Entity\Banner;
use Fa\Bundle\ContentBundle\Repository\BannerRepository;

/**
 * This class is used to call LifecycleEvent of doctrine.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class BannerListener
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
     * Post remove.
     *
     * @param object $event     LifecycleEventArgs.
     * @param object $objBanner Banner object.
     */
    public function preRemove(Banner $objBanner, LifecycleEventArgs $event)
    {
        $this->removeBannerCache($objBanner);
    }

    /**
     * Post persist.
     *
     * @param object $event     LifecycleEventArgs.
     * @param object $objBanner Banner object.
     */
    public function postPersist(Banner $objBanner, LifecycleEventArgs $event)
    {
        $this->removeBannerCache($objBanner);
    }

    /**
     * Post update.
     *
     * @param object $event     LifecycleEventArgs.
     * @param object $objBanner Banner object.
     */
    public function postUpdate(Banner $objBanner, LifecycleEventArgs $event)
    {
        $this->removeBannerCache($objBanner);
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaContentBundle:Banner')->getTableName();
    }

    /**
     * Remove static page cache.
     *
     * @param object $objBanner Banner object.
     *
     * @return string
     */
    private function removeBannerCache($objBanner)
    {
        if ($objBanner->getBannerPages()) {
            foreach ($objBanner->getBannerPages()->toArray() as $bannerPage) {
                CommonManager::removeCachePattern($this->container, $this->getTableName().'|getBannersArrayByPage|'.$bannerPage->getId().'_*');
                //exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project_path').'/console fa:update:banner:cache generate '.$bannerPage->getId().' >/dev/null &');
            }
        }
    }
}
