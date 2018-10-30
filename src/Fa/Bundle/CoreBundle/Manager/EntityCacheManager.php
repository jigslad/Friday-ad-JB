<?php
namespace Fa\Bundle\CoreBundle\Manager;

use Symfony\Component\HttpFoundation\RequestStack;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * Fa\Bundle\CoreBundle\Manager\EntityCacheManager
 *
 * This manager is used to generate entity cache.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd.
 *
 */
class EntityCacheManager
{
    protected $doctrine;
    protected $cacheManager;
    protected $container;

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * Set cache manager.
     *
     * @param Object $cacheManage Cachemanger object
     */
    public function setCacheManager($cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * Set doctrine object.
     *
     * @param Object $doctrine doctrine object
     */
    public function setDoctrine($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Set container object.
     *
     * @param Object $container container object
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * create cached version of entity array
     *
     * @param string $entityClass entity class
     *
     * @return boolean
     */
    public function generateArray($entityClass)
    {
        $entityType    = null;
        $explodeResult = explode(':', $entityClass);

        //check if passed entity is Entity then get all entity type
        if (count($explodeResult) == 2 && isset($explodeResult[1]) && $explodeResult[1] == 'Entity') {
            $entityRepository = $this->doctrine
            ->getRepository($explodeResult[0].':'.$explodeResult[1]);

            $entityRecords = $entityRepository->createQueryBuilder('e')
            ->getQuery()
            ->getResult();

            foreach ($entityRecords as $entityRecord) {
                $this->generateArray($explodeResult[0].':'.$explodeResult[1].':'.$entityRecord->getType());
            }
        } else {
            if (count($explodeResult) == 3 && isset($explodeResult[1]) && $explodeResult[1] == 'Entity') {
                $entityType = $explodeResult[2];
            }
            $repository = $this->doctrine
            ->getRepository($entityClass);

            $query = $repository->createQueryBuilder('e')
            ->select('COUNT(e.id)');

            if ($entityType) {
                $query->add('where', 'e.type = :type')
                ->setParameter('type', $entityType);
            }

            $records = $query->getQuery()
            ->getSingleScalarResult();

            $offset      = 0;
            $limit       = 1000;
            $slab        = ceil($records / $limit);
            $entityArray = array();

            for ($i = 0; $i < $slab; $i++) {
                $subArr = array();
                $offset = ($i * $limit);

                $records = $this->getRecords($offset, $limit, $entityClass, $repository, null, $entityType);

                foreach ($records as $record) {
                    $subArr[$record->getId()] = $record->getName();
                }

                $entityArray = ($entityArray + $subArr);
            }

            if (count($entityArray)) {
                $this->cacheManager->set('entityCacheArray_'.$entityClass, $entityArray);
            }
        }
    }

    /**
     * get records method used to return entity records
     *
     * @param integer $offset      offset
     * @param integer $limit       limit
     * @param string  $entityClass entity class
     * @param object  $repository  repository object
     * @param string  $culture     culture
     * @param string  $entityType  entity type
     *
     * @return Doctrine_Collection
     */
    public function getRecords($offset, $limit, $entityClass, $repository, $culture = null, $entityType = null)
    {
        $query = $repository->createQueryBuilder('e')
        ->setFirstResult($offset)
        ->setMaxResults($limit);

        if ($entityType) {
            $query->add('where', 'e.type = :type')
            ->setParameter('type', $entityType);
        }

        $records = $query->getQuery()
        ->getResult();

        return $records;
    }

    /**
     * get entity cache array by class
     *
     * @param string $entityClass entity class
     *
     * @return array cache chache array
     */
    public function getEntityCacheArray($entityClass)
    {
        $cache = $this->cacheManager->get('entityCacheArray_'.$entityClass);

        if (!$cache) {
            $this->generateArray($entityClass);

            return $this->cacheManager->get('entityCacheArray_'.$entityClass);
        } else {
            return $cache;
        }
    }

    /**
     * Returns entity translated name by ad ids in id name key value pair array
     *
     * @param string $repositoryName name of repository
     * @param mixed  $ids            entity ids
     *
     * @return array id name key value pair array
     */
    public function getEntityNameById($repositoryName, $ids)
    {
        if (!$ids) {
            return false;
        }

        $entityArray = null;
        $isSingle    = false;

        if (!is_array($ids)) {
            $isSingle = true;
            $ids      = (array) $ids;
        }

        foreach ($ids as $id) {
            $cacheKey = 'EntityCacheManager'.'|'.__FUNCTION__.'|'.$repositoryName.'_'.$id;
            $cached   = CommonManager::getCacheVersion($this->container, $cacheKey);

            if ($cached) {
                $entityArray[$id] = $cached;
            } else {
                $entityRepository = $this->doctrine->getRepository($repositoryName);

                $qb = $entityRepository->createQueryBuilder('e')
                    ->select('e.id, e.name')
                    ->where('e.id = :id')
                    ->setParameter('id', $id);

                $result = $qb->getQuery()->getOneOrNullResult();

                if ($result) {
                    $entityArray[$id] = $result['name'];
                } else {
                    $entityArray[$id] = null;
                }

                CommonManager::setCacheVersion($this->container, $cacheKey, $entityArray[$id]);
            }//end if
        }//end foreach

        return ($isSingle == true && is_array($entityArray) ? array_pop($entityArray) : $entityArray);
    }

    /**
     * Returns entity translated name by ad ids in id name key value pair array
     *
     * @param string $repositoryName name of repository
     * @param mixed  $ids            entity ids
     *
     * @return array id name key value pair array
     */
    public function getEntitySlugById($repositoryName, $ids)
    {
        if (!$ids) {
            return false;
        }

        $entityArray = null;
        $isSingle    = false;

        if (!is_array($ids)) {
            $isSingle = true;
            $ids      = (array) $ids;
        }

        foreach ($ids as $id) {
            $cacheKey = 'EntityCacheManager'.'|'.__FUNCTION__.'|'.$repositoryName.'_'.$id;
            $cached   = CommonManager::getCacheVersion($this->container, $cacheKey);

            if ($cached) {
                $entityArray[$id] = $cached;
            } else {
                $entityRepository = $this->doctrine->getRepository($repositoryName);

                $qb = $entityRepository->createQueryBuilder('e')
                ->where('e.id = '.$id);

                if ($repositoryName == 'FaEntityBundle:Location' || $repositoryName == 'FaEntityBundle:Locality') {
                    $qb->select('e.id, e.url as slug');
                } elseif ($repositoryName == 'FaEntityBundle:Category') {
                    $qb->select('e.id, e.full_slug as slug');
                } else {
                    $qb->select('e.id, e.slug as slug');
                }

                $result = $qb->getQuery()->getOneOrNullResult();

                if ($result) {
                    $entityArray[$id] = $result['slug'];
                } else {
                    $entityArray[$id] = null;
                }

                CommonManager::setCacheVersion($this->container, $cacheKey, $entityArray[$id]);
            }//end if
        }//end foreach

        return ($isSingle == true && is_array($entityArray) ? array_pop($entityArray) : $entityArray);
    }

    /**
     * Returns entity translated name by ad ids in id name key value pair array
     *
     * @param string $repositoryName name of repository
     * @param mixed  $ids            entity ids
     *
     * @return array id name key value pair array
     */
    public function getEntityLvlById($repositoryName, $ids)
    {
        if (!$ids) {
            return false;
        }

        $entityArray = null;
        $isSingle    = false;

        if (!is_array($ids)) {
            $isSingle = true;
            $ids      = (array) $ids;
        }

        foreach ($ids as $id) {
            $cacheKey = 'EntityCacheManager'.'|'.__FUNCTION__.'|'.$repositoryName.'_'.$id;
            $cached   = CommonManager::getCacheVersion($this->container, $cacheKey);

            if ($cached) {
                $entityArray[$id] = $cached;
            } else {
                $entityRepository = $this->doctrine->getRepository($repositoryName);

                $qb = $entityRepository->createQueryBuilder('e')
                ->where('e.id = :id')
                ->setParameter('id', $id);

                $qb->select('e.id, e.lvl as lvl');


                $result = $qb->getQuery()->getOneOrNullResult();

                if ($result) {
                    $entityArray[$id] = $result['lvl'];
                } else {
                    $entityArray[$id] = null;
                }

                CommonManager::setCacheVersion($this->container, $cacheKey, $entityArray[$id]);
            }//end if
        }//end foreach

        return ($isSingle == true && is_array($entityArray) ? array_pop($entityArray) : $entityArray);
    }

    /**
     * Returns entity id by name
     *
     * @param string $repositoryName name of repository
     * @param string name
     *
     * @return array id name key value pair array
     */
    public function getEntityIdByName($repositoryName, $name)
    {
        if (!$name) {
            return false;
        }

        $cacheKey = 'EntityCacheManager'.'|'.__FUNCTION__.'|'.$repositoryName.'_'.$name;
        $cached   = CommonManager::getCacheVersion($this->container, $cacheKey);

        if ($cached) {
            return $cached;
        } else {
            $entityRepository = $this->doctrine->getRepository($repositoryName);
            $qb = $entityRepository->createQueryBuilder('e')
            ->select('e.id, e.name')
            ->where('e.name = :name')
            ->setMaxResults(1)
            ->setParameter('name', $name);

            $result = $qb->getQuery()->getOneOrNullResult();

            if ($result) {
                CommonManager::setCacheVersion($this->container, $cacheKey, $result['id']);
                return $result['id'];
            }
        }//end if

        return null;
    }
}
