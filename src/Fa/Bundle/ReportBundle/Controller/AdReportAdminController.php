<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\ReportBundle\Controller;

use Fa\Bundle\CoreBundle\Manager\FormManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\ReportBundle\Form\AdReportSearchAdminType;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;

/**
 * This is default controller for dot mailer bundle.
 *
 * @author    Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version   v1.0
 */
class AdReportAdminController extends CoreController implements ResourceAuthorizationController
{
    /**
     * Ad report action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     * @return Response
     */
    public function indexAction(Request $request)
    {
        /**
         * @var FormManager $formManager
         */
        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getHistoryRepository('FaReportBundle:AdReportDaily'), $this->getHistoryRepositoryTable('FaReportBundle:AdReportDaily'), 'fa_item_report');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form = $formManager->createForm(AdReportSearchAdminType::class, null, array('action' => $this->generateUrl('fa_report_ad'), 'method' => 'GET', 'allow_extra_fields' => true));
        $pagination = null;
        $data['search'] = array_filter($data['search']);
        $isCountQuery = false;
        $townIds = array();
        
        if (isset($data['search']['town_id'])) {
            $townIds = array_filter($data['search']['town_id']);            
        }elseif(isset($data['search']['county_id'])) {
            $getTownIds = $this->getRepository('FaEntityBundle:Location')->getChildrenIdsById($data['search']['county_id']);
            $townIds = array_filter($getTownIds);
        } elseif(isset($data['search']['location_group__location_group_id'])) {
            $getTownIds = $this->getRepository('FaEntityBundle:LocationGroupLocation')->getChildrenIdsByIds($data['search']['location_group__location_group_id']);
            $townIds = array_filter($getTownIds);    
        }
        //echo 'getTownIds===<pre>';print_r($getTownIds);
       //echo '<pre>';print_r($townIds); die;
        
        if (!empty($townIds)) {
            $data['search']['town_id'] = $townIds;
        } else {
            unset($data['search']['town_id']);
        }
        

        if ($data['search']) {
            $form->submit($data['search']);
            if ($form->isValid()) {
                //shorten url and redirect.
                if (isset($data['search']) && !isset($data['search']['parsed']) && $request->getQueryString()) {
                    parse_str(urldecode($request->getQueryString()), $parsedArray);
                    $parsedArray['fa_item_report']['parsed'] = true;
                    $parsedArray = array_filter(array_map('array_filter', $parsedArray));

                    return $this->redirectToRoute('fa_report_ad', $parsedArray);
                }
                if (in_array('total_ads', $data['search']['report_columns'])) {
                    $isCountQuery = true;
                }
                $query = $this->getHistoryRepository('FaReportBundle:AdReportDaily')->getAdReportQuery($data['search'], $data['sorter'], $this->container, $isCountQuery);

                // initialize pagination manager service and prepare listing with pagination based of data
                $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
                $this->get('fa.pagination.manager')->init($query, $page, 20, 0, true);
                $pagination = $this->get('fa.pagination.manager')->getPagination();
            }
        }

        // store criteria in session for export.
        if ($pagination && $pagination->getNbResults()) {
            if (isset($data['search']['_token'])) {
                unset($data['search']['_token']);
            }
            $criteria = array('search' => $data['search'], 'sort' => $data['sorter']);
            $this->get('session')->set('ad_report_export_criteria', serialize($criteria));
        } else {
            $this->get('session')->remove('ad_report_export_criteria');
        }

        $parameters = array(
            'heading' => 'Ad report',
            'form' => $form->createView(),
            'pagination' => $pagination,
            'sorter' => $data['sorter'],
            'searchParams' => $data['search'],
            'page' => (isset($page) ? $page : 1),
            'isCountQuery' => $isCountQuery,
        );

        return $this->render('FaReportBundle:AdReportAdmin:index.html.twig', $parameters);
    }

    /**
     * Ad report export to csv action.
     *
     * @param Request $request A Request object.
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function exportAdReportToCsvAction(Request $request)
    {
        if (!$this->get('session')->has('ad_report_export_criteria')) {
            return parent::handleMessage('Please select report criteria.', 'fa_report_ad', array(), 'error');
        } else {
            CommonManager::setAdminBackUrl($request, $this->container);
            $backUrl = CommonManager::getAdminCancelUrl($this->container);
            exec('nohup' . ' ' . $this->container->getParameter('fa.php.path') . ' ' . $this->container->getParameter('project_path') . '/console fa:ad:report:export-to-csv --criteria=\'' . $this->get('session')->get('ad_report_export_criteria') . '\' >/dev/null &');
            $searchParam = unserialize($this->get('session')->get('ad_report_export_criteria'));
            if (isset($searchParam['search']['csv_email']) && $searchParam['search']['csv_email']) {
                $message = $this->get('translator')->trans('Report csv will be generated and emailed to %email%.', array('%email%' => $searchParam['search']['csv_email']));
            } else {
                $message = $this->get('translator')->trans('It will take time to generate csv, Please check Download generated cvs list after 5 mins or as per search result count.');
            }
            return parent::handleMessage($message, ($backUrl ? $backUrl : 'fa_report_ad'));
        }
    }

    /**
     * Ad report list csv action.
     *
     * @param Request $request A Request object.
     * @return JsonResponse|Response
     */
    public function ajaxListAdReportCsvAction(Request $request)
    {
        $htmlContent = '';

        if ($request->isXmlHttpRequest()) {
            $fileList = CommonManager::listDirFileByDate($this->container->get('kernel')->getRootDir() . "/../data/reports/ad/", array('csv'));
            $htmlContent = $this->renderView('FaReportBundle:AdReportAdmin:ajaxListAdReportCsv.html.twig', array('fileList' => $fileList));
            return new JsonResponse(array('htmlContent' => $htmlContent));
        } else {
            return new Response();
        }
    }

    /**
     * Ad report delete csv action.
     *
     * @param Request $request A Request object.
     * @return JsonResponse|Response
     */
    public function ajaxDeleteAdReportCsvAction(Request $request)
    {
        $htmlContent = '';
        $error = '';

        if ($request->isXmlHttpRequest()) {
            $reportPath = $this->container->get('kernel')->getRootDir() . "/../data/reports/ad/";
            $fileName = $request->get('fileName');
            if (is_file($reportPath . $fileName)) {
                unlink($reportPath . $fileName);
                $fileList = CommonManager::listDirFileByDate($reportPath, array('csv'));
                $htmlContent = $this->renderView('FaReportBundle:AdReportAdmin:ajaxListAdReportCsv.html.twig', array('fileList' => $fileList, 'csvDelete' => 1));
            }
            return new JsonResponse(array('htmlContent' => $htmlContent, 'error' => $error));
        } else {
            return new Response();
        }
    }

    /**
     * Download ad report csv.
     *
     * @param string $fileName
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function downloadAdReportCsvAction($fileName)
    {
        $filePath = $this->container->get('kernel')->getRootDir() . "/../data/reports/ad/" . $fileName;
        if (is_file($filePath)) {
            CommonManager::downloadFile($filePath, $fileName);
            return new Response();
        }
    }

}
