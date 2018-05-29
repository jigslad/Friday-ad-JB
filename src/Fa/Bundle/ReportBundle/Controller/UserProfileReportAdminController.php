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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\ReportBundle\Form\UserReportSearchAdminType;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\PromotionBundle\Entity\Package;
use Fa\Bundle\ReportBundle\Form\UserProfileReportSearchAdminType;

/**
 * This is default controller for dot mailer bundle.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserProfileReportAdminController extends CoreController implements ResourceAuthorizationController
{
    /**
     * Home page action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function indexAction(Request $request)
    {
        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getHistoryRepository('FaReportBundle:UserReport'), $this->getHistoryRepositoryTable('FaReportBundle:UserReport'), 'fa_report_user_profile_report_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();
        // initialize form manager service
        $formManager    = $this->get('fa.formmanager');
        $form           = $formManager->createForm(UserProfileReportSearchAdminType::class, null, array('action' => $this->generateUrl('fa_report_user_profile'), 'method' => 'GET'));
        $resultArray    = array();
        $pagination     = null;
        $isExport       = false;

        if ($this->container->get('session')->has('search_criteria')) {
            $this->container->get('session')->remove('search_criteria');
        }

        if ($data['search']) {
            $form->submit($data['search']);
            if ($form->isValid()) {
                $query = $this->getHistoryRepository('FaReportBundle:UserReport')->getUserProfileReportQuery($data['search'], $data['sorter']);

                //initialize pagination manager service and prepare listing with pagination based of data
                $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
                $this->get('fa.pagination.manager')->init($query, $page, 20, 0, true);
                $pagination = $this->get('fa.pagination.manager')->getPagination();

                if ($pagination->getNbResults()) {
                    foreach ($pagination->getCurrentPageResults() as $record) {
                        $processedRecord                          = $this->getHistoryRepository('FaReportBundle:UserReport')->processUserProfileRecord($record, $data['search'], $this->container);
                        $userIdsArray[]                           = $processedRecord['user_id'];
                        $resultArray[$processedRecord['user_id']] = $processedRecord;
                    }
                }
            }
        }

        if (count($resultArray) > 0) {
            $isExport       = true;
            $searchCriteria = $data['search'];
            if (isset($searchCriteria['_token'])) {
                unset($searchCriteria['_token']);
            }
            if (isset($data['sorter'])) {
                $searchCriteria['sorter'] = $data['sorter'];
            }
            $this->container->get('session')->set('search_criteria', $searchCriteria);
        }

        $parameters = array(
            'heading'         => 'Users Profile Report',
            'form'            => $form->createView(),
            'pagination'      => $pagination,
            'sorter'          => $data['sorter'],
            'searchParams'    => $data['search'],
            'reportDataArray' => $resultArray,
            'isExport'        => $isExport,
        );

        return $this->render('FaReportBundle:UserProfileReportAdmin:index.html.twig', $parameters);
    }

    /**
     * User report export to csv action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function exportToCSVAction(Request $request)
    {
        if (!$this->get('session')->has('search_criteria')) {
            return parent::handleMessage('Please select report criteria.', 'fa_report_user_profile', array(), 'error');
        } else {
            $serilizedSearchCriteria = serialize($this->container->get('session')->get('search_criteria'));
            $serilizedSearchCriteria = trim($serilizedSearchCriteria);
            CommonManager::setAdminBackUrl($request, $this->container);
            $backUrl = CommonManager::getAdminCancelUrl($this->container);
            exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->get('kernel')->getRootDir().'/console fa:export-user-profile-report --criteria=\''.$serilizedSearchCriteria.'\' >/dev/null &');
            $searchParam = $this->get('session')->get('search_criteria');
            if (isset($searchParam['rus_csv_email']) && $searchParam['rus_csv_email']) {
                $message = $this->get('translator')->trans('Report csv will be generated and emailed to %email%.', array('%email%' => $searchParam['rus_csv_email']));
            } else {
                $message = $this->get('translator')->trans('It will take time to generate csv, Please check Download generated cvs list after 5 mins or as per search result count.');
            }
            return parent::handleMessage($message, ($backUrl ? $backUrl : 'fa_report_user_profile'));
        }
    }

    /**
     * Ad report list csv action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function ajaxListUserProfileReportCsvAction(Request $request)
    {
        $htmlContent   = '';

        if ($request->isXmlHttpRequest()) {
            $fileList    = CommonManager::listDirFileByDate($this->container->get('kernel')->getRootDir()."/../data/reports/user_profile/", array('csv'));
            $htmlContent = $this->renderView('FaReportBundle:UserProfileReportAdmin:ajaxListUserProfileReportCsv.html.twig', array('fileList' => $fileList));
            return new JsonResponse(array('htmlContent' => $htmlContent));
        } else {
            return new Response();
        }
    }

    /**
     * Download message attachment
     *
     * @param Request $request A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function downloadUserProfileReportCsvAction($fileName)
    {
        $filePath = $this->container->get('kernel')->getRootDir()."/../data/reports/user_profile/".$fileName;
        if (is_file($filePath)) {
            CommonManager::downloadFile($filePath, $fileName);
            return new Response();
        }
    }

    /**
     * Ad report delete csv action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function ajaxDeleteUserProfileReportCsvAction(Request $request)
    {
        $htmlContent = '';
        $error       = '';

        if ($request->isXmlHttpRequest()) {
            $reportPath = $this->container->get('kernel')->getRootDir()."/../data/reports/user_profile/";
            $fileName   = $request->get('fileName');
            if (is_file($reportPath.$fileName)) {
                unlink($reportPath.$fileName);
                $fileList    = CommonManager::listDirFileByDate($reportPath, array('csv'));
                $htmlContent = $this->renderView('FaReportBundle:UserProfileReportAdmin:ajaxListUserProfileReportCsv.html.twig', array('fileList' => $fileList, 'csvDelete' => 1));
            }
            return new JsonResponse(array('htmlContent' => $htmlContent, 'error' => $error));
        } else {
            return new Response();
        }
    }
}
