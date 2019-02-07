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
use Fa\Bundle\ReportBundle\Form\ProfilePackageRevenueReportSearchAdminType;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\PromotionBundle\Entity\Package;
use Fa\Bundle\ReportBundle\Repository\UserReportProfilePackageDailyRepository;

/**
 * This is default controller for dot mailer bundle.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class ProfilePackageRevenueReportAdminController extends CoreController implements ResourceAuthorizationController
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
        $entityCacheManager = $this->container->get('fa.entity.cache.manager');
        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getHistoryRepository('FaReportBundle:UserReport'), $this->getHistoryRepositoryTable('FaReportBundle:UserReport'), 'fa_report_profile_package_revenue_report_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();
        // initialize form manager service
        $formManager    = $this->get('fa.formmanager');
        $form           = $formManager->createForm(ProfilePackageRevenueReportSearchAdminType::class, null, array('action' => $this->generateUrl('fa_report_profile_package_revenue'), 'method' => 'GET'));
        $resultArray    = array();
        $pagination     = null;
        $isExport       = false;

        if ($this->container->get('session')->has('search_criteria')) {
            $this->container->get('session')->remove('search_criteria');
        }

        if ($data['search']) {
            $form->submit($data['search']);
            if ($form->isValid()) {
                $query = $this->getHistoryRepository('FaReportBundle:UserReport')->getPPRReportQuery($data['search'], $data['sorter']);

                // initialize pagination manager service and prepare listing with pagination based of data
                $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
                $this->get('fa.pagination.manager')->init($query, $page, 20, 0, true);
                $pagination = $this->get('fa.pagination.manager')->getPagination();

                if ($pagination->getNbResults()) {
                    foreach ($pagination->getCurrentPageResults() as $record) {
                        $processedRecord                          = $this->getHistoryRepository('FaReportBundle:UserReport')->processRecordForPPR($record, $data['search'], $this->container);
                        $userIdsArray[]                           = $processedRecord['user_id'];
                        $resultArray[$processedRecord['user_id']] = $processedRecord;
                    }


                    $categoryFields = array_keys(CommonManager::getHistoryRepository($this->container, 'FaReportBundle:UserReportProfilePackageDaily')->getPPRReportCategoryFieldsArray());
                    $isCategorySet  = false;
                    if (CommonManager::inArrayMulti($categoryFields, $data['search']['rus_report_columns']) && $isCategorySet == false) {
                        $categoryArray = CommonManager::getHistoryRepository($this->container, 'FaReportBundle:UserReportCategoryDaily')->getCategoryInWhichMaxAdPostedByUserIdAndDateRange($userIdsArray, $data['search']['rus_from_date'], $data['search']['rus_to_date']);
                        if ($categoryArray && count($categoryArray) > 0) {
                            foreach ($resultArray as $keyUserId => $valueFields) {
                                $resultArray[$keyUserId]['category'] = '';
                                $resultArray[$keyUserId]['class'] = '';
                                foreach ($categoryArray as $key => $values) {
                                    $categoryPath = CommonManager::getEntityRepository($this->container, 'FaEntityBundle:Category')->getCategoryPathArrayById($values['category_id'], false, $this->container);
                                    if (is_array($categoryPath)) {
                                        $counter       = 1;
                                        $isCategorySet = true;
                                        foreach ($categoryPath as $key => $value) {
                                            switch ($counter) {
                                                case 1:
                                                    $resultArray[$values['user_id']]['category'] = $value;
                                                    break;
                                                case 2:
                                                    $resultArray[$values['user_id']]['class'] = $value;
                                                    break;
                                            }
                                            $counter++;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    $adCountsFields = array_keys(CommonManager::getHistoryRepository($this->container, 'FaReportBundle:UserReportProfilePackageDaily')->getPPRReportDailyBasicFieldsArray());
                    if (CommonManager::inArrayMulti($adCountsFields, $data['search']['rus_report_columns'])) {
                        $adCountsArray = CommonManager::getHistoryRepository($this->container, 'FaReportBundle:UserReportDaily')->getDifferentSumByUserIdAndDateRange($userIdsArray, $data['search']['rus_from_date'], $data['search']['rus_to_date']);
                        if ($adCountsArray && is_array($adCountsArray)) {
                            foreach ($resultArray as $keyUserId => $valueFields) {
                                if (array_key_exists($keyUserId, $adCountsArray)) {
                                    foreach ($adCountsArray[$keyUserId] as $fieldName => $fieldValue) {
                                        $resultArray[$keyUserId][$fieldName] = $fieldValue;
                                    }
                                }
                            }
                        }
                    }


                    $packageFields = array_keys(CommonManager::getHistoryRepository($this->container, 'FaReportBundle:UserReportProfilePackageDaily')->getPPRReportProfilePackageFieldsArray());
                    if (CommonManager::inArrayMulti($packageFields, $data['search']['rus_report_columns'])) {
                        if ($userIdsArray && is_array($userIdsArray)) {
                            $packageArray = CommonManager::getHistoryRepository($this->container, 'FaReportBundle:UserReportProfilePackageDaily')->getPackageDetailsByUserIdAndDateRange($userIdsArray, $data['search']['rus_from_date'], $data['search']['rus_to_date']);
                            if ($packageArray && is_array($packageArray)) {
                                foreach ($resultArray as $keyUserId => $valueFields) {
                                    if (array_key_exists($keyUserId, $packageArray)) {
                                        $packageName = CommonManager::getHistoryRepository($this->container, 'FaReportBundle:UserReportProfilePackageDaily')->getMostRecentPackageNameByUserIdAndDateRange($keyUserId, $data['search']['rus_from_date'], $data['search']['rus_to_date']);
                                        $resultArray[$keyUserId]['package_name'] = $packageName;
                                        $resultArray[$keyUserId]['package_value_gross'] = CommonManager::formatCurrency($packageArray[$keyUserId]['package_value_gross'], $this->container);
                                        $resultArray[$keyUserId]['package_value_net'] = CommonManager::formatCurrency(CommonManager::getNetAmountFromGrossAmount($packageArray[$keyUserId]['package_value_gross'], $this->container), $this->container);
                                        $resultArray[$keyUserId]['package_category_id'] = $entityCacheManager->getEntityNameById('FaEntityBundle:Category', $packageArray[$keyUserId]['package_category_id']);
                                    }
                                }
                            }

                            $packageRevenueArray = CommonManager::getHistoryRepository($this->container, 'FaReportBundle:UserReportProfilePackageDaily')->getPackageRevenueDetailsByUserIdAndDateRange($userIdsArray, $data['search']['rus_from_date'], $data['search']['rus_to_date']);
                            if ($packageRevenueArray && is_array($packageRevenueArray)) {
                                foreach ($resultArray as $keyUserId => $valueFields) {
                                    if (array_key_exists($keyUserId, $packageRevenueArray)) {
                                        $resultArray[$keyUserId]['package_transaction_revenue_gross'] = CommonManager::formatCurrency($packageRevenueArray[$keyUserId]['package_transaction_revenue_gross'], $this->container);
                                        $resultArray[$keyUserId]['package_transaction_revenue_net'] = CommonManager::formatCurrency(CommonManager::getNetAmountFromGrossAmount($packageRevenueArray[$keyUserId]['package_transaction_revenue_gross'], $this->container), $this->container);
                                    }
                                }
                            }

                            $packageCancelledArray = CommonManager::getHistoryRepository($this->container, 'FaReportBundle:UserReportProfilePackageDaily')->getPackageCancelledCountsByUserIdAndDateRange($userIdsArray, $data['search']['rus_from_date'], $data['search']['rus_to_date']);
                            if ($packageCancelledArray && is_array($packageCancelledArray)) {
                                foreach ($resultArray as $keyUserId => $valueFields) {
                                    $resultArray[$keyUserId]['package_cancelled'] = 'No';
                                    if (array_key_exists($keyUserId, $packageCancelledArray)) {
                                        $totalCancelled = $packageCancelledArray[$keyUserId]['total_cancelled_packages'];
                                        if ($totalCancelled > 0) {
                                            $resultArray[$keyUserId]['package_cancelled'] = 'Yes';
                                        }
                                    }
                                }
                            }
                        }
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
            'heading'         => 'Profile Package Revenue Report',
            'form'            => $form->createView(),
            'pagination'      => $pagination,
            'sorter'          => $data['sorter'],
            'searchParams'    => $data['search'],
            'reportDataArray' => $resultArray,
            'isExport'        => $isExport,
        );

        return $this->render('FaReportBundle:ProfilePackageRevenueReportAdmin:index.html.twig', $parameters);
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
            return parent::handleMessage('Please select report criteria.', 'fa_report_profile_package_revenue', array(), 'error');
        } else {
            $serilizedSearchCriteria = serialize($this->container->get('session')->get('search_criteria'));
            $serilizedSearchCriteria = trim($serilizedSearchCriteria);
            CommonManager::setAdminBackUrl($request, $this->container);
            $backUrl = CommonManager::getAdminCancelUrl($this->container);
            exec('nohup'.' '.$this->container->getParameter('fa.php.path').' bin/console fa:export-profile-package-revenue-report --criteria=\''.$serilizedSearchCriteria.'\' >/dev/null &');
            $searchParam = $this->get('session')->get('search_criteria');
            if (isset($searchParam['rus_csv_email']) && $searchParam['rus_csv_email']) {
                $message = $this->get('translator')->trans('Report csv will be generated and emailed to %email%.', array('%email%' => $searchParam['rus_csv_email']));
            } else {
                $message = $this->get('translator')->trans('It will take time to generate csv, Please check Download generated cvs list after 5 mins or as per search result count.');
            }
            return parent::handleMessage($message, ($backUrl ? $backUrl : 'fa_report_user'));
        }
    }

    /**
     * Ad report list csv action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function ajaxListPPRReportCsvAction(Request $request)
    {
        $htmlContent   = '';

        if ($request->isXmlHttpRequest()) {
            $fileList    = CommonManager::listDirFileByDate($this->container->get('kernel')->getRootDir()."/../data/reports/profile_package_revenue/", array('csv'));
            $htmlContent = $this->renderView('FaReportBundle:ProfilePackageRevenueReportAdmin:ajaxListPPRReportCsv.html.twig', array('fileList' => $fileList));
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
    public function downloadPPRReportCsvAction($fileName)
    {
        $filePath = $this->container->get('kernel')->getRootDir()."/../data/reports/profile_package_revenue/".$fileName;
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
    public function ajaxDeletePPRReportCsvAction(Request $request)
    {
        $htmlContent = '';
        $error       = '';

        if ($request->isXmlHttpRequest()) {
            $reportPath = $this->container->get('kernel')->getRootDir()."/../data/reports/profile_package_revenue/";
            $fileName   = $request->get('fileName');
            if (is_file($reportPath.$fileName)) {
                unlink($reportPath.$fileName);
                $fileList    = CommonManager::listDirFileByDate($reportPath, array('csv'));
                $htmlContent = $this->renderView('FaReportBundle:ProfilePackageRevenueReportAdmin:ajaxListPPRReportCsv.html.twig', array('fileList' => $fileList, 'csvDelete' => 1));
            }
            return new JsonResponse(array('htmlContent' => $htmlContent, 'error' => $error));
        } else {
            return new Response();
        }
    }
}
