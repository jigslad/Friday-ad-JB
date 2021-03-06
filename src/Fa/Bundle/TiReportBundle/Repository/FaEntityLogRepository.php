<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\TiReportBundle\Repository;

use Gedmo\Loggable\Entity\Repository\LogEntryRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\PromotionBundle\Entity\Package;
use Fa\Bundle\CoreBundle\Entity\ConfigRule;
use Fa\Bundle\UserBundle\Entity\User;

/**
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class FaEntityLogRepository extends LogEntryRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS          = 'tifel';

    /**
     * Prepare query builder.
     *
     * @param array $data Array of data.
     *
     * @return Doctrine\ORM\QueryBuilder The query builder
     */
    public function getBaseQueryBuilder()
    {
        return $this->createQueryBuilder(self::ALIAS);
    }


    /**
     * Get ad report query
     *
     * @param array   $searchParams Search parameters array.
     * @param array   $sorter       Sort parameters array.
     * @param object  $container    Container object.
     * @param boolean $isCountQuery Is count query.
     *
     * @return array
     */
    public function getEntityLogReportQuery($searchParams, $sorter, $container, $isCountQuery = false)
    {
        $this->queryBuilder = $this->createQueryBuilder(self::ALIAS)
            ->andWhere(self::ALIAS.'.status = 1');

        if (isset($searchParams['fa_entity_log__loggedAt']) && $searchParams['fa_entity_log__loggedAt']) {
            $this->addLoggedAtFilter($searchParams['fa_entity_log__loggedAt']);
        }

        if (isset($searchParams['fa_entity_log__objectId']) && $searchParams['fa_entity_log__objectId']) {
            $this->addObjectIdFilter($searchParams['fa_entity_log__objectId']);
        }

        if (isset($searchParams['fa_entity_log__objectClass']) && $searchParams['fa_entity_log__objectClass']) {
            $this->addObjectClassFilter($searchParams['fa_entity_log__objectClass']);
        }

        if (isset($searchParams['fa_entity_log__username']) && $searchParams['fa_entity_log__username']) {
            $this->addUsernameFilter($searchParams['fa_entity_log__username']);
        }

        return $this->queryBuilder->getQuery();
    }

    /**
     * Add logged at filter to existing query object.
     *
     * @param string $loggedAt
     */
    protected function addLoggedAtFilter($loggedAt = null)
    {
        $loggedAt = strtotime(str_replace('/', '-', $loggedAt));
        $startDate = CommonManager::getTimeStampFromStartDate(date('Y-m-d', $loggedAt));
        $endDate   = CommonManager::getTimeStampFromEndDate(date('Y-m-d', $loggedAt));
        $this->queryBuilder->andWhere('('.self::ALIAS.'.loggedAt BETWEEN '.$startDate.' AND  '.$endDate.')');
    }

    /**
     * Add object id to existing query object.
     *
     * @param integer $objectId
     */
    protected function addObjectIdFilter($objectId = null)
    {
        $this->queryBuilder->andWhere($this->getRepositoryAlias().'.objectId=:objectId')
                            ->setParameter('objectId', $objectId);
    }

    /**
     * Add object class to existing query object.
     *
     * @param string $objectClass
     */
    protected function addObjectClassFilter($objectClass = null)
    {
        $this->queryBuilder->andWhere($this->getRepositoryAlias().'.objectClass=:objectClass')
                          ->setParameter('objectClass', $objectClass);
    }

    /**
     * Add username to existing query object.
     *
     * @param string $user
     */
    protected function addUsernameFilter($username = null)
    {
        $this->queryBuilder->andWhere($this->getRepositoryAlias().'.username=:username')
                          ->setParameter('username', $username);
    }

    /**
     * return object class array.
     *
     * @param string $key
     */
    public function getObjectClassArray($key = null)
    {
        $objClassArr = array(
            'Fa\Bundle\EmailBundle\Entity\EmailTemplate' => 'Email Template',

            'Fa\Bundle\PromotionBundle\Entity\Package' => 'Package / Subscription',
            'Fa\Bundle\PromotionBundle\Entity\PackageRule' => 'Package Rule',
            'Fa\Bundle\PromotionBundle\Entity\PackagePrint' => 'Package Print',

            'Fa\Bundle\AdBundle\Entity\PaaFieldRule' => 'Paa Field Rule',

            'Fa\Bundle\CoreBundle\Entity\ConfigRule' => 'Configuration Rules',

            'Fa\Bundle\UserBundle\Entity\User' => 'User',

            'Fa\Bundle\AdBundle\Entity\Ad' => 'Ad',
            'Fa\Bundle\AdBundle\Entity\AdForSale' => 'Ad For Sale',
            'Fa\Bundle\AdBundle\Entity\AdAdult' => 'Ad Adult',
            'Fa\Bundle\AdBundle\Entity\AdAnimals' => 'Ad Animals',
            'Fa\Bundle\AdBundle\Entity\AdCommunity' => 'Ad Community',
            'Fa\Bundle\AdBundle\Entity\AdJobs' => 'Ad Jobs',
            'Fa\Bundle\AdBundle\Entity\AdMotors' => 'Ad Motors',
            'Fa\Bundle\AdBundle\Entity\AdProperty' => 'Ad Property',
            'Fa\Bundle\AdBundle\Entity\AdServices' => 'Ad Services',
            'Fa\Bundle\AdBundle\Entity\AdImage' => 'Ad Image',
            'Fa\Bundle\AdBundle\Entity\AdLocation' => 'Ad Location',
            'Fa\Bundle\AdBundle\Entity\AdUserPackage' => 'Ad Package',
        );

        if ($key == null) {
            return $objClassArr;
        } else {
            return isset($objClassArr[$key]) ? $objClassArr[$key] : '';
        }
    }

    /**
     * return object class array.
     *
     * @param string $key
     */
    public function getObjectClassOptionArray($key = null)
    {
        $objClassArr = array(
            'Fa\Bundle\EmailBundle\Entity\EmailTemplate' => 'Email Template',

            'Package' => array(
                'Fa\Bundle\PromotionBundle\Entity\Package' => 'Package / Subscription',
                'Fa\Bundle\PromotionBundle\Entity\PackageRule' => 'Package Rule',
                'Fa\Bundle\PromotionBundle\Entity\PackagePrint' => 'Package Print',
            ),

            'Fa\Bundle\AdBundle\Entity\PaaFieldRule' => 'Paa Field Rule',

            'Fa\Bundle\CoreBundle\Entity\ConfigRule' => 'Configuration Rules',

            'Fa\Bundle\UserBundle\Entity\User' => 'User',

            'Ad' => array(
                'Fa\Bundle\AdBundle\Entity\Ad' => 'Ad',
                'Fa\Bundle\AdBundle\Entity\AdForSale' => 'Ad For Sale',
                'Fa\Bundle\AdBundle\Entity\AdAdult' => 'Ad Adult',
                'Fa\Bundle\AdBundle\Entity\AdAnimals' => 'Ad Animals',
                'Fa\Bundle\AdBundle\Entity\AdCommunity' => 'Ad Community',
                'Fa\Bundle\AdBundle\Entity\AdJobs' => 'Ad Jobs',
                'Fa\Bundle\AdBundle\Entity\AdMotors' => 'Ad Motors',
                'Fa\Bundle\AdBundle\Entity\AdProperty' => 'Ad Property',
                'Fa\Bundle\AdBundle\Entity\AdServices' => 'Ad Services',
                'Fa\Bundle\AdBundle\Entity\AdImage' => 'Ad Image',
                'Fa\Bundle\AdBundle\Entity\AdLocation' => 'Ad Location',
                'Fa\Bundle\AdBundle\Entity\AdUserPackage' => 'Ad Package',
            ),
        );

        if ($key == null) {
            return $objClassArr;
        } else {
            return isset($objClassArr[$key]) ? $objClassArr[$key] : '';
        }
    }

    /**
     * Get entity field label
     *
     * @param string $module    Module name
     * @param string $container Container object
     *
     * @return string
     */
    public function getEntityFieldLabel($module, $objectId, $container)
    {
        $adObj = null;
        $adVerticalObj = null;
        $entityFieldLabelArray = array();
        switch ($module) {
            case 'package':
                $entityFieldLabelArray = $this->getEntityFieldLabelArray('fa_promotion_package_admin', $container);
                break;
            case 'email_template':
                $entityFieldLabelArray = $this->getEntityFieldLabelArray('fa_email_template_email_template_admin', $container);
                break;
            case 'paa_field_rule':
                $entityFieldLabelArray = $this->getEntityFieldLabelArray('fa_ad_paa_field_rule_admin', $container);
                break;
            case 'config_rule':
                $entityFieldLabelArray = $this->getEntityFieldLabelArray('fa_core_config_rule_admin', $container);
                break;
            case 'user':
                $entityFieldLabelArray = $this->getEntityFieldLabelArray('fa_user_user_admin', $container);
                break;
            case 'ad':
            case 'ad_for_sale':
            case 'ad_adult':
            case 'ad_animals':
            case 'ad_community':
            case 'ad_jobs':
            case 'ad_motors':
            case 'ad_property':
            case 'ad_services':
                if ($module == 'ad') {
                    $adObj = $this->_em->getRepository('FaAdBundle:Ad')->findOneBy(array('id' => $objectId));
                } else {
                    $adVerticalObj = $this->_em->getRepository('FaAdBundle:'.CommonManager::camelize($module))->findOneBy(array('id' => $objectId));
                    $adObj = ($adVerticalObj ? $adVerticalObj->getAd() : null);
                }
                if ($adObj) {
                    $categoryId = ($adObj->getCategory() ? $adObj->getCategory()->getId() : null);
                    $entityFieldLabelArray = $this->_em->getRepository('FaAdBundle:PaaField')->getDimensionPaaFieldsWithLabel($categoryId, $container);
                    $commonPaaFields = $this->_em->getRepository('FaAdBundle:PaaField')->getCommonPaaFields();
                    foreach ($commonPaaFields as $paaField) {
                        $entityFieldLabelArray[$paaField->getField()] = $paaField->getLabel();
                    }
                }
                break;
        }

        return array(
                $entityFieldLabelArray,
                $adObj,
                $adVerticalObj,
                ($adVerticalObj ? unserialize($adVerticalObj->getMetaData()) : null)
            );
    }

    /**
     * Get log table name.
     *
     * @return Ambigous <string, multitype:>
     */
    private function getFaEntityLogTableName()
    {
        return $this->_em->getClassMetadata('FaTiReportBundle:FaEntityLog')->getTableName();
    }

    /**
     *
     * @param string $formName  Form name
     * @param string $container Container object
     *
     * @return array
     */
    private function getEntityFieldLabelArray($formName, $container)
    {
        $culture     = CommonManager::getCurrentCulture($container);
        $tableName   = $this->getFaEntityLogTableName();
        $cacheKey    = $tableName.'|'.__FUNCTION__.'|'.$formName.'_'.$culture;
        $cachedValue = CommonManager::getCacheVersion($container, $cacheKey);

        if ($cachedValue !== false) {
            return $cachedValue;
        }

        $entityFieldLabelArray = array();

        $formManager = $container->get('fa.formmanager');
        $entity = null;
        if ($formName == 'fa_promotion_package_admin') {
            $entity = new Package();
            $container->get('ti_package_created_log')->info('package created in Ti entity log getEntityFieldLabelArray function' . $entity->getId());
        } elseif ($formName == 'fa_core_config_rule_admin') {
            $entity = new ConfigRule();
        } elseif ($formName == 'fa_user_user_admin') {
            $entity = new User();
        }
        $form = $formManager->createForm($formName, $entity);

        $children = $form->all();
        foreach ($children as $child) {
            /** @var FormConfigInterface $config */
            $config = $child->getConfig();

            /** @var string $label */
            $label = $config->getOption("label");
            if ($label) {
                $entityFieldLabelArray[$config->getName()] = $label;
            } else {
                $label = str_replace(array('_id', '_'), array('', ' '), $config->getName());
                $label = ucfirst($label);
                $entityFieldLabelArray[$config->getName()] = $label;
            }
        }

        CommonManager::setCacheVersion($container, $cacheKey, $entityFieldLabelArray);

        return $entityFieldLabelArray;
    }

    /**
     * Get module name by object class.
     *
     * @param string $class Class name
     *
     * @return string
     */
    public function getModuleNameByObjectClass($class = null)
    {
        $objClassArr = array(
            'Fa\Bundle\EmailBundle\Entity\EmailTemplate' => 'email_template',
            'Fa\Bundle\PromotionBundle\Entity\Package' => 'package',
            'Fa\Bundle\AdBundle\Entity\PaaFieldRule' => 'paa_field_rule',
            'Fa\Bundle\CoreBundle\Entity\ConfigRule' => 'config_rule',
            'Fa\Bundle\UserBundle\Entity\User' => 'user',
            'Fa\Bundle\AdBundle\Entity\Ad' => 'ad',
            'Fa\Bundle\AdBundle\Entity\AdForSale' => 'ad_for_sale',
            'Fa\Bundle\AdBundle\Entity\AdAdult' => 'ad_adult',
            'Fa\Bundle\AdBundle\Entity\AdAnimals' => 'ad_animals',
            'Fa\Bundle\AdBundle\Entity\AdCommunity' => 'ad_community',
            'Fa\Bundle\AdBundle\Entity\AdJobs' => 'ad_jobs',
            'Fa\Bundle\AdBundle\Entity\AdMotors' => 'ad_motors',
            'Fa\Bundle\AdBundle\Entity\AdProperty' => 'ad_property',
            'Fa\Bundle\AdBundle\Entity\AdServices' => 'ad_services',
            'Fa\Bundle\AdBundle\Entity\AdImage' => 'ad_image',
            'Fa\Bundle\AdBundle\Entity\AdLocation' => 'ad_location',
            'Fa\Bundle\AdBundle\Entity\AdUserPackage' => 'ad_user_package',
        );

        if ($class == null) {
            return $objClassArr;
        } else {
            return isset($objClassArr[$class]) ? $objClassArr[$class] : '';
        }
    }

    /**
     * Remove same entity log by class and action.
     *
     * @param string $class  Entity Class.
     */
    public function removeSameLogByClass($entityLog)
    {
        if ($entityLog) {
            $duplicateLogEntry = $this->findOneBy(array('objectId' => ($entityLog->getObjectId() - 1), 'objectClass' => $entityLog->getObjectClass(), 'md5' => $entityLog->getMd5(), 'status' => 1, 'username' => $entityLog->getUsername(), 'action' => 'remove'));
            //var_dump($duplicateLogEntry);exit;
            if ($duplicateLogEntry) {
                $this->createQueryBuilder(self::ALIAS)
                ->update()
                ->set(self::ALIAS.'.status', 0)
                ->where(self::ALIAS.'.id IN (:ids)')
                ->setParameter('ids', array($entityLog->getId(), $duplicateLogEntry->getId()))
                ->getQuery()
                ->execute();
            }
        }
    }

    /**
     * Merge password change.
     *
     * @param string $class  Entity Class.
     * @param string $action Action.
     *
     * @return array
     */
    public function mergePasswordChange($entityLog)
    {
        if ($entityLog) {
            $logEntries = $this->createQueryBuilder(self::ALIAS)
            ->andWhere(self::ALIAS.'.loggedAt = :loggedAt')
            ->setParameter('loggedAt', $entityLog->getLoggedAt())
            ->andWhere(self::ALIAS.'.objectId = :objectId')
            ->setParameter('objectId', $entityLog->getObjectId())
            ->andWhere(self::ALIAS.'.objectClass = :objectClass')
            ->setParameter('objectClass', $entityLog->getObjectClass())
            ->orderBy(self::ALIAS.'.id', 'ASC')
            ->getQuery()
            ->getResult();

            if (count($logEntries) == 2) {
                $entityLog = $logEntries[0];
                $duplicateLogEntry = $logEntries[1];
                $duplicateData = $duplicateLogEntry->getData();
                $data = $entityLog->getData();
                if (isset($data['password'])) {
                    $data['password']['previous'] = 'Changed';
                    $data['password']['new'] = 'Changed';
                }
                if (isset($duplicateData['password'])) {
                    $duplicateData['password']['previous'] = 'Changed';
                    $duplicateData['password']['new'] = 'Changed';
                }

                $updateSql = 'Update fa_entity_log set data = \''.serialize($data).'\' where id = '.$entityLog->getId();
                $stmt = $this->_em->getConnection()->prepare($updateSql);
                $stmt->execute();

                $this->createQueryBuilder(self::ALIAS)
                ->delete()
                ->where(self::ALIAS.'.id = :id')
                ->setParameter('id', $duplicateLogEntry->getId())
                ->getQuery()
                ->execute();
            }
        }
    }
}
