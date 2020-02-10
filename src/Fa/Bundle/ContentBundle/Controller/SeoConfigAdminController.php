<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ContentBundle\Controller;

use Fa\Bundle\EntityBundle\Entity\Entity;
use Fa\Bundle\EntityBundle\Entity\Category;
use Fa\Bundle\LexikTranslationBundle\Util\DataGrid\DataGridFormatter;
use function GuzzleHttp\headers_from_lines;
use Fa\Bundle\ContentBundle\Entity\SeoConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\EntityBundle\Entity\CategoryDimension;
use Fa\Bundle\AdminBundle\Controller\CrudController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Fa\Bundle\AdBundle\EventListener\AdRequestListener;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;
use Fa\Bundle\ContentBundle\Repository\SeoConfigRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryDimensionRepository;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\AdBundle\Repository\RedirectsRepository;
use Fa\Bundle\AdBundle\Entity\Redirects;
/**
 * This controller is used for static page management.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class SeoConfigAdminController extends CrudController implements ResourceAuthorizationController
{
    protected $shortList = false;

    /**
     * Get table name.
     *
     * @return object
     */
    protected function getTableName()
    {
        return 'seo_config';
    }

    /**
     * Get the main config view.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);

        if ($request->get('respond') == 'json' && ($configSlug = $request->get('config'))) {
            $data = CommonManager::data_get($this->seoConfig(false), "{$configSlug}.data", []);
            return new JsonResponse($data);
        }

        $seoConfigs = $this->seoConfig(true, $request->get('short_list', true));

        if (!empty($CrawlConfig = CommonManager::data_get($seoConfigs, SeoConfigRepository::CRAWL_CONFIG . '.data', []))) {
            $crawlConfigs = json_decode(json_decode($CrawlConfig), true, 512);
            $seoConfigs[SeoConfigRepository::CRAWL_CONFIG]['data'] = array_map(function ($crawlConfig) {
                return [
                    'category' => CommonManager::data_get($crawlConfig, 'category'),
                    'dimension' => CommonManager::data_get($crawlConfig, 'dimension'),
                    'region' => filter_var(CommonManager::data_get($crawlConfig, 'region', false), FILTER_VALIDATE_BOOLEAN),
                    'county' => filter_var(CommonManager::data_get($crawlConfig, 'county', false), FILTER_VALIDATE_BOOLEAN),
                    'town' => filter_var(CommonManager::data_get($crawlConfig, 'town', false), FILTER_VALIDATE_BOOLEAN),
                ];
            }, $crawlConfigs);
        }

        if (!empty($moreFilters = CommonManager::data_get($seoConfigs, SeoConfigRepository::LHS_FILTER_ORDER))) {
            $entityOrder = json_decode(CommonManager::data_get($moreFilters, 'data'));

            if(!empty($entityOrder)) {
                $entityOrder = json_decode($entityOrder, true, 512);
            }

            $entityOrder = $this->getEntities(explode(',', CommonManager::data_get($entityOrder, '_more_filter_entities_', '')));
            $seoConfigs[SeoConfigRepository::LHS_FILTER_ORDER]['_more_filter_entities_'] = json_encode(json_encode($entityOrder));
        }

        $parameters = [
            'heading'    => $this->get('translator')->trans('General SEO Config & Redirects'),
            'configs'    => $seoConfigs,
            'dimensions' => $this->getAllDimensions(),
            'categories' => $this->getCategories(),
            'shortList'  => $this->shortList,
        ];

        return $this->render('FaContentBundle:SeoConfigAdmin:index.html.twig', $parameters);
    }

    /**
     * Get all seo configs.
     *
     * @param bool $jsonEncoded
     * @param bool $shortList
     * @return array
     */
    protected function seoConfig($jsonEncoded = true, $shortList = false)
    {
        /** @var SeoConfigRepository $seoConfigRepository */
        $seoConfigRepository = $this->getRepository('FaContentBundle:SeoConfig');

        $seoConfigs = [];
        $data = $seoConfigRepository->getBaseQueryBuilder()->getQuery()->getArrayResult();

        foreach ($data as $config) {
            $type = CommonManager::data_get($config, 'type');
            $jsonStringData = $config['data'];

            if (filter_var($shortList, FILTER_VALIDATE_BOOLEAN)) {
                $arrayData = json_decode($jsonStringData, true, 512);
                $dataLength = count($arrayData);
                if ($dataLength > 200) {
                    $arrayData = array_slice($arrayData, 0, 200);
                    $jsonStringData = json_encode($arrayData);
                    p("{$type} count reduced to 200. Actual Count: {$dataLength}");
                    $this->shortList = true;
                }
            }

            $config['data'] = $jsonEncoded ? json_encode($jsonStringData) : json_decode($jsonStringData, true, 512);
            $seoConfigs[$type] = $config;
        }

        return $seoConfigs;
    }

    /**
     * Get all categories.
     *
     * @return array
     */
    protected function getCategories()
    {
        /** @var Category[] $allCategories */
        $allCategories = $this->getRepository('FaEntityBundle:Category')->findBy([
            'lvl' => [1,2]
        ]);

        $categories = [];

        $categories[CategoryRepository::ALL_ID]['parent'] = [
            'id' => CategoryRepository::ALL_ID,
            'name' => 'All Categories',
            'is_main' => true,
        ];

        foreach ($allCategories as $category) {
            if (!empty($parent = $category->getParent()) && $parent->getSlug() != CategoryRepository::CATEGORY_ROOT_SLUG && $parent->getStatus()) {
                $categories[$parent->getId()]['children'][] = [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                    'is_main' => false,
                ];
            } elseif ($category->getSlug() != CategoryRepository::CATEGORY_ROOT_SLUG && $parent->getStatus()) {
                $categories[$category->getId()]['parent'] = [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                    'is_main' => true,
                ];
            }
        }

        return $categories;
    }

    /**
     * Get all Active Dimensions
     *
     * @return array
     */
    protected function getAllDimensions()
    {
        $dims = [];
        /** @var CategoryDimension[] $allDimensions */
        $allDimensions = $this->getRepository('FaEntityBundle:CategoryDimension')->findBy([
            'status' => 1
        ]);

        foreach ($allDimensions as $dimension) {
            revert_slug(!empty($name = $dimension->getName()) ? $name : $dimension->getKeyword());
            revert_slug(!empty($category = $dimension->getCategory()) ? $category->getName() : 'null');
            $dims[] = [
                'id' => $dimension->getId(),
                'name' => $name,
                'category'=>$category,
                'status' => $dimension->getIsSearchable(),
                'Crawlable' => $dimension->getIsNotCrawlable()
            ];
        }
        return $dims;
    }

    /**
     * Get all Active Dimensions
     *
     * @param array $ids
     * @return array
     */
    protected function getEntities($ids = [])
    {
        $entities = [];
        /** @var Entity[] $allEntities */
        $allEntities = $this->getRepository('FaEntityBundle:Entity')->findBy([
            'status' => 1,
            'id' => $ids
        ]);

        foreach ($allEntities as $entity) {

            $entities[$entity->getId()] = [
                'id' => $entity->getId(),
                'text' => $entity->getName(),
            ];
        }

        $values = [];
        foreach ($ids as $id) {

            if (!empty($data = CommonManager::data_get($entities, $id))) {
                $values[] = $data;
            }
        }

        return $values;
    }

    /**
     * Add Meta Tag Info
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function metaRobotsAction(Request $request)
    {
        $data = $request->get('data');

        $data = $this->processBulkUpload($request, $data, SeoConfigRepository::META_ROBOTS);

        return new JsonResponse([
            'status' => 1,
            'data' => $data
        ]);
    }

    /**
     * Add Max Dim Rules data.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function maxDimRulesAction(Request $request)
    {
        $data = $request->get('data');

        $this->saveData(SeoConfigRepository::MAX_DIM_RULES, $data);

        return new JsonResponse([
            'status' => 1,
            'data' => $data
        ]);
    }

    /**
     * Add Motors Redirects data.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */

    public function motorsRedirectsAction(Request $request)
    {
        $data = $request->get('data');

        $data = $this->processMotorsRedirectBulkUpload($request, $data);

        return new JsonResponse([
            'status' => 1,
            'data' => $data
        ]);
    }
    public function processMotorsRedirectBulkUpload($request, $data){
        $type =strtolower($request->get('type'));
        $format = strtolower($request->get('format'));
        if ($type == 'bulk') {
            if ($format && $format == 'file') {
                $data = $this->getFileData($data);
            }
            $this->insertNewMotorsRedirectsData($data);
        }
        return $this->dataTableRedirectConfigAction($request);
    }
    public function insertNewMotorsRedirectsData($motorsData){
        foreach ($motorsData as $singleData) {
            $actualData = explode(',',$singleData);
            if(!empty($actualData)) {
                $dataArray['old'] = $actualData[0];
                $dataArray['new'] = $actualData[1];
                $dataArray['is_location'] = ($actualData[2] == 'location')?1:0;
            }
            $getExistData = $this->getRepository('FaAdBundle:Redirects')->getRedirectByArray($dataArray);
            if(empty($getExistData)) {
                $redirects = new Redirects;
                $redirects->setOld($dataArray['old']);
                $redirects->setNew($dataArray['new']);
                $redirects->setIsLocation($dataArray['is_location']);
                $this->updateEntity($redirects);
            }
        }
    }

    /**
     * Add Redirects data.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */

    public function redirectsAction(Request $request)
    {
        $data = $request->get('data');

        $data = $this->processRedirectBulkUpload($request, $data);

        return new JsonResponse([
            'status' => 1,
            'data' => $data
        ]);
    }
    
    public function processRedirectBulkUpload($request, $data) 
    {
        $type =strtolower($request->get('type'));
        $format = strtolower($request->get('format'));
        if ($type == 'bulk') {
            if ($format && $format == 'file') {
                $data = $this->getFileData($data);
            }
            if(!is_array($data)){
                $data = array($data);
            }
            $this->insertNewRedirectsData($data);
        }
        return $this->dataTableRedirectConfigAction($request);
    }
    public function insertNewRedirectsData($data){
        foreach ($data as $singleData) {
            $actualData = explode(',',$singleData);
            if(!empty($actualData)) {
                $dataArray['old'] = $actualData[0];
                $dataArray['new'] = $actualData[1];
                $dataArray['is_location'] = ($actualData[2] == 'location')?1:0;
            }
            $getExistData = $this->getRepository('FaAdBundle:Redirects')->getRedirectByArray($dataArray);
            if(empty($getExistData)) {
                $redirects = new Redirects;
                $redirects->setOld($dataArray['old']);
                $redirects->setNew($dataArray['new']);
                $redirects->setIsLocation($dataArray['is_location']);
                $this->updateEntity($redirects);
            }
        }
    }

    /**
     * Updates the un-necessary query params.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function unnecessaryQueryParamsAction(Request $request)
    {
        $data = $request->get('data');

        $data = $this->processBulkUpload($request, $data, SeoConfigRepository::UNNECESSARY_QUERY_PARAMS);

        return new JsonResponse([
            'status' => 1,
            'data' => $data
        ]);
    }

    /**
     * Updates the un-necessary query params.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function URLRightTrimAction(Request $request)
    {
        $data = $request->get('data');

        $data = $this->processBulkUpload($request, $data, SeoConfigRepository::URL_RIGHT_TRIM);

        return new JsonResponse([
            'status' => 1,
            'data' => $data
        ]);
    }

    /**
     * Updates the un-necessary query params.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function unnecessaryODataParamsAction(Request $request)
    {
        $data = $request->get('data');

        $data = $this->processBulkUpload($request, $data, SeoConfigRepository::UNNECESSARY_ODATA_PARAMS);

        return new JsonResponse([
            'status' => 1,
            'data' => $data
        ]);
    }

    /**
     * Update the main category aliases.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function categoryAliasAction(Request $request)
    {
        $data = $request->get('data');

        $data = $this->processBulkUpload($request, $data, SeoConfigRepository::CATEGORY_ALIAS);

        return new JsonResponse([
            'status' => 1,
            'data' => $data
        ]);
    }

    /**
     * Updates the filter aliases - includes categories and entities.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function filterAliasAction(Request $request)
    {
        $data = $request->get('data');

        $data = $this->processBulkUpload($request, $data, SeoConfigRepository::FILTER_ALIAS);

        return new JsonResponse([
            'status' => 1,
            'data' => $data
        ]);
    }

    /**
     * Updates the location aliases.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function locationAliasAction(Request $request)
    {
        $data = $request->get('data');

        $data = $this->processBulkUpload($request, $data, SeoConfigRepository::LOCATION_ALIAS);

        return new JsonResponse([
            'status' => 1,
            'data' => $data
        ]);
    }

    /**
     * Updates the region aliases.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function regionAliasAction(Request $request)
    {
        $data = $request->get('data');

        $data = $this->processBulkUpload($request, $data, SeoConfigRepository::REGION_ALIAS);

        return new JsonResponse([
            'status' => 1,
            'data' => $data
        ]);
    }

    /**
     * Updates sub category aliases.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function allCategoryAliasAction(Request $request)
    {
        $data = $request->get('data');

        $data = $this->processBulkUpload($request, $data, SeoConfigRepository::ALL_CATEGORY_ALIAS);

        return new JsonResponse([
            'status' => 1,
            'data' => $data
        ]);
    }

    /**
     * Updates the 'for-sale' url excluded categories.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function forSaleExclusionsAction(Request $request)
    {
        $data = $request->get('data');

        $this->saveData(SeoConfigRepository::FOR_SALE_EXCLUSION, $data);

        return new JsonResponse([
            'status' => 1,
            'data' => $data
        ]);
    }

    /**
     * Get sitemap stats
     *
     * @return JsonResponse
     */
    public function sitemapStatAction()
    {
        $process = CommonManager::array_first(explode("\n", shell_exec("ps -aux | grep sitemap")));

        if (substr_exist($process, 'url_type')) {
            $urlType = CommonManager::array_first(explode(' ', substr($process, strpos($process, 'url_type=') + strlen('url_type='))));
            $offset = CommonManager::array_first(explode(' ', substr($process, strpos($process, 'offset=') + strlen('offset='))));

            return new JsonResponse([
                'stat' => [
                    'raw' => $process,
                    'url_type' => $urlType,
                    'page' => $offset,
                ],
                'files' => $this->sitemapFiles()
            ]);
        }

        return new JsonResponse(
            [
                'stat' => [],
                'files' => $this->sitemapFiles()
            ]
        );
    }

    /**
     * Act on sitemaps.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sitemapUpdateAction(Request $request)
    {
        $type = $request->get('type');
        $command = '';

        if ($type == 'restart') {
            $command = $this->runCommand("--init_flag=-1");
        } elseif ($type == 'resume') {
            $command = $this->runCommand();
        }

        return new JsonResponse($command);
    }

    /**
     * Get current settings for
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function categoryDimensionAction(Request $request)
    {
        $categoryId = $request->get('category_id');

        if (empty($categoryId)) {
            return new JsonResponse([
                'status' => 1,
                'data' => ''
            ]);
        }

        $filterOrderConfig = json_decode(json_decode(CommonManager::data_get($this->seoConfig(), SeoConfigRepository::LHS_FILTER_ORDER . '.data', ''), true, 512), true, 512);

        $config = CommonManager::data_get($filterOrderConfig, "category_id_{$categoryId}", '');

        return new JsonResponse([
            'status' => 1,
            'order' => $config
        ]);
    }

    /**
     * Save LHS Filter option for a category.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function LHSFilterOrderAction(Request $request)
    {
        $order = (array) $request->get('data', []);
        $categoryId = $request->get('category_id');

        $filterOrderConfig = CommonManager::json_decode(json_decode(data_get($this->seoConfig(), SeoConfigRepository::LHS_FILTER_ORDER . '.data', ''), true, 512), true, 512);

        if (!empty($categoryId)) {
            $filterOrderConfig["category_id_{$categoryId}"] = implode(',', $order);
        } else {
            $moreFilterEntities = array_wrap(CommonManager::data_get($order, '_more_filters_'));
            $filterOrderConfig["_more_filter_entities_"] = implode(',', $moreFilterEntities);
        }

        $this->saveData(SeoConfigRepository::LHS_FILTER_ORDER, $filterOrderConfig);

        return new JsonResponse([
            'status' => 1,
            'data' => $filterOrderConfig
        ]);
    }

    /**
     * Updates the category settings.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function categorySettingsAction(Request $request)
    {
        $data =  [
            'max_sub_categories' => $request->get('max_sub_category'),
            'min_ad_count' => $request->get('min_ad_count'),
            'nested_header_search' => $request->get('nested_header_search')
        ];

        $this->saveData(SeoConfigRepository::SUB_CATEGORY_CONFIG, $data);

        $this->container->get('session')
            ->getFlashBag()
            ->add('success', "Sub Category config is saved.");

        return new JsonResponse($data);
    }

    /**
     * Updates the category settings.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function keywordSearchSettingsAction(Request $request)
    {
        $data =  [
            'search_everywhere' => $request->get('search_everywhere', false),
            'search_ad_title' => $request->get('search_ad_title', false),
            'search_ad_details' => $request->get('search_ad_details', false),
            'search_ad_category' => $request->get('search_ad_category', false),
        ];

        $this->saveData(SeoConfigRepository::KEYWORD_SEARCH_CONFIG, $data);

        $this->container->get('session')
            ->getFlashBag()
            ->add('success', "Header Keyword Search config is saved.");

        return new JsonResponse($data);
    }

    /**
     * Get the available sitemap files
     *
     * @return array
     */
    protected function sitemapFiles()
    {
        $basedir = $this->container->getParameter('nmp.site.basedir');
        $sitemapFiles = array_filter(scandir(str_replace('web/uploads', 'web/' . $basedir . 'uploads', StaticPageController::SITEMAP_DIRECTORY)), function ($sitemapFileName) {
            return substr_exist($sitemapFileName, '_sitemap_') && substr_exist($sitemapFileName, '.xml');
        });

        return implode('<br>&nbsp;', $sitemapFiles);
    }

    /**
     * @param $type
     * @return string
     */
    protected function runCommand($type = '')
    {
        $website= $this->container->getParameter('nmp.site.name');
        $command = $this->container->getParameter('fa.php.path') . ' ' .
            $this->container->get('kernel')->getRootDir()
            . "/console fa:generate:category:sitemap {$type} --website={$website} &> /dev/null";

        passthru($command, $returnVar);

        return $command;
    }

    /**
     * Saves the data for the given type.
     *
     * @param $type
     * @param $data
     * @return bool
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function saveData($type, $data)
    {
        $seoConfig = $this->findSeoConfig($type);

        if (empty($seoConfig)) {
            $seoConfig = new SeoConfig();
            $seoConfig->setType($type);
            $seoConfig->setStatus(true);
        }

        $seoConfig->setData(!empty($data) ? $data : []);
        $this->updateEntity($seoConfig);

        return true;
    }

    /**
     * Find one Seo Config by the given type.
     *
     * @param $type
     * @return null|SeoConfig
     */
    protected function findSeoConfig($type)
    {
        /** @var SeoConfigRepository $seoConfigRepository */
        $seoConfigRepository = $this->getRepository('FaContentBundle:SeoConfig');

        /** @var SeoConfig $seoConfig */
        return $seoConfigRepository->findOneBy([
            'type' => $type
        ]);
    }

    /**
     * Updates the given entity to table.
     *
     * @param $entity
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function updateEntity($entity)
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush($entity);
    }

    /**
     * Suggest the input based on the type and keyword.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function suggestAction(Request $request)
    {
        $type = $request->get('type');
        $keyword = $request->get('keyword');
        $conditions = $this->retrieveConditions($request->get('conditions', ''));

        if (!in_array($type, ['category', 'location', 'region', 'category-entity', 'entity'])) {
            return new JsonResponse([]);
        }

        switch ($type) {
            case 'category': return $this->returnJson($this->findCategoryBySlug($keyword, $conditions));
            case 'location' : return $this->returnJson(array_merge($this->findLocationBySlug($keyword, $conditions), $this->findLocalityBySlug($keyword)));
            case 'region' : return $this->returnJson($this->findRegionBySlug($keyword));
            case 'category-entity' : return $this->returnJson(array_merge($this->findCategoryBySlug($keyword, $conditions), $this->findEntityBySlug($keyword)));
            case 'entity' : return $this->returnJson($this->findEntityBySlug($keyword, $conditions));
        }
    }

    /**
     * Suggest the input based on the type and keyword.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function statusToggleAction(Request $request)
    {
        $type = $request->get('type');
        $status = boolval($request->get('status'));

        $this->toggleStatus($type, $status);

        return new JsonResponse([
            'status' => $status,
            'type' => $type,
        ]);
    }

    /**
     * Toggle status of given seo config type.
     *
     * @param $type
     * @param $status
     * @return bool
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function toggleStatus($type, $status)
    {
        $seoConfig = $this->findSeoConfig($type);

        if ($seoConfig) {
            $seoConfig->setStatus($status);
            $this->updateEntity($seoConfig);
        }

        return $seoConfig
            ? $this->findSeoConfig($type)->getStatus()
            : !$status;
    }

    /**
     * Finds the categories matching the slug.
     *
     * @param $slug
     * @param array $conditions
     * @return array|mixed
     */
    protected function findCategoryBySlug($slug, $conditions = [])
    {
        $conditionString = !empty($conditions) ? (' and ' . implode(' and ', $conditions)) : '';

        $query = "select
              slug
          from category
            WHERE slug like '{$slug}%'
            {$conditionString}
            ";

        try {
            $em = $this->getEntityManager();
            $stmt = $em->getConnection()
                ->prepare($query);
            $stmt->execute();

            return CommonManager::data_get($stmt->fetchAll(), '*.slug');
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Finds the locations matching the slug.
     *
     * @param $slug
     * @param array $conditions
     * @return array|mixed
     */
    protected function findLocationBySlug($slug, $conditions = [])
    {
        $conditionString = !empty($conditions) ? (' and ' . implode(' and ', $conditions)) : '';
        $query = "select
              url
          from location
            WHERE url like '{$slug}%'
            {$conditionString} ";

        try {
            $em = $this->getEntityManager();
            $stmt = $em->getConnection()
                ->prepare($query);
            $stmt->execute();

            return CommonManager::data_get($stmt->fetchAll(), '*.url');
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Finds the localities matching the slug.
     *
     * @param $slug
     * @return array|mixed
     */
    protected function findLocalityBySlug($slug)
    {
        $query = "select
              url
          from locality
            WHERE url like '{$slug}%'";

        try {
            $em = $this->getEntityManager();
            $stmt = $em->getConnection()
                ->prepare($query);
            $stmt->execute();

            return CommonManager::data_get($stmt->fetchAll(), '*.url');
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Finds the regions matching the slug.
     *
     * @param $slug
     * @return array|mixed
     */
    protected function findRegionBySlug($slug)
    {
        $query = "select
              slug
          from region
            WHERE slug like '{$slug}%'";

        try {
            $em = $this->getEntityManager();
            $stmt = $em->getConnection()
                ->prepare($query);
            $stmt->execute();

            return CommonManager::data_get($stmt->fetchAll(), '*.slug');
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Find entities by slug.
     *
     * @param $slug
     * @param bool $conditions
     * @return array
     */
    protected function findEntityBySlug($slug, $conditions = false)
    {
        $slug = revert_slug($slug);
        $query = "select
              id,
              name,
              slug
          from entity
            WHERE name like '{$slug}%'";

        try {
            $em = $this->getEntityManager();
            $stmt = $em->getConnection()
                ->prepare($query);
            $stmt->execute();

            if (!$conditions) {
                return array_map(function ($item) {
                    return slug($item);
                }, CommonManager::data_get($stmt->fetchAll(), '*.name'));
            } elseif (CommonManager::array_first(array_wrap($conditions)) == 'id-slug-pair') {

                $entities = [];
                foreach ($stmt->fetchAll() as $entity) {
                    $entities[CommonManager::data_get($entity, 'id')] = slug(data_get($entity, 'slug'));
                }

                return $entities;
            }
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Gives a JSON response for the given data.
     *
     * @param $data
     * @return JsonResponse
     */
    protected function returnJson($data)
    {
        return new JsonResponse($data);
    }

    /**
     * Retrieve the conditions from the string.
     *
     * @param $conditions
     * @return array
     */
    protected function retrieveConditions($conditions)
    {
        $conditions = trim($conditions);

        if (empty($conditions)) {
            return [];
        }

        return explode('||', $conditions);
    }

    /**
     * Get the file data.
     *
     * @param $fileData
     * @return array|bool|string
     */
    protected function getFileData($fileData)
    {
        if (empty($fileData)) {
            return [];
        }

        $data = explode(',', $fileData);
        $data = array_filter(explode("\n", base64_decode($data[1])));
        $data = array_map(function ($item) {
            return trim(trim($item, "\r"));
        }, $data);

        unset($data[0]);

        return array_values($data);
    }

    public function uploadCsvAction(Request $request)
    {
        $csvColumnsArray = array();
        if ($request->ismethod('post')) {
            $errorMsg = '';
            $webPath = $this->container->get('kernel')->getRootDir() . '/../web';
            $objUploadedFile = $request->files->get('objCSVFileTopLink');
            if (!$objUploadedFile) {
                $objUploadedFile = $request->files->get('objCSVFilePopularSearch');
            }
            $fileOriginalName = $objUploadedFile->getClientOriginalName();
            $fileExtension = substr(strrchr($fileOriginalName, '.'), 1);
            $tmpFilePath = $webPath . DIRECTORY_SEPARATOR . $this->container->getParameter('fa.ad.image.tmp.dir');

            if ($fileExtension == 'csv') {
                // upload file.
                $objUploadedFile->move($tmpFilePath, $fileOriginalName);
                $objFile = fopen($tmpFilePath . '/' . $fileOriginalName, "r");
                $rowCounter = 0;
                while (!feof($objFile)) {
                    $rowArray = fgetcsv($objFile);

                    if ($rowCounter == 0) {
                        if (is_array($rowArray) && count($rowArray) != 2) {
                            $errorMsg = "CSV file must have 2 columns!";
                        }
                    }

                    if (isset($rowArray[0])) {
                        $csvColumnsArray[] = $rowArray[0];
                    }
                    if (isset($rowArray[1])) {
                        $csvColumnsArray[] = $rowArray[1];
                    }
                }
                fclose($objFile);
            } else {
                $errorMsg = "Only csv files are allowed!";
            }

            unlink($tmpFilePath . '/' . $fileOriginalName);
        }

        return new JsonResponse(array(
            'data' => $csvColumnsArray,
            'error' => $errorMsg
        ));
    }

    /**
     * Save the crawl settings.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function crawlSettingsAction(Request $request)
    {
        $data = array_map(function ($datum) {

            return [
                'category' => CommonManager::data_get($datum, 'category'),
                'dimension' => CommonManager::data_get($datum, 'dimension'),
                'region' => filter_var(CommonManager::data_get($datum, 'region', false), FILTER_VALIDATE_BOOLEAN),
                'county' => filter_var(CommonManager::data_get($datum, 'county', false), FILTER_VALIDATE_BOOLEAN),
                'town' => filter_var(CommonManager::data_get($datum, 'town', false), FILTER_VALIDATE_BOOLEAN),
            ];

        }, ((array) $request->get('data')));

        $data = array_filter($data, function ($item) {

            $category = CommonManager::data_get($item, 'category');
            $dimension = CommonManager::data_get($item, 'dimension');
            $region = CommonManager::data_get($item, 'region');
            $county = CommonManager::data_get($item, 'county');
            $town = CommonManager::data_get($item, 'town');

            return !empty($category) || !empty($dimension) || $region || $county || $town;
        });

        $this->saveData(SeoConfigRepository::CRAWL_CONFIG, $data);

        return new JsonResponse([
            'status' => 1,
            'data' => $data
        ]);
    }

    /**
     * Process Bulk Upload.
     *
     * @param Request $request
     * @param $data
     * @param $configType
     * @return array|string
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function processBulkUpload(Request $request, $data, $configType)
    {
        if (empty($type = strtolower($request->get('type')))) {
            $this->saveData($configType, $data);
        } elseif ($type == 'bulk') {
            var_dump($data);


            $existingData = CommonManager::data_get($this->seoConfig(false), "{$configType}.data", []);

            // Convert Assoc array to Normal array
            if (is_associative_array($existingData)) {
                $newArray = [];
                foreach ($existingData as $key => $value) {
                    $newArray[] = "{$key}:{$value}";
                }
                $existingData = $newArray;
            }

            if (($format = strtolower($request->get('format'))) && $format == 'file') {

                if (in_array($configType, [SeoConfigRepository::META_ROBOTS])) {
                    $data = $this->getFileData($data);

                    $newArray = [];
                    foreach ($data as $metaRule) {
                        $metaRule = str_replace("\",", ':', $metaRule);
                        $metaRule = str_replace("\"", '', $metaRule);
                        $newArray[] = $metaRule;
                    }

                    $data = $newArray;

                } else {
                    $data = array_map(function ($value) {
                        return implode(':', explode(',', $value));
                    }, $this->getFileData($data));
                }

            } elseif ($format == 'text') {

                if (!in_array($configType, [SeoConfigRepository::META_ROBOTS])) {
                    $data = implode(",", $data);
                    $data = array_unique(array_filter(explode(',', $data)));
                } else {
                    $data = array_unique(array_filter(array_wrap($data)));
                }
            }

            $data = array_merge($existingData, $data);

            if (!empty($data)) {
                $this->saveData($configType, $data);
            }
        }

        return $data;
    }

    /**
     * Get the redirect view.
     *
     * @param Request $request
     * @return Response
     */
    public function validateRedirectsViewAction(Request $request)
    {
        $root = $request->server->get('DOCUMENT_ROOT') . '/../data/seo';
        $website = $this->container->getParameter('base_url');
        $file = "{$root}/{$website}/redirect_rules.csv";

        $data = [];
        if (file_exists($file)) {
            $csvReader = csv_reader($file);
            $data = $csvReader->getAll();
        }

        $parameters = [
            'heading' => 'Verify Redirects',
            'data' => $data,
        ];

        return $this->render('FaContentBundle:SeoConfigAdmin:verifyRedirect.html.twig', $parameters);
    }

    /**
     * Upload sample redirect rule csv.
     *
     * @param Request $request
     * @return Response
     */
    public function validateRedirectsUploadAction(Request $request)
    {
        /** @var UploadedFile $file */
        $file = $request->files->get('redirects');

        $this->saveRedirectRuleFile($request, $file);

        return $this->redirect(router($this->container)->generate('admin_validate_redirects_view'));
    }

    /**
     * Check redirect status for given array of redirect rules.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validateRedirectsAction(Request $request)
    {
        $data = (array) $request->get('data');

        if (empty($data)) {
            return new JsonResponse([]);
        }

        return new JsonResponse($this->validateURLRedirects($request, $data));
    }

    /**
     * Save the redirect sample rule csv
     *
     * @param Request $request
     * @param UploadedFile $file
     */
    protected function saveRedirectRuleFile(Request $request, UploadedFile $file)
    {
        $root = $request->server->get('DOCUMENT_ROOT') . '/../data/seo';

        if (!is_dir($root)) {
            mkdir($root, 0777);
        }

        $website = $this->container->getParameter('base_url');

        if (!is_dir("{$root}/{$website}")) {
            mkdir("{$root}/{$website}", 0777);
        }

        file_put_contents("{$root}/{$website}/redirect_rules.csv", file_get_contents($file));
    }

    /**
     * Check the status of redirects.
     *
     * @param Request $request
     * @param array $redirectArray
     * @return array
     */
    protected function validateURLRedirects(Request $request, $redirectArray = [])
    {
        $uri = parse_url($request->getUri());
        $script = basename($request->server->get('SCRIPT_NAME'));

        $baseUrl = rtrim(
            CommonManager::data_get($uri, 'scheme')
            . '://'
            . CommonManager::data_get($uri, 'host')
            . '/'
            . ($script == 'app_dev.php' ? $script : ''), '/');

        foreach($redirectArray as &$redirectDatum) {

            $fromUrl = rtrim("{$baseUrl}/" . ltrim(CommonManager::data_get($redirectDatum, 'from'), '/'), '/') . '/';
            $expectedTo = ltrim(CommonManager::data_get($redirectDatum, 'expected_to'), '/');

            $headers = $this->getResponseHeader($fromUrl);

            $redirectTarget = CommonManager::array_first(CommonManager::data_get($headers, 'Location', []));

            if (empty($redirectTarget)) {
                $redirectDatum['redirect'] = 'No Redirect';
                continue;
            }

            if (!substr_exist($redirectTarget, $expectedTo)) {
                $redirectDatum['redirect'] = $redirectTarget;
                $redirectDatum['redirect_to_other_url'] = true;
            } else {
                $redirectDatum['redirect'] = 'Expected Result';
            }
        }

        return $redirectArray;
    }

    public function dataTableConfigAction(Request $request)
    {
        $configSlug = $request->get('config');
        $offset = $request->get('start');
        $page = $request->get('draw');
        $perPage = $request->get('length');

        $configs = $this->getConfigData($configSlug);

        return new JsonResponse([
            'data' => $configs,
        ]);
    }
    
    public function dataTableRedirectConfigAction(Request $request)
    {
        $configSlug = $request->get('config');
        $offset = $request->get('start');
        $page = $request->get('draw');
        $perPage = $request->get('length');
        
        $configs = $this->getRedirectConfigData();
        
        return new JsonResponse([
            'data' => $configs,
        ]);
    }

    public function dataTableMotorsRedirectConfigAction(Request $request)
    {
        $configSlug = $request->get('config');
        $offset = $request->get('start');
        $page = $request->get('draw');
        $perPage = $request->get('length');

        $configs = $this->getMotorsRedirectConfigData();

        return new JsonResponse([
            'data' => $configs,
        ]);
    }

    /**
     * Get the config data for the data-table.
     *
     * @param $config
     * @return array
     */
    public function getConfigData($config)
    {
        $data = CommonManager::data_get($this->seoConfig(false), "{$config}.data", []);

        // Convert Assoc array to Normal array
        if (is_array($data) && is_associative_array($data)) {
            $newData = [];
            foreach ($data as $key => $value) {
                $newData[] = "{$key}:{$value}";
            }
            $data = $newData;
        }

      if (in_array($config, [
            SeoConfigRepository::UNNECESSARY_ODATA_PARAMS,
            SeoConfigRepository::UNNECESSARY_QUERY_PARAMS,
            SeoConfigRepository::URL_RIGHT_TRIM,
            ])) {
            return $this->transformSingleItemConfig($data);
        } elseif (in_array($config, [
            SeoConfigRepository::REGION_ALIAS,
            SeoConfigRepository::LOCATION_ALIAS,
            SeoConfigRepository::FILTER_ALIAS,
            SeoConfigRepository::CATEGORY_ALIAS,
            SeoConfigRepository::ALL_CATEGORY_ALIAS,
        ])) {
            return $this->transformAliases($data);
        } elseif ($config == SeoConfigRepository::META_ROBOTS) {
            return $this->transformMetaData($data);
        }
    }

    
    /**
     * Get the config data for the data-table.
     *
     * @param $config
     * @return array
     */
    public function getRedirectConfigData() {
        $data = $this->getRepository('FaAdBundle:Redirects')->getAllRedirects();
        return $this->transformRedirects($data);
    }

    /**
     * Get the config data for the data-table.
     *
     * @param $config
     * @return array
     */
    public function getMotorsRedirectConfigData() {
        $data = $this->getRepository('FaAdBundle:MotorsRedirects')->getAllRedirects();
        return $this->transformMotorsRedirects($data);
    }

    /**
     * Transform Redirect data.
     *
     * @param array $data
     * @return array
     */
    private function transformMotorsRedirects($data = [])
    {
        return array_map(function ($item) {
            $action =
                '<span class="datatable-action-list" style="list-style: none">
                    <textarea class="data" style="display: none;">'. CommonManager::data_get($item, 'id', '') .'</textarea>
                    <i class="fa fi-pencil small edit-motors"></i>
                    <i class="fa fi-undo undo hidden"></i>
                    <i class="fa fi-trash motors-redirect-delete"></i>
                    <i class="fa fi-save motors-redirect-save hidden"></i>
                </span>';


            return [
                'from'  => CommonManager::data_get($item, 'nval', ''),
                'Field'    => CommonManager::data_get($item, 'field_name', ''),
                'category'    => CommonManager::data_get($item, 'mapped_id', ''),
                'parent'    => CommonManager::data_get($item, 'parent', ''),
                'parent category'    => CommonManager::data_get($item, 'parent_cat_id', ''),
                'action'  => $action,
            ];

        }, $data);
    }
    /**
     * Transform Redirect data.
     *
     * @param array $data
     * @return array
     */
    private function transformRedirects($data = [])
    {
        return array_map(function ($item) {            
            $action =
                '<span class="datatable-action-list" style="list-style: none">
                    <textarea class="data" style="display: none;">'. CommonManager::data_get($item, 'id', '') .'</textarea>
                    <i class="fa fi-pencil small edit"></i>
                    <i class="fa fi-undo undo hidden"></i>
                    <i class="fa fi-trash redirectdelete"></i>
                    <i class="fa fi-save redirectsave hidden"></i>
                </span>';


            return [
                'from'  => CommonManager::data_get($item, 'old', ''),
                'to'    => CommonManager::data_get($item, 'new', ''),
                'type'  => (CommonManager::data_get($item, 'is_location')==1) ? 'location' : ((CommonManager::data_get($item, 'is_location')==2)?'article':'category'),
                'action'  => $action,
            ];

        }, $data);
    }

    /**
     * Transform Un data.
     *
     * @param array $data
     * @return array
     */
    private function transformSingleItemConfig($data = [])
    {
        return array_map(function ($item) {

            $action =
                '<span class="datatable-action-list" style="list-style: none">
                    <textarea class="data" style="display: none;">'. $item .'</textarea>
                    <i class="fa fi-pencil small edit"></i>
                    <i class="fa fi-undo undo hidden"></i>
                    <i class="fa fi-trash delete"></i>
                    <i class="fa fi-save save hidden"></i>
                </span>';


            return [
                'param'  => $item,
                'action'  => $action,
            ];

        }, $data);
    }

    /**
     * Transform Aliases data.
     *
     * @param array $data
     * @return array
     */
    private function transformAliases($data = [])
    {
        return array_map(function ($item) {
            $items = explode(':', $item);

            $action =
                '<span class="datatable-action-list" style="list-style: none">
                    <textarea class="data" style="display: none;">'. $item .'</textarea>
                    <i class="fa fi-pencil small edit"></i>
                    <i class="fa fi-undo undo hidden"></i>
                    <i class="fa fi-trash delete"></i>
                    <i class="fa fi-save save hidden"></i>
                </span>';


            return [
                'legacy'  => CommonManager::data_get($items, 0, ''),
                'new'    => CommonManager::data_get($items, 1, ''),
                'action'  => $action,
            ];

        }, $data);
    }

    /**
     * Transform Aliases data.
     *
     * @param array $data
     * @return array
     */
    private function transformMetaData($data = [])
    {
        return array_map(function ($item) {
            $items = explode(':', $item);

            $action =
                '<span class="datatable-action-list" style="list-style: none">
                    <textarea class="data" style="display: none;">'. $item .'</textarea>
                    <i class="fa fi-pencil small edit"></i>
                    <i class="fa fi-undo undo hidden"></i>
                    <i class="fa fi-trash delete"></i>
                    <i class="fa fi-save save hidden"></i>
                </span>';


            return [
                'meta'   => CommonManager::data_get($items, 0, ''),
                'url'    => CommonManager::data_get($items, 1, ''),
                'action' => $action,
            ];

        }, $data);
    }

    /**
     * Get Url Response header.
     *
     * @param $url
     * @return mixed
     */
    public function getResponseHeader($url)
    {
        // Build the HTTP Request Headers
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        parse_str(curl_exec($ch), $response);

        $headers = CommonManager::array_first($response);
        $headers = str_replace("\r", "", $headers);
        $headers = array_filter(explode("\n", $headers));
        $headers = headers_from_lines($headers);

        return $headers;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function removeRuleAction(Request $request)
    {
        $configSlug = $request->get('config');
        $rule = $request->get('rule');

        $removeRules = [
            $rule,
        ];

        $changed = false;
        $data = CommonManager::data_get($this->seoConfig(false), "{$configSlug}.data", []);
        $isAssocArray = is_associative_array($data);

        foreach ($removeRules as $rule) {

            if ($isAssocArray) {
                $ruleKey = explode(':', $rule);
                if (isset($data[$ruleKey[0]])) {
                    unset($data[$ruleKey[0]]);
                    $changed = true;
                }
            } else {
                if (!is_bool($pos = array_search($rule, $data))) {
                    unset($data[$pos]);
                    $changed = true;
                }
            }
        }

        if ($changed) {
            $this->saveData($configSlug, ($isAssocArray ? $data: array_values($data)));

            return new JsonResponse([
                'changed' => true,
                'rule' => $rule,
            ]);
        }

        return new JsonResponse([
            'changed' => false,
            'rule' => $rule,
        ]);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function editRuleAction(Request $request)
    {
        $configSlug = $request->get('config');
        $ruleFrom = $request->get('rule_from');
        $ruleTo = $request->get('rule_to');

        $changed = false;
        $data = CommonManager::data_get($this->seoConfig(false), "{$configSlug}.data", []);

        $rulesToChange= [
            $ruleFrom => $ruleTo
        ];

        foreach ($rulesToChange as $from => $to) {

            if (!is_bool($pos = array_search($from, $data))) {
                $changed = true;
                $data[$pos] = $to;
            }
        }

        if ($changed) {
            $this->saveData($configSlug, array_values($data));

            return new JsonResponse([
                'changed' => true,
                'ruleFrom' => $ruleFrom,
                'ruleTo' => $ruleTo,
            ]);
        }

        return new JsonResponse([
            'changed' => false,
            'ruleFrom' => $ruleFrom,
            'ruleTo' => $ruleTo,
        ]);
    }
    
    public function redirectremoveRuleAction(Request $request)
    {      
        $ruleId = $request->get('rule');
        $result = '';
        
        $result = $this->getRepository('FaAdBundle:Redirects')->deleteRecordById($ruleId);        
        
        if ($result) {
            return new JsonResponse([
                'changed' => true,
                'rule' => $ruleId,
            ]);
        }
        
        return new JsonResponse([
            'changed' => false,
            'rule' => $ruleId,
        ]);
    }
    
    
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function redirecteditRuleAction(Request $request)
    {
        $ruleTo = $ruleArray = $getExistData = array();
        $ruleId = $request->get('rule_from');
        $ruleVal = $request->get('rule_to');
        $ruleTo = explode(':',$ruleVal);
        
        if(!empty($ruleTo)) {
            $ruleArray['old'] = $ruleTo[0];
            $ruleArray['new'] = $ruleTo[1];
            $ruleArray['is_location'] = ($ruleTo[2]=='location')?1:($ruleTo[2]=='article'?2:0);
        }
        
        $getExistData = $this->getRepository('FaAdBundle:Redirects')->getRedirectByArray($ruleArray);
        if(empty($getExistData)) {            
            $redirects = $this->getRepository('FaAdBundle:Redirects')->find($ruleId);           
            $redirects->setOld($ruleArray['old']);
            $redirects->setNew($ruleArray['new']);
            $redirects->setIsLocation($ruleArray['is_location']);
            $this->updateEntity($redirects);
            
            return new JsonResponse([
                'changed' => true,
                'ruleFrom' => $ruleId,
                'ruleTo' => $ruleVal,
            ]);
        }
                
        return new JsonResponse([
            'changed' => false,
            'ruleFrom' => $ruleId,
            'ruleTo' => $ruleTo,
        ]);
    }

    public function motorsRedirectRemoveAction(Request $request)
    {
        $motorsRedirectId = $request->get('MotorsRedirectsID');
        $result = '';

        $result = $this->getRepository('FaAdBundle:MotorsRedirects')->deleteRecordById($motorsRedirectId);

        if ($result) {
            return new JsonResponse([
                'changed' => true,
                'MotorsRedirectsID' => $motorsRedirectId,
            ]);
        }

        return new JsonResponse([
            'changed' => false,
            'MotorsRedirectsID' => $motorsRedirectId,
        ]);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function motorsRedirectEditAction(Request $request)
    {
        $ruleTo = $ruleArray = $getExistData = array();
        $ruleId = $request->get('rule_from');
        $ruleVal = $request->get('rule_to');
        $ruleTo = explode(':',$ruleVal);

        if(!empty($ruleTo)) {
            $ruleArray['old'] = $ruleTo[0];
            $ruleArray['new'] = $ruleTo[1];
            $ruleArray['is_location'] = ($ruleTo[2]=='location')?1:($ruleTo[2]=='article'?2:0);
        }

        $getExistData = $this->getRepository('FaAdBundle:MotorsRedirects')->getRedirectByArray($ruleArray);
        if(empty($getExistData)) {
            $redirects = $this->getRepository('FaAdBundle:MotorsRedirects')->find($ruleId);
            $redirects->setOld($ruleArray['old']);
            $redirects->setNew($ruleArray['new']);
            $redirects->setIsLocation($ruleArray['is_location']);
            $this->updateEntity($redirects);

            return new JsonResponse([
                'changed' => true,
                'ruleFrom' => $ruleId,
                'ruleTo' => $ruleVal,
            ]);
        }

        return new JsonResponse([
            'changed' => false,
            'ruleFrom' => $ruleId,
            'ruleTo' => $ruleTo,
        ]);
    }
    /**
     * Save LHS Filter status of a category.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function LHSFilterCategoryStatusUpdateAction(Request $request){
        $data = $request->get('data');
        $dim_id =$data['dimension'];
        $status = $data['status'];

        $dimension = $this->getRepository('FaEntityBundle:CategoryDimension')->find($dim_id);
        if($dimension){
            $dimension->setIsSearchable($status);
            $this->getEntityManager()->persist($dimension);
            $this->getEntityManager()->flush();
            $dimension = $this->getRepository('FaEntityBundle:CategoryDimension')->find($dim_id);
            return new JsonResponse([
                'status' => 1,
                'dimension_status' => $dimension->getIsSearchable(),
            ]);
        }
        return new JsonResponse([
            'status' => 0,
            'error' => 'record not Found',
        ]);
    }

    /**
     * Save Crwal Setting for Category Dimension.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function crawlCategoryDimensionStatusUpdateAction(Request $request){
        $data = $request->get('data');
        $dim_id =$data['dimension'];
        $status = $data['status'];

        $dimension = $this->getRepository('FaEntityBundle:CategoryDimension')->find($dim_id);
        if($dimension){
            $dimension->setIsNotCrawlable($status);
            $this->getEntityManager()->persist($dimension);
            $this->getEntityManager()->flush();
            $dimension = $this->getRepository('FaEntityBundle:CategoryDimension')->find($dim_id);
            return new JsonResponse([
                'status' => 1,
                'dimension_id' => $dimension->getId(),
                'dimension_crawl' => $dimension->getIsNotCrawlable(),
            ]);
        }
        return new JsonResponse([
            'status' => 0,
            'error' => 'record not Found',
        ]);
    }
    /**
     * Lists all Category dimensions.
     *
     * @param Request $request.
     *
     * @return JsonResponse
     */
    public function getCategoryDimensionAction(Request $request){
        $category = $request->get('category_id');
        $categoryList = $this->getRepository('FaEntityBundle:Category')->getNestedChildrenIdsByCategoryId($category);
        $list = $dimension = $this->getRepository('FaEntityBundle:CategoryDimension')->getDimesionsArrayByCategoryArray($categoryList);
        return new JsonResponse($list);
    }
}
