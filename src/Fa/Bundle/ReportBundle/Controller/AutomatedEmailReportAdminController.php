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
use Fa\Bundle\ReportBundle\Form\AutomatedEmailReportSearchAdminType;

/**
 * This is default controller for automated email report.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AutomatedEmailReportAdminController extends CoreController implements ResourceAuthorizationController
{
    /**
     * Automated email report action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function indexAction(Request $request)
    {
        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getHistoryRepository('FaReportBundle:AutomatedEmailReportDaily'), $this->getHistoryRepositoryTable('FaReportBundle:AutomatedEmailReportDaily'), 'fa_report_automated_email_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        // initialize form manager service
        $formManager    = $this->get('fa.formmanager');
        $form           = $formManager->createForm(AutomatedEmailReportSearchAdminType::class, null, array('action' => $this->generateUrl('fa_report_automated_email'), 'method' => 'GET'));
        $pagination     = null;
        $data['search'] = array_filter($data['search']);

        if ($data['search']) {
            $form->submit($data['search']);
            if ($form->isValid()) {
                //shorten url and redirect.
                if (isset($data['search']) && !isset($data['search']['parsed']) && $request->getQueryString()) {
                    parse_str(urldecode($request->getQueryString()), $parsedArray);
                    $parsedArray['fa_report_automated_email_search_admin']['parsed'] = true;
                    $parsedArray = array_filter(array_map('array_filter', $parsedArray));

                    return $this->redirectToRoute('fa_report_automated_email', $parsedArray);
                }
                $query = $this->getHistoryRepository('FaReportBundle:AutomatedEmailReportDaily')->getAutomatedEmailReportQuery($data['search'], $data['sorter'], $this->container);

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
            $this->get('session')->set('automated_email_report_export_criteria', serialize($criteria));
        } else {
            $this->get('session')->remove('automated_email_report_export_criteria');
        }

        $parameters = array(
            'heading'         => 'Automated email report',
            'form'            => $form->createView(),
            'pagination'      => $pagination,
            'sorter'          => $data['sorter'],
            'searchParams'    => $data['search'],
        );

        return $this->render('FaReportBundle:AutomatedEmailReportAdmin:index.html.twig', $parameters);
    }

    /**
     * Automated email report export to csv action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function exportAutomatedEmailReportToCsvAction(Request $request)
    {
        if (!$this->get('session')->has('automated_email_report_export_criteria')) {
            return parent::handleMessage('Please select report criteria.', 'fa_report_automated_email', array(), 'error');
        } else {
            CommonManager::setAdminBackUrl($request, $this->container);
            $backUrl = CommonManager::getAdminCancelUrl($this->container);
            exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->get('kernel')->getRootDir().'/console fa:automated-email:report:export-to-csv --criteria=\''.$this->get('session')->get('automated_email_report_export_criteria').'\' >/dev/null &');
            $searchParam = unserialize($this->get('session')->get('automated_email_report_export_criteria'));
            if (isset($searchParam['search']['csv_email']) && $searchParam['search']['csv_email']) {
                $message = $this->get('translator')->trans('Report csv will be generated and emailed to %email%.', array('%email%' => $searchParam['search']['csv_email']));
            } else {
                $message = $this->get('translator')->trans('It will take time to generate csv, Please check Download generated cvs list after 5 mins or as per search result count.');
            }
            return parent::handleMessage($message, ($backUrl ? $backUrl : 'fa_report_automated_email'));
        }
    }

    /**
     * Automated email report list csv action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function ajaxListAutomatedEmailReportCsvAction(Request $request)
    {
        $htmlContent   = '';

        if ($request->isXmlHttpRequest()) {
            $fileList    = CommonManager::listDirFileByDate($this->container->get('kernel')->getRootDir()."/../data/reports/automated_email/", array('csv'));
            $htmlContent = $this->renderView('FaReportBundle:AutomatedEmailReportAdmin:ajaxListAutomatedEmailReportCsv.html.twig', array('fileList' => $fileList));
            return new JsonResponse(array('htmlContent' => $htmlContent));
        } else {
            return new Response();
        }
    }

    /**
     * Automated email report delete csv action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function ajaxDeleteAutomatedEmailReportCsvAction(Request $request)
    {
        $htmlContent = '';
        $error       = '';

        if ($request->isXmlHttpRequest()) {
            $reportPath = $this->container->get('kernel')->getRootDir()."/../data/reports/automated_email/";
            $fileName   = $request->get('fileName');
            if (is_file($reportPath.$fileName)) {
                unlink($reportPath.$fileName);
                $fileList    = CommonManager::listDirFileByDate($reportPath, array('csv'));
                $htmlContent = $this->renderView('FaReportBundle:AutomatedEmailReportAdmin:ajaxListAutomatedEmailReportCsv.html.twig', array('fileList' => $fileList, 'csvDelete' => 1));
            }
            return new JsonResponse(array('htmlContent' => $htmlContent, 'error' => $error));
        } else {
            return new Response();
        }
    }

    /**
     * Download automated email report csv.
     *
     * @param Request $request A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function downloadAutomatedEmailReportCsvAction($fileName)
    {
        $filePath = $this->container->get('kernel')->getRootDir()."/../data/reports/automated_email/".$fileName;
        if (is_file($filePath)) {
            CommonManager::downloadFile($filePath, $fileName);
            return new Response();
        }
    }
}
