<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Fa\Bundle\UserBundle\Entity\UserReview;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This user review listener allows various business rule to perform after or before user review save
 * such as solr update, remove etc...
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class UserReviewListener
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
     * @param ContainerInterface $container object.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * This method is used to get table name.
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaUserBundle:UserReview')->getTableName();
    }

    /**
     * This method is used to get table name.
     *
     * @return string
     */
    private function getUserTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaUserBundle:User')->getTableName();
    }

    /**
     * Pre remove.
     *
     * @param object $event  LifecycleEventArgs.
     * @param object $entity UserReview object.
     */
    public function preRemove(UserReview $entity, LifecycleEventArgs $event)
    {
        $this->removeUserReviewCache($entity);
    }

    /**
     * Post persist.
     *
     * @param object $event  LifecycleEventArgs.
     * @param object $entity UserReview object.
     */
    public function postPersist(UserReview $entity, LifecycleEventArgs $event)
    {
        $this->removeUserReviewCache($entity);
    }

    /**
     * Post update.
     *
     * @param object $event  LifecycleEventArgs.
     * @param object $entity UserReview object.
     */
    public function postUpdate(UserReview $entity, LifecycleEventArgs $event)
    {
        $this->removeUserReviewCache($entity);
    }

    /**
     * Returns table name.
     *
     * @param removeUserReviewCache $entity
     *
     * @return string
     */
    private function removeUserReviewCache($entity)
    {
        $culture = CommonManager::getCurrentCulture($this->container);
        if ($entity->getUser()) {
            // remove cache for profile exposure.
            CommonManager::removeCache($this->container, $this->getUserTableName().'|getProfileExposureUserDetailForAdList|'.$entity->getUser()->getId().'_'.$culture);
        }
    }
}
