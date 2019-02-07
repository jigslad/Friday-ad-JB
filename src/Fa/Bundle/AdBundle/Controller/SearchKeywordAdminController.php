<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fa\Bundle\AdBundle\Entity\PaaFieldRule;
// use Fa\Bundle\AdBundle\Form\PaaFieldRuleType;
use Fa\Bundle\AdminBundle\Controller\CrudController;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Form\SearchKeywordUploadCsvAdminType;
use Fa\Bundle\AdBundle\Form\SearchKeywordSearchAdminType;

/**
 * This controller is used for search keyword crud management.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class SearchKeywordAdminController extends CrudController implements ResourceAuthorizationController
{
    /**
     * SaveNewUsingForm.
     *
     * @var boolean.
     */
    protected $saveNewUsingForm  = true;

    /**
     * Save edit using form.
     *
     * @var boolean
     */
    protected $saveEditUsingForm = true;

    /**
     * Get table name.
     *
     * @return string
     */
    public function getTableName()
    {
        return 'search_keyword';
    }

    /**
     * Lists all search keywords.
     *
     * @return Response A Response object.
     */
    public function indexAction(Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);

        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaAdBundle:SearchKeyword'), $this->getRepositoryTable('FaAdBundle:SearchKeyword'), 'fa_ad_search_keyword_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaAdBundle:SearchKeyword'), $data);
        $queryBuilder = $this->get('fa.sqlsearch.manager')->getQueryBuilder();
        $queryBuilder = $queryBuilder->orderBy($queryBuilder->getRootAlias().'.search_count', 'desc');
        $query        = $queryBuilder->getQuery();

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(SearchKeywordSearchAdminType::class, null, array('action' => $this->generateUrl('search_keyword_admin'), 'method' => 'GET'));

        if ($data['search']) {
            $form->submit($data['search']);
        }

        $parameters = array(
            'heading'    => 'Keywords',
            'form'       => $form->createView(),
            'pagination' => $pagination,
            'sorter'     => $data['sorter'],
        );

        return $this->render('FaAdBundle:SearchKeywordAdmin:index.html.twig', $parameters);
    }

    /**
     * This method is used upload keyword cvs.
     *
     * @param Request $request Request object.
     *
     * @return Response A Response object.
     */
    public function uploadCsvAction(Request $request)
    {
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(SearchKeywordUploadCsvAdminType::class, null, array('action' => $this->generateUrl('search_keyword_upload_csv_admin'), 'method' => 'POST'));

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                return $this->handleMessage($this->get('translator')->trans('Csv file is uploaded successfully.', array(), 'success'), 'search_keyword_admin');
            }
        }

        $parameters = array(
            'heading'    => 'Upload Csv',
            'form'       => $form->createView()
        );

        return $this->render('FaAdBundle:SearchKeywordAdmin:uploadCsv.html.twig', $parameters);
    }

    /**
     * This method is used import keyword cvs.
     *
     * @param Request $request Request object.
     *
     * @return Response A Response object.
     */
    public function importKeywordsAction(Request $request)
    {
        if (file_exists($this->get('kernel')->getRootDir().'/../web/uploads/keyword/search_keywords.csv')) {
            // move file to import directory
            rename($this->get('kernel')->getRootDir().'/../web/uploads/keyword/search_keywords.csv', $this->get('kernel')->getRootDir().'/../web/uploads/keyword/import/search_keywords.csv');

            exec('nohup'.' '.$this->container->getParameter('fa.php.path').' bin/console fa:import:search-keywords >/dev/null &');

            return $this->handleMessage($this->get('translator')->trans('Keywords import has been started...Please wait for a while.', array(), 'success'), 'search_keyword_admin');
        }

        return $this->handleMessage($this->get('translator')->trans('Keywords import is runnning or completed. Please upload a new csv file to import again.', array(), 'success'), 'search_keyword_admin');
    }

    /**
     * This method is used process keyword cvs.
     *
     * @param Request $request Request object.
     *
     * @return Response A Response object.
     */
    public function processKeywordsAction(Request $request)
    {
        if (file_exists($this->get('kernel')->getRootDir().'/../web/uploads/keyword/process/search_keywords.csv')) {
            // move file to import directory
            rename($this->get('kernel')->getRootDir().'/../web/uploads/keyword/process/search_keywords.csv', $this->get('kernel')->getRootDir().'/../web/uploads/keyword/processing/search_keywords.csv');

            exec('nohup'.' '.$this->container->getParameter('fa.php.path').' bin/console fa:process:search-keywords >/dev/null &');

            return $this->handleMessage($this->get('translator')->trans('Keywords processing has been started...Please wait for a while.', array(), 'success'), 'search_keyword_admin');
        }

        return $this->handleMessage($this->get('translator')->trans('Keywords processing is running or completed. Please upload a new csv file to process again.', array(), 'success'), 'search_keyword_admin');
    }

    /**
     * Deletes a record.
     *
     * @param Request $request A request object.
     * @param integer $id      Id.
     *
     * @return Response A response object.
     */
    public function deleteAction(Request $request, $id)
    {
        $backUrl       = CommonManager::getAdminBackUrl($this->container);
        $deleteManager = $this->get('fa.deletemanager');
        $entity        = $this->getRepository($this->getBundleName().':'.$this->getEntityName())->find($id);

        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find %bundleAlias%.', array('%bundleAlias%' => $this->getBundleAlias())));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', $this->getRouteName(''));
        }

        try {
            $searchKeywordId = $entity->getId();
            $deleteManager->delete($entity);

            $this->getRepository('FaAdBundle:SearchKeywordCategory')->removeByKeywordId($searchKeywordId);
        } catch (\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException $e) {
            return parent::handleMessage($this->get('translator')->trans("This record can not be removed from database because it's reference exists in database.", array(), 'error'), $this->getRouteName(''), array(), 'error');
        } catch (\Exception $e) {
            return parent::handleException($e, 'error', $this->getRouteName(''));
        }

        return parent::handleMessage($this->get('translator')->trans('Keyword has been deleted successfully.', array(), 'success'), ($backUrl ? $backUrl : $this->getRouteName('')));
    }
}
