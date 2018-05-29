<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\Listener;

use Gedmo\Loggable\Mapping\Event\LoggableAdapter;
use Gedmo\Tool\Wrapper\AbstractWrapper;
use Gedmo\Loggable\LoggableListener as GedmoLoggableListener;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Common\EventArgs;
use Gedmo\Exception\InvalidMappingException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Loggable listener
 *
 * Extends the Gedmo loggable listener to provide some custom functionality.
 *
 *
 * @author Mark Ogilvie <mark.ogilvie@specshaper.com>
 */
class LoggableListener extends GedmoLoggableListener {

    // Token storage to get user
    private $tokenStorage;
    private $container;

    // Injet token storage in the services.yml
    public function __construct(TokenStorageInterface $token, ContainerInterface $container)
    {
        $this->tokenStorage = $token;
        $this->container    = $container;
    }

    /**
     * Create a new Log instance
     *
     * @param string $action
     * @param object $object
     * @param LoggableAdapter $ea
     * @return void
     */
    protected function createLogEntry($action, $object, LoggableAdapter $ea)
    {
        $user = null;
        $om = $ea->getObjectManager();
        if ($this->tokenStorage && $this->tokenStorage->getToken()) {
            $user = $this->tokenStorage->getToken()->getUser();
        }

        if ($user && is_object($user)) {
            $adminRolesArray = $om->getRepository('FaUserBundle:Role')->getRoleArrayByType('A', $this->container);
            $userRole = $om->getRepository('FaUserBundle:User')->getUserRole($user->getId(), $this->container);
            if (in_array($userRole, $adminRolesArray)) {
                $wrapped = AbstractWrapper::wrap($object, $om);
                $meta = $wrapped->getMetadata();
                if ($config = $this->getConfiguration($om, $meta->name)) {
                    $logEntryClass = $this->getLogEntryClass($ea, $meta->name);
                    $logEntryMeta = $om->getClassMetadata($logEntryClass);
                    /** @var \Gedmo\Loggable\Entity\LogEntry $logEntry */
                    $logEntry = $logEntryMeta->newInstance();

                    $logEntry->setAction($action);
                    $logEntry->setUsername($this->username);
                    $logEntry->setObjectClass($meta->name);
                    $logEntry->setLoggedAt();
                    $logEntry->setCreationIp($this->container->get('request_stack')->getCurrentRequest()->getClientIp());

                    // check for the availability of the primary key
                    $uow = $om->getUnitOfWork();
                    if ($action === self::ACTION_CREATE && $ea->isPostInsertGenerator($meta)) {
                        $this->pendingLogEntryInserts[spl_object_hash($object)] = $logEntry;
                    } else {
                        $logEntry->setObjectId($wrapped->getIdentifier());
                    }
                    $newValues = array();
                    if ($action !== self::ACTION_REMOVE && isset($config['versioned'])) {
                        foreach ($ea->getObjectChangeSet($uow, $object) as $field => $changes) {
                            if (!in_array($field, $config['versioned'])) {
                                continue;
                            }
                            $oldValue = $changes[0];
                            $value = $changes[1];
                            if ($meta->isSingleValuedAssociation($field) && ($value || $oldValue)) {
                                if ($value) {
                                    $oid = spl_object_hash($value);
                                    $wrappedAssoc = AbstractWrapper::wrap($value, $om);
                                    $value = $wrappedAssoc->getIdentifier(false);
                                    if (!is_array($value) && !$value) {
                                        $this->pendingRelatedObjects[$oid][] = array(
                                            'log' => $logEntry,
                                            'field' => $field
                                        );
                                    }
                                    $formattedValue = $this->getEntityValue($wrappedAssoc->getObject(), $om);
                                    if (is_array($formattedValue)) {
                                        $value = array();
                                        $value[0] = $formattedValue;
                                    } else {
                                        $value = (string)$changes[1];
                                    }
                                }

                                if ($oldValue) {
                                    $formattedOldValue = $this->getEntityValue($oldValue, $om);
                                    if (is_array($formattedOldValue)) {
                                        $oldValue = array();
                                        $oldValue[0] = $formattedOldValue;
                                    } else {
                                        $oldValue = (string)$changes[0];
                                    }
                                }
                            }

                            if ($field == 'role' || ($field == 'password' && (!$oldValue || $newValues) ) || ($value == $oldValue) || (is_array($value) && is_array($oldValue) && md5(serialize($value)) == md5(serialize($oldValue)))) {
                            } else {
                                if ($field == 'password') {
                                    $oldValue = 'Password Changed';
                                    $value = 'Password Changed';
                                }
                                $newValues[$field]['previous'] = $oldValue;
                                $newValues[$field]['new'] = $value;
                            }
                        }
                        // For each collection add it to the return array in our custom format.
                        foreach ($uow->getScheduledCollectionUpdates() as $col) {
                            $associations = $this->getCollectionChangeSetData($col);
                            $newValues = array_merge($newValues, $associations);
                        }
                        $logEntry->setData($newValues);
                        $logEntry->setMd5(md5(serialize($newValues)));
                    }

                    if ($action == self::ACTION_REMOVE && isset($config['versioned'])) {
                        $newValues = array();
                        $refObj = new \ReflectionObject($object);
                        foreach ($refObj->getProperties() as $key => $property) {
                            if (!in_array($property->getName(), $config['versioned'])) {
                                continue;
                            }
                            $property->setAccessible(true);
                            $removeValues = $this->getEntityValue($property->getValue($object), $om);
                            if ($removeValues) {
                                $newValues[$property->getName()]['previous'] = null;
                                if (is_array($removeValues)) {
                                    $newValues[$property->getName()]['new'][] = $removeValues;
                                } else {
                                    $newValues[$property->getName()]['new'] = (string)$removeValues;
                                }
                            } elseif(!count($newValues)) {
                                $newValues = array();
                            }
                        }
                        $logEntry->setData($newValues);
                        $logEntry->setMd5(md5(serialize($newValues)));
                    }

                    if($action === self::ACTION_UPDATE && 0 === count($newValues)) {
                        return;
                    }

                    $version = 1;
                    if ($action !== self::ACTION_CREATE) {
                        $version = $ea->getNewVersion($logEntryMeta, $object);
                        if (empty($version)) {
                            // was versioned later
                            $version = 1;
                        }
                    }
                    $logEntry->setVersion($version);

                    $this->prePersistLogEntry($logEntry, $object);

                    $om->persist($logEntry);
                    $uow->computeChangeSet($logEntryMeta, $logEntry);
                }
            }
        }
    }

    /**
     * New custom function to get information about changes to entity relationships
     * Use the PersistentCollection methods to extract the info you want.
     *
     * @param PersistentCollection $col
     * @return array
     */
    protected function getCollectionChangeSetData(PersistentCollection $col)
    {
        $newValues = array();
        $fieldName = $col->getMapping()['fieldName'];

        // http://www.doctrine-project.org/api/orm/2.1/class-Doctrine.ORM.PersistentCollection.html
        // $col->toArray() returns the onFlush array of collection items;
        // $col->getSnapshot() returns the prePersist array of collection items
        // $col->getDeleteDiff() returns the deleted items
        // $col->getInsertDiff() returns the inserted items
        // These methods return persistentcollections. You need to process them to get just the title/name
        // of the entity you want.
        // Instead of creating two records, you can create an array of added and removed fields.
        // Use private a newfunction stripCollectionArray to process the entity into the array

        $newValues1 = $this->stripCollectionArray($col->toArray());
        $oldValues = $this->stripCollectionArray($col->getSnapshot());

        if (md5(serialize($newValues1)) != md5(serialize($oldValues))) {
            $newValues[$fieldName]['new'] = $this->stripCollectionArray($col->toArray());
            $newValues[$fieldName]['previous'] = $this->stripCollectionArray($col->getSnapshot());
        }

        return $newValues;
    }

    /**
     * Function to process your entity into the desired format for inserting
     * into the LogEntry
     *
     * @param type $entityArray
     * @return type
     */
    protected function stripCollectionArray($entityArray)
    {
        $returnArr = array();
        foreach ($entityArray as $entity) {
            $returnArr[] = $this->getEntityValue($entity);
        }


        return $returnArr;
    }

    /**
     * Get entity value.
     *
     * @param object  $entity
     *
     * @return string[]|NULL[]
     */
    protected function getEntityValue($entity, $em = null)
    {
        $arr = array();
        if (is_object($entity)) {
            $className = get_class($entity);
            if ($className == 'Doctrine\ORM\PersistentCollection') {
                return array();
            }
            $arr['id'] = $entity->getId();

            if ($em) {
                $className = $em->getClassMetadata(get_class($entity))->getName();
            }
            $arr['class'] = $className;

            if (method_exists($entity, 'getName')) {
                $arr['name'] = $entity->getName();
            } elseif (method_exists($entity, 'getTitle')) {
                $arr['name'] = $entity->getTitle();
            } else {
                $arr['name'] = get_class($entity);
            }

            return $arr;
        }

        return $entity;
    }
}