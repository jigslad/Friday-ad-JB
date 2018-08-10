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
use Fa\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Gedmo\Sluggable\Util\Urlizer as Urlizer;
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\UserBundle\Entity\UserSite;
use Fa\Bundle\UserBundle\Manager\UserSiteBannerManager;

/**
 * This user listener allows various business rule to perform after or before user save
 * such as solr update, remove etc...
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class UserListener
{
    /**
     * Container service class object.
     *
     * @var object
     */
    private $container;

    /**
     * Business category changed
     *
     * @var object
     */
    private $isBusinessCategoryChanged = false;

    /**
     * Zip changed
     *
     * @var object
     */
    private $isZipChanged = false;

    /**
     * Status changed
     *
     * @var object
     */
    private $isStatusChanged = false;

    /**
     * Role changed
     *
     * @var boolean
     */
    private $isRoleChanged = false;

    /**
     * Half account changed
     *
     * @var boolean
     */
    private $isHalfAccountChanged = false;

    /**
     * Send user to moderation flag
     *
     * @var boolean
     */
    private $sendUserToModerationFlag = false;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container object.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->isBusinessCategoryChanged = false;
    }

    /**
     * This method is used to get table name.
     *
     * @return string
     */
    private function getTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaUserBundle:User')->getTableName();
    }

    /**
     * Get user site table name.
     *
     * @return Ambigous <string, multitype:>
     */
    private function getUserSiteTableName()
    {
        return $this->container->get('doctrine')->getManager()->getClassMetadata('FaUserBundle:UserSite')->getTableName();
    }

    /**
     * Post remove.
     *
     * @param object $event  LifecycleEventArgs.
     * @param object $entity User object.
     */
    public function preRemove(User $entity, LifecycleEventArgs $event)
    {
        $culture = CommonManager::getCurrentCulture($this->container);
        try {
            $this->removeUserSiteFromSolr($entity);
        } catch (\Exception $e) {
        }
        CommonManager::removeCache($this->container, $this->getTableName().'|getProfileExposureUserDetailForAdList|'.$entity->getId().'_'.$culture);
        $this->removeUserCache($entity);
        CommonManager::removeCache($this->container, $this->getTableName().'|getUserRole|'.$entity->getId().'_'.$culture);
        CommonManager::removeCache($this->container, $this->getTableName().'|getUserProfileSlug|'.$entity->getId().'_'.$culture);

        CommonManager::removeCache($this->container, $this->getTableName().'|getUserStatus|'.$entity->getId().'_'.$culture);
        CommonManager::removeCache($this->container, "resource|getResourcesArrayByUserId|".$entity->getId());
        CommonManager::removeCache($this->container, $this->getTableName().'|getUserProfileName|'.$entity->getId().'_'.$culture);
        $userSite = $this->container->get('doctrine')->getManager()->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $entity->getId()));
        if ($userSite) {
            CommonManager::removeCache($this->container, $this->getUserSiteTableName().'|getUserIdBySlug|'.$userSite->getSlug());
        }
    }

    /**
     * Post persist.
     *
     * @param object $event  LifecycleEventArgs.
     * @param object $entity User object.
     */
    public function postPersist(User $entity, LifecycleEventArgs $event)
    {
        $culture = CommonManager::getCurrentCulture($this->container);
        $this->removeUserCache($entity);

        //if business seller then add new entry in user_site
        if ($entity->getRole() && $entity->getRole()->getName() == RoleRepository::ROLE_BUSINESS_SELLER) {
            $userSite = $this->container->get('doctrine')->getManager()->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $entity->getId()));
            if (!$userSite) {
                $userSite = new UserSite();
                $userSite->setUser($entity);
                $this->container->get('doctrine')->getManager()->persist($userSite);
                $this->container->get('doctrine')->getManager()->flush($userSite);
                CommonManager::removeCache($this->container, $this->getTableName().'|getUserProfileSlug|'.$entity->getId().'_'.$culture);
                $this->container->get('doctrine')->getManager()->getRepository('FaUserBundle:User')->getUserProfileSlug($entity->getId(), $this->container);
            }
        }

        //$this->container->get('doctrine')->getManager()->getRepository('FaUserBundle:UserSiteBanner')->updateUserBanner($entity, $this->container);
    }

    /**
     * Pre update.
     *
     * @param object $event  LifecycleEventArgs.
     * @param object $entity User object.
     */
    public function preUpdate(User $entity, LifecycleEventArgs $event)
    {
        $culture = CommonManager::getCurrentCulture($this->container);
        $em = $event->getEntityManager();
        $uow = $em->getUnitOfWork();
        $updatedEntities = $uow->getScheduledEntityUpdates();
        foreach ($updatedEntities as $entity) {
            $changes = $uow->getEntityChangeSet($entity);

            if (array_key_exists('role', $changes)) {
                $this->isRoleChanged = true;
                CommonManager::removeCache($this->container, $this->getTableName().'|getUserRole|'.$entity->getId().'_'.$culture);
                CommonManager::removeCache($this->container, "resource|getResourcesArrayByUserId|".$entity->getId());
                CommonManager::removeCache($this->container, $this->getTableName().'|getUserProfileSlug|'.$entity->getId().'_'.$culture);
                CommonManager::removeCache($this->container, $this->getTableName().'|getUserProfileName|'.$entity->getId().'_'.$culture);
                $userSite = $this->container->get('doctrine')->getManager()->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $entity->getId()));
                if ($userSite) {
                    CommonManager::removeCache($this->container, $this->getUserSiteTableName().'|getUserIdBySlug|'.$userSite->getSlug());
                }
                CommonManager::removeCache($this->container, $this->getTableName().'|getProfileExposureUserDetailForAdList|'.$entity->getId().'_'.$culture);
                CommonManager::removeCache($this->container, $this->getTableName().'|getUserStatus|'.$entity->getId().'_'.$culture);
            }
            if (array_key_exists('first_name', $changes) || array_key_exists('last_name', $changes) || array_key_exists('business_name', $changes)) {
                CommonManager::removeCache($this->container, $this->getTableName().'|getUserProfileSlug|'.$entity->getId().'_'.$culture);
                CommonManager::removeCache($this->container, $this->getTableName().'|getUserProfileName|'.$entity->getId().'_'.$culture);
                $this->sendUserToModerationFlag = true;
            }
            if (array_key_exists('business_name', $changes)) {
                $userSite = $this->container->get('doctrine')->getManager()->getRepository('FaUserBundle:UserSite')->findOneBy(array('user' => $entity->getId()));
                if ($userSite) {
                    CommonManager::removeCache($this->container, $this->getUserSiteTableName().'|getUserIdBySlug|'.$userSite->getSlug());
                }
                // remove cache for profile exposure.
                CommonManager::removeCache($this->container, $this->getTableName().'|getProfileExposureUserDetailForAdList|'.$entity->getId().'_'.$culture);
                $this->sendUserToModerationFlag = true;
            }

            if (array_key_exists('business_category_id', $changes)) {
                $this->isBusinessCategoryChanged = true;
                $this->sendUserToModerationFlag = true;
                // remove cache for profile exposure.
                CommonManager::removeCache($this->container, $this->getTableName().'|getProfileExposureUserDetailForAdList|'.$entity->getId().'_'.$culture);
            }

            if (array_key_exists('status', $changes)) {
                CommonManager::removeCache($this->container, $this->getTableName().'|getUserStatus|'.$entity->getId().'_'.$culture);
                $this->isStatusChanged = true;
            }

            if (array_key_exists('zip', $changes)) {
                $this->isZipChanged = true;
                $this->sendUserToModerationFlag = true;
            }

            if (array_key_exists('is_half_account', $changes)) {
                $this->isHalfAccountChanged = true;
            }
        }
    }

    /**
     * Post update.
     *
     * @param object $event  LifecycleEventArgs.
     * @param object $entity User object.
     */
    public function postUpdate(User $entity, LifecycleEventArgs $event)
    {
        $this->removeUserCache($entity);
        if ($this->isBusinessCategoryChanged) {
            //$this->container->get('doctrine')->getManager()->getRepository('FaUserBundle:UserSiteBanner')->updateUserBanner($entity, $this->container);
        }
        if ($this->isZipChanged || $this->isBusinessCategoryChanged || $this->isStatusChanged) {
            try {
                // solr
                $this->handleSolr($entity);
            } catch (\Exception $e) {
            }
        }

        //update to dotmailer
        if ($this->isBusinessCategoryChanged || $this->isRoleChanged || $this->isHalfAccountChanged) {
            $em = $this->container->get('doctrine')->getManager();
            $dotmailer = $em->getRepository('FaDotMailerBundle:Dotmailer')->findOneBy(array('email' => $entity->getEmail()));
            if ($dotmailer) {
                if ($entity->getRole()) {
                    $dotmailer->setRoleId($entity->getRole()->getId());
                }
                $dotmailer->setBusinessCategoryId($entity->getBusinessCategoryId());
                $dotmailer->setIsHalfAccount($entity->getIsHalfAccount());
                $em->persist($dotmailer);
                $em->flush($dotmailer);
            }
        }

        //send user to moderation
        if ($this->sendUserToModerationFlag && $this->container->get('security.token_storage')->getToken() && CommonManager::isAuth($this->container) && !CommonManager::isAdminLoggedIn($this->container)) {
            exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->get('kernel')->getRootDir().'/console fa:send:business-user-for-moderation --userId='.$entity->getId().' >/dev/null &');
        }
    }

    /**
     * Returns table name.
     *
     * @param removeUserCache $entity
     *
     * @return string
     */
    private function removeUserCache($entity)
    {

    }

    /**
     * Handle solr.
     *
     * @param User $user
     *
     * return boolean
     */
    public function handleSolr(User $user)
    {
        if ($user && $user->getStatus() && $user->getStatus()->getId() == EntityRepository::USER_STATUS_ACTIVE_ID) {
            $this->updateUserSiteToSolr($user);
        } else {
            $this->removeUserSiteFromSolr($user);
        }
    }

    /**
     * Update solr index.
     *
     * @param User $user
     *
     * return boolean
     */
    public function updateUserSiteToSolr(User $user)
    {
        $solrClient = $this->container->get('fa.solr.client.user.shop.detail');
        if (!$solrClient->ping()) {
            return false;
        }

        $userShopDetailSolrIndex = $this->container->get('fa.user.shop.detail.solrindex');
        return $userShopDetailSolrIndex->update($solrClient, $user, $this->container, false);
    }

    /**
     * Remove from solr.
     *
     * @param User $user
     *
     * return boolean
     */
    private function removeUserSiteFromSolr(User $user)
    {
        $solrClient = $this->container->get('fa.solr.client.user.shop.detail');
        if (!$solrClient->ping()) {
            return false;
        }

        $solr = $solrClient->connect();
        $solr->deleteById($user->getId());
        $solr->commit();
        return true;
    }
}
