<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ContentBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * Header image repository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class HeaderImageRepository extends EntityRepository
{
    use \Fa\Bundle\CoreBundle\Search\Search;

    const ALIAS = 'hi';

    const LARGE_SCREEN_TYPE_ID  = 1;
    const MEDIUM_SCREEN_TYPE_ID = 2;
    const SMALL_SCREEN_TYPE_ID  = 3;

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
     * Add status filter.
     *
     * @param integer Status entity type.
     */
    protected function addStatusFilter($status = null)
    {
        $this->queryBuilder->andWhere($this->getRepositoryAlias().'.status = '.$status);
    }

    /**
     * Add screen type filter.
     *
     * @param integer $screenType Screen type.
     */
    protected function addScreenTypeFilter($screenType = null)
    {
        $this->queryBuilder->andWhere($this->getRepositoryAlias().'.screen_type = '.$screenType);
    }

    /**
     * Add file name filter.
     *
     * @param string $fileName File name.
     */
    protected function addFileNameFilter($fileName = null)
    {
        $this->queryBuilder->andWhere($this->getRepositoryAlias().'.file_name = :file_name')->setParameter('file_name', $fileName);
    }

    /**
     * Get screen type.
     */
    public function getScreenType()
    {
        return array(
            self::LARGE_SCREEN_TYPE_ID  => 'Large screen',
            self::MEDIUM_SCREEN_TYPE_ID => 'Medium screen',
            self::SMALL_SCREEN_TYPE_ID  => 'Small screen',
        );
    }

    /**
     * Get header image table name.
     *
     * @return Ambigous <string, multitype:>
     */
    private function getHeaderImageTableName()
    {
        return $this->_em->getClassMetadata('FaContentBundle:HeaderImage')->getTableName();
    }

    /**
     * Get header category array.
     *
     * @param object  $container  Container identifier.
     *
     * @return array
     */
    public function getHeaderImageArray($container = null)
    {
        if ($container) {
            $culture     = CommonManager::getCurrentCulture($container);
            $tableName   = $this->getHeaderImageTableName();
            $cacheKey    = $tableName.'|'.__FUNCTION__.'|'.$culture;
            $cachedValue = CommonManager::getCacheVersion($container, $cacheKey);

            if ($cachedValue !== false) {
                return $cachedValue;
            }
        }

        /* $query = $this->getBaseQueryBuilder()
         ->select(self::ALIAS.'.path', self::ALIAS.'.file_name', self::ALIAS.'.phone_file_name', self::ALIAS.'.screen_type', self::ALIAS.'.right_hand_image_url', CategoryRepository::ALIAS.'.id as category_id', LocationRepository::ALIAS.'t.id as town_id', LocationRepository::ALIAS.'d.id as domicile_id')
         ->leftJoin(self::ALIAS.'.category', CategoryRepository::ALIAS)
         ->leftJoin(self::ALIAS.'.location_town', LocationRepository::ALIAS.'t')
         ->leftJoin(self::ALIAS.'.location_domicile', LocationRepository::ALIAS.'d')
         ->andWhere(self::ALIAS.'.status = 1');*/

        $query = $this->getBaseQueryBuilder()
        ->select(self::ALIAS.'.path', self::ALIAS.'.file_name', self::ALIAS.'.phone_file_name', self::ALIAS.'.screen_type', self::ALIAS.'.right_hand_image_url', self::ALIAS.'.override_image', CategoryRepository::ALIAS.'.id as category_id', LocationRepository::ALIAS.'d.id as domicile_id')
        ->leftJoin(self::ALIAS.'.category', CategoryRepository::ALIAS)
        ->leftJoin(self::ALIAS.'.location_domicile', LocationRepository::ALIAS.'d')
        ->andWhere(self::ALIAS.'.status = 1');

        $headeImages       = $query->getQuery()->getArrayResult();
        $headerImagesArray = array();

        if (count($headeImages)) {
            foreach ($headeImages as $index => $headeImage) {
                $headerImagePath = $container->get('kernel')->getRootDir().'/../web/'.$headeImage['path'].'/'.$headeImage['file_name'];
                if (is_file($headerImagePath)) {
                    $key = '';
                    /* if ($headeImage['town_id']) {
                         $key .= $headeImage['town_id'].'_';
                     }*/
                    if ($headeImage['domicile_id']) {
                        $key .= $headeImage['domicile_id'].'_';
                    }
                    if ($headeImage['category_id']) {
                        $key .= $headeImage['category_id'].'_';
                    }

                    if ($headeImage['screen_type']) {
                        $key .= $headeImage['screen_type'].'_';
                    }

                    $key = trim($key, '_');

                    $imageSize = getimagesize($headerImagePath);

                    $headerImagesArray[$key]['override_'.$headeImage['override_image']][$index] = array(
                        'image'       => CommonManager::getSharedImageUrl($container, $headeImage['path'], $headeImage['file_name']),
                        'width'       => $imageSize[0],
                        'height'      => $imageSize[1],
                    );
                    $headerImagesArray['all'][$headeImage['screen_type']]['override_'.$headeImage['override_image']][$index] = array(
                        'image'       => CommonManager::getSharedImageUrl($container, $headeImage['path'], $headeImage['file_name']),
                        'width'       => $imageSize[0],
                        'height'      => $imageSize[1],
                    );

                    if (isset($headeImage['phone_file_name']) && $headeImage['phone_file_name']) {
                        $headerImagesArray[$key]['override_'.$headeImage['override_image']][$index]['phone_image'] = CommonManager::getSharedImageUrl($container, $headeImage['path'], $headeImage['phone_file_name']);
                        $headerImagesArray['all'][$headeImage['screen_type']]['override_'.$headeImage['override_image']][$index]['phone_image'] = CommonManager::getSharedImageUrl($container, $headeImage['path'], $headeImage['phone_file_name']);
                    }
                    
                    if (!empty($headeImage['right_hand_image_url'])) {
                        $headerImagesArray[$key]['override_'.$headeImage['override_image']][$index]['phone_image_url'] = $headeImage['right_hand_image_url'];
                        $headerImagesArray['all'][$headeImage['screen_type']]['override_'.$headeImage['override_image']][$index]['phone_image_url'] = $headeImage['right_hand_image_url'];
                    }
                }
            }
        }
        
        if ($container) {
            CommonManager::setCacheVersion($container, $cacheKey, $headerImagesArray);
        }

        return $headerImagesArray;
    }

    /**
     * Get screen type based on screen width.
     *
     * @param integer $width Width of screen
     *
     * @return integer
     */
    public function getScreenTypeFromResolutionWidth($width)
    {
        if ($width > 1151) {
            return self::LARGE_SCREEN_TYPE_ID;
        } elseif ($width >= 760 && $width <= 1151) {
            return self::MEDIUM_SCREEN_TYPE_ID;
        } elseif ($width <= 759) {
            return self::SMALL_SCREEN_TYPE_ID;
        }
    }

    /**
     * Get header category array.
     *
     * @param object  $container  Container identifier.
     *
     * @return array
     */
    public function getHeaderImageById($id, $container = null)
    {
        if ($container) {
            $culture     = CommonManager::getCurrentCulture($container);
            $tableName   = $this->getHeaderImageTableName();
            $cacheKey    = $tableName.'|'.__FUNCTION__.'|'.$culture;
            $cachedValue = CommonManager::getCacheVersion($container, $cacheKey);

            if ($cachedValue !== false) {
                //return $cachedValue;
            }
        }

        $query = $this->getBaseQueryBuilder()
        ->select(self::ALIAS.'.path', self::ALIAS.'.file_name', self::ALIAS.'.phone_file_name', self::ALIAS.'.screen_type', self::ALIAS.'.right_hand_image_url', CategoryRepository::ALIAS.'.id as category_id', LocationRepository::ALIAS.'t.id as town_id', LocationRepository::ALIAS.'d.id as domicile_id')
        ->leftJoin(self::ALIAS.'.category', CategoryRepository::ALIAS)
        ->leftJoin(self::ALIAS.'.location_town', LocationRepository::ALIAS.'t')
        ->leftJoin(self::ALIAS.'.location_domicile', LocationRepository::ALIAS.'d')
        ->andWhere(self::ALIAS.'.id = '.$id);

        $headerImagesArray = array();
        $headerImages = $query->getQuery()->getArrayResult();
        $headerImagesArray = ($headerImages)?$headerImages[0]:array();

        if ($container) {
            CommonManager::setCacheVersion($container, $cacheKey, $headerImagesArray);
        }

        return $headerImagesArray;
    }
    
    /**
     * get result by image name
     *
     * @param string image name
     *
     * @return string
     */
    public function findByImageName($imageName = '')
    {
        $qb = $this->getBaseQueryBuilder()
        ->select('COUNT('.self::ALIAS.'.id)')
        ->Where(self::ALIAS.'.file_name = :file_name')
        ->setParameter('file_name', $imageName)
        ->orWhere(self::ALIAS.'.phone_file_name = :phone_file_name')
        ->setParameter('phone_file_name', $imageName);
        return $qb->getQuery()->getSingleScalarResult();
    }
    
    /**
     * Get header category array.
     *
     * @param object  $container  Container identifier.
     *
     * @return array
     */
    public function getHeaderImageArrayByCatId($catId, $container = null)
    {
        if ($container) {
            $culture     = CommonManager::getCurrentCulture($container);
            $tableName   = $this->getHeaderImageTableName();
            $cacheKey    = $tableName.'|'.__FUNCTION__.'|'.$culture;
            $cachedValue = CommonManager::getCacheVersion($container, $cacheKey);
            
            if ($cachedValue !== false) {
                //return $cachedValue;
            }
        }
                
        $query = $this->getBaseQueryBuilder()
        ->select(self::ALIAS.'.path', self::ALIAS.'.file_name', self::ALIAS.'.phone_file_name', self::ALIAS.'.screen_type', self::ALIAS.'.right_hand_image_url', self::ALIAS.'.override_image', CategoryRepository::ALIAS.'.id as category_id', LocationRepository::ALIAS.'d.id as domicile_id')
        ->leftJoin(self::ALIAS.'.category', CategoryRepository::ALIAS)
        ->leftJoin(self::ALIAS.'.location_domicile', LocationRepository::ALIAS.'d')
        ->andWhere(self::ALIAS.'.category = :catId')
        ->setParameter('catId', $catId)
        ->andWhere(self::ALIAS.'.status = 1');
        
        $headeImages       = $query->getQuery()->getArrayResult();
        $headerImagesArray = array();
        
        if (count($headeImages)) {
            foreach ($headeImages as $index => $headeImage) {
                $headerBaseImagePath = $container->get('kernel')->getRootDir().'/../web/';
                
                $awsUrl = $headeImage['path'].'/'.$headeImage['file_name'];
                
                $fileexistsInAws = 0;
                if(CommonManager::checkImageExistOnAws($container,$awsUrl)) {
                    $headerBaseImagePath = $container->getParameter('fa.static.aws.url');
                    $fileexistsInAws = 1;
                }
                
                $headerImagePath = $headerBaseImagePath. '/'.$headeImage['path'].'/'.$headeImage['file_name'];
                
                if (is_file($headerImagePath) || $fileexistsInAws==1) {
                    $key = '';
                    /* if ($headeImage['town_id']) {
                     $key .= $headeImage['town_id'].'_';
                     }*/
                    if ($headeImage['domicile_id']) {
                        $key .= $headeImage['domicile_id'].'_';
                    }
                    if ($headeImage['category_id']) {
                        $key .= $headeImage['category_id'].'_';
                    }
                    
                    if ($headeImage['screen_type']) {
                        $key .= $headeImage['screen_type'].'_';
                    }
                    
                    $key = trim($key, '_');
                    
                    $imageSize = getimagesize($headerImagePath);
                    
                    $headerImagesArray[$key]['override_'.$headeImage['override_image']][$index] = array(
                        'image'       => CommonManager::getSharedImageUrl($container, $headeImage['path'], $headeImage['file_name']),
                        'width'       => $imageSize[0],
                        'height'      => $imageSize[1],
                    );
                    $headerImagesArray['all'][$headeImage['screen_type']]['override_'.$headeImage['override_image']][$index] = array(
                        'image'       => CommonManager::getSharedImageUrl($container, $headeImage['path'], $headeImage['file_name']),
                        'width'       => $imageSize[0],
                        'height'      => $imageSize[1],
                    );
                    
                    if (isset($headeImage['phone_file_name']) && $headeImage['phone_file_name']) {
                        $headerImagesArray[$key]['override_'.$headeImage['override_image']][$index]['phone_image'] = CommonManager::getSharedImageUrl($container, $headeImage['path'], $headeImage['phone_file_name']);
                        $headerImagesArray['all'][$headeImage['screen_type']]['override_'.$headeImage['override_image']][$index]['phone_image'] = CommonManager::getSharedImageUrl($container, $headeImage['path'], $headeImage['phone_file_name']);
                    }
                    
                    if (!empty($headeImage['right_hand_image_url'])) {
                        $headerImagesArray[$key]['override_'.$headeImage['override_image']][$index]['phone_image_url'] = $headeImage['right_hand_image_url'];
                        $headerImagesArray['all'][$headeImage['screen_type']]['override_'.$headeImage['override_image']][$index]['phone_image_url'] = $headeImage['right_hand_image_url'];
                    }
                }
            }
        }
        
        if ($container) {
            CommonManager::setCacheVersion($container, $cacheKey, $headerImagesArray);
        }
        
        return $headerImagesArray;
    }
}
