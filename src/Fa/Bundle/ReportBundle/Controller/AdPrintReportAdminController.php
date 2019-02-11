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

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Symfony\Component\HttpFoundation\Request;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\HttpFoundation\Response;
use Fa\Bundle\ReportBundle\Form\AdPrintReportSearchAdminType;

/**
 * This is default controller for ad print report.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdPrintReportAdminController extends CoreController implements ResourceAuthorizationController
{
    /**
     * Ad print report action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function indexAction(Request $request)
    {
        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getHistoryRepository('FaReportBundle:AdPrintReportDaily'), $this->getHistoryRepositoryTable('FaReportBundle:AdPrintReportDaily'), 'fa_report_item_print_report_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();
        // initialize form manager service
        $formManager    = $this->get('fa.formmanager');
        $form           = $formManager->createForm(AdPrintReportSearchAdminType::class, null, array('action' => $this->generateUrl('fa_report_ad_print'), 'method' => 'GET'));
        $pagination     = null;
        $data['search'] = array_filter($data['search']);

        if ($data['search']) {
            $form->submit($data['search']);
            if ($form->isValid()) {
                $query = $this->getHistoryRepository('FaReportBundle:AdPrintReportDaily')->getAdPrintReportQuery($data['search'], $data['sorter'], $this->container);

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
            $this->get('session')->set('ad_print_report_export_criteria', serialize($criteria));
        } else {
            $this->get('session')->remove('ad_print_report_export_criteria');
        }

        $parameters = array(
            'heading'         => 'Print expiry report',
            'form'            => $form->createView(),
            'pagination'      => $pagination,
            'sorter'          => $data['sorter'],
            'searchParams'    => $data['search'],
            'page'            => (isset($page) ? $page : 1),
        );

        return $this->render('FaReportBundle:AdPrintReportAdmin:index.html.twig', $parameters);
    }

    /**
     * Ad print report export to csv action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function exportAdPrintReportToCsvAction(Request $request)
    {
        if (!$this->get('session')->has('ad_print_report_export_criteria')) {
            return parent::handleMessage('Please select report criteria.', 'fa_report_ad_print', array(), 'error');
        } else {
            CommonManager::setAdminBackUrl($request, $this->container);
            $backUrl = CommonManager::getAdminCancelUrl($this->container);
            exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project_path').'/console fa:ad:print:report:export-to-csv --criteria=\''.$this->get('session')->get('ad_print_report_export_criteria').'\' >/dev/null &');
            $searchParam = unserialize($this->get('session')->get('ad_print_report_export_criteria'));
            if (isset($searchParam['search']['csv_email']) && $searchParam['search']['csv_email']) {
                $message = $this->get('translator')->trans('Report csv will be generated and emailed to %email%.', array('%email%' => $searchParam['search']['csv_email']));
            } else {
                $message = $this->get('translator')->trans('It will take time to generate csv, Please check Download generated cvs list after 5 mins or as per search result count.');
            }
            return parent::handleMessage($message, ($backUrl ? $backUrl : 'fa_report_ad_print'));
        }
    }

    /**
     * Ad report list csv action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function ajaxListAdPrintReportCsvAction(Request $request)
    {
        $htmlContent   = '';

        if ($request->isXmlHttpRequest()) {
            $fileList    = CommonManager::listDirFileByDate($this->container->get('kernel')->getRootDir()."/../data/reports/ad_print/", array('csv'));
            $htmlContent = $this->renderView('FaReportBundle:AdPrintReportAdmin:ajaxListAdPrintReportCsv.html.twig', array('fileList' => $fileList));
            return new JsonResponse(array('htmlContent' => $htmlContent));
        } else {
            return new Response();
        }
    }

    /**
     * Ad print report delete csv action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function ajaxDeleteAdPrintReportCsvAction(Request $request)
    {
        $htmlContent = '';
        $error       = '';

        if ($request->isXmlHttpRequest()) {
            $reportPath = $this->container->get('kernel')->getRootDir()."/../data/reports/ad_print/";
            $fileName   = $request->get('fileName');
            if (is_file($reportPath.$fileName)) {
                unlink($reportPath.$fileName);
                $fileList    = CommonManager::listDirFileByDate($reportPath, array('csv'));
                $htmlContent = $this->renderView('FaReportBundle:AdPrintReportAdmin:ajaxListAdPrintReportCsv.html.twig', array('fileList' => $fileList, 'csvDelete' => 1));
            }
            return new JsonResponse(array('htmlContent' => $htmlContent, 'error' => $error));
        } else {
            return new Response();
        }
    }

    /**
     * Download ad print report csv.
     *
     * @param Request $request A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function downloadAdPrintReportCsvAction($fileName)
    {
        $filePath = $this->container->get('kernel')->getRootDir()."/../data/reports/ad_print/".$fileName;
        if (is_file($filePath)) {
            CommonManager::downloadFile($filePath, $fileName);
            return new Response();
        }
    }
}
