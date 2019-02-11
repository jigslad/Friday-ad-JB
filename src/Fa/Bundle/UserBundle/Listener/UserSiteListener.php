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
use Fa\Bundle\UserBundle\Entity\UserSite;
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
class UserSiteListener
{
    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Send user to moderation flag
     *
     * @var boolean
     */
    private $sendUserToModerationFlag = false;

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
     * @param UserSite           $userSite
     * @param LifecycleEventArgs $event
     */
    public function preRemove(UserSite $userSite, LifecycleEventArgs $event)
    {
        try {
            // solr
            $this->removeUserSiteFromSolr($userSite);
            $this->removeUserSiteCache($userSite);
        } catch (\Exception $e) {
        }
    }

    /**
     * Post persist.
     *
     * @param UserSite           $userSite
     * @param LifecycleEventArgs $event
     */
    public function postPersist(UserSite $userSite, LifecycleEventArgs $event)
    {
        try {
            // solr
            $this->handleSolr($userSite);
            $this->removeUserSiteCache($userSite);
        } catch (\Exception $e) {
        }
    }

    /**
     * Pre update.
     *
     * @param object $event  LifecycleEventArgs.
     * @param object $entity UserSite object.
     */
    public function preUpdate(UserSite $entity, LifecycleEventArgs $event)
    {
        $culture = CommonManager::getCurrentCulture($this->container);
        $em = $event->getEntityManager();
        $uow = $em->getUnitOfWork();
        $updatedEntities = $uow->getScheduledEntityUpdates();
        foreach ($updatedEntities as $entity) {
            $changes = $uow->getEntityChangeSet($entity);

            if (array_key_exists('website_link', $changes) || array_key_exists('about_us', $changes) || array_key_exists('company_address', $changes) || array_key_exists('company_welcome_message', $changes) || array_key_exists('phone1', $changes) || array_key_exists('phone2', $changes) || array_key_exists('facebook_url', $changes) || array_key_exists('google_url', $changes) || array_key_exists('twitter_url', $changes) || array_key_exists('pinterest_url', $changes) || array_key_exists('youtube_video_url', $changes) || array_key_exists('instagram_url', $changes) || array_key_exists('profile_exposure_category_id', $changes)) {
                $this->sendUserToModerationFlag = true;
            }
        }
    }

    /**
     * Post update.
     *
     * @param UserSite           $userSite
     * @param LifecycleEventArgs $event
     */
    public function postUpdate(UserSite $userSite, LifecycleEventArgs $event)
    {
        try {
            // solr
            $this->handleSolr($userSite);
            $this->removeUserSiteCache($userSite);
        } catch (\Exception $e) {
        }

        //send user to moderation
        if ($this->sendUserToModerationFlag && $this->container->get('security.token_storage')->getToken() && CommonManager::isAuth($this->container) && !CommonManager::isAdminLoggedIn($this->container)) {
            exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project_path').'/console fa:send:business-user-for-moderation --userId='.$userSite->getUser()->getId().' >/dev/null &');
        }
    }

    /**
     * Handle solr.
     *
     * @param UserSite $userSite
     *
     * return boolean
     */
    public function handleSolr(UserSite $userSite)
    {
        if ($userSite->getUser()->getStatus() && $userSite->getUser()->getStatus()->getId() == EntityRepository::USER_STATUS_ACTIVE_ID) {
            $this->updateUserSiteToSolr($userSite);
        } else {
            $this->removeUserSiteFromSolr($userSite);
        }
    }

    /**
     * Update solr index.
     *
     * @param UserSite $userSite
     *
     * return boolean
     */
    public function updateUserSiteToSolr(UserSite $userSite)
    {
        $solrClient = $this->container->get('fa.solr.client.user.shop.detail');
        if (!$solrClient->ping()) {
            return false;
        }

        $userShopDetailSolrIndex = $this->container->get('fa.user.shop.detail.solrindex');
        return $userShopDetailSolrIndex->update($solrClient, $userSite->getUser(), $this->container, false);
    }

    /**
     * Remove UserSite from solr.
     *
     * @param UserSite $userSite
     *
     * return boolean
     */
    private function removeUserSiteFromSolr(UserSite $userSite)
    {
        $solrClient = $this->container->get('fa.solr.client.user.shop.detail');
        if (!$solrClient->ping()) {
            return false;
        }

        $solr = $solrClient->connect();
        $solr->deleteById($userSite->getUser()->getId());
        $solr->commit();
        return true;
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaUserBundle:UserSite')->getTableName();
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getUserTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaUserBundle:User')->getTableName();
    }

    /**
     * Removes cache
     *
     * @return string
     */
    private function removeUserSiteCache($entity)
    {
        $culture = CommonManager::getCurrentCulture($this->container);
        if ($entity->getUser()) {
            CommonManager::removeCache($this->container, $this->getUserTableName().'|getTopbusinessUserDetailForAdList|'.$entity->getUser()->getId().'_'.$culture);
            CommonManager::removeCache($this->container, $this->getUserTableName().'|getTopbusinessUserDetailForAdList|'.$entity->getUser()->getEmail().'_'.$culture);
            CommonManager::removeCache($this->container, $this->getUserTableName().'|getProfileExposureUserDetailForAdList|'.$entity->getUser()->getId().'_'.$culture);
        }
    }
}
