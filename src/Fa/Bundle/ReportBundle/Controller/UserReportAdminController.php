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

/**
 * This is default controller for dot mailer bundle.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class UserReportAdminController extends CoreController implements ResourceAuthorizationController
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
        $this->get('fa.searchfilters.manager')->init($this->getHistoryRepository('FaReportBundle:UserReport'), $this->getHistoryRepositoryTable('FaReportBundle:UserReport'), 'fa_report_user_report_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();
        // initialize form manager service
        $formManager    = $this->get('fa.formmanager');
        $form           = $formManager->createForm(UserReportSearchAdminType::class, null, array('action' => $this->generateUrl('fa_report_user'), 'method' => 'GET'));
        $resultArray    = array();
        $pagination     = null;
        $isExport       = false;

        if ($this->container->get('session')->has('search_criteria')) {
            $this->container->get('session')->remove('search_criteria');
        }

        if ($data['search']) {
            $form->submit($data['search']);
            if ($form->isValid()) {
                if ($data['search']['rus_report_type'] == 'user_wise') {
                    $query = $this->getHistoryRepository('FaReportBundle:UserReport')->getUserReportQuery($data['search'], $data['sorter']);

                    // initialize pagination manager service and prepare listing with pagination based of data
                    $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
                    $this->get('fa.pagination.manager')->init($query, $page, 20, 0, true);
                    $pagination = $this->get('fa.pagination.manager')->getPagination();

                    if ($pagination->getNbResults()) {
                        foreach ($pagination->getCurrentPageResults() as $record) {
                            $processedRecord                          = $this->getHistoryRepository('FaReportBundle:UserReport')->processRecord($record, $data['search'], $this->container);
                            $userIdsArray[]                           = $processedRecord['user_id'];
                            $resultArray[$processedRecord['user_id']] = $processedRecord;
                        }

                        $categoryFields = array_keys(CommonManager::getUserReportCategoryFieldsArray());
                        $isCategorySet  = false;
                        if (CommonManager::inArrayMulti($categoryFields, $data['search']['rus_report_columns']) && $isCategorySet == false) {
                            $categoryArray = CommonManager::getHistoryRepository($this->container, 'FaReportBundle:UserReportCategoryDaily')->getCategoryInWhichMaxAdPostedByUserIdAndDateRange($userIdsArray, $data['search']['rus_from_date'], $data['search']['rus_to_date']);
                            if ($categoryArray && count($categoryArray) > 0) {
                                foreach ($resultArray as $keyUserId => $valueFields) {
                                    $resultArray[$keyUserId]['category'] = '';
                                    $resultArray[$keyUserId]['class'] = '';
                                    $resultArray[$keyUserId]['subclass'] = '';
                                    $resultArray[$keyUserId]['sub_sub_class'] = '';
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
                                                    case 3:
                                                        $resultArray[$values['user_id']]['subclass'] = $value;
                                                        break;
                                                    case 4:
                                                        $resultArray[$values['user_id']]['sub_sub_class'] = $value;
                                                        break;
                                                }
                                                $counter++;
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        $editionFields = array_keys(CommonManager::getUserReportEditionFieldsArray());
                        if (CommonManager::inArrayMulti($editionFields, $data['search']['rus_report_columns'])) {
                            if ($userIdsArray && is_array($userIdsArray)) {
                                $editionArray = CommonManager::getHistoryRepository($this->container, 'FaReportBundle:UserReportEditionDaily')->getEditionInWhichMaxAdPostedByUserIdAndDateRange($userIdsArray, $data['search']['rus_from_date'], $data['search']['rus_to_date']);
                                $entityCacheManager = $this->container->get('fa.entity.cache.manager');
                                foreach ($resultArray as $keyUserId => $valueFields) {
                                    $resultArray[$keyUserId]['edition'] = '';
                                    if ($editionArray && is_array($editionArray)) {
                                        foreach ($editionArray as $key => $values) {
                                            if ($keyUserId == $values['user_id']) {
                                                $editionName = $entityCacheManager->getEntityNameById('FaAdBundle:PrintEdition', $values['edition_id']);
                                                $resultArray[$values['user_id']]['edition'] = $editionName;
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        $packageFields = array_keys(CommonManager::getUserReportProfilePackageFieldsArray());
                        if (CommonManager::inArrayMulti($packageFields, $data['search']['rus_report_columns'])) {
                            if ($userIdsArray && is_array($userIdsArray)) {
                                $packageArray = CommonManager::getHistoryRepository($this->container, 'FaReportBundle:UserReportProfilePackageDaily')->getPackageDetailsByUserIdAndDateRange($userIdsArray, $data['search']['rus_from_date'], $data['search']['rus_to_date']);
                                if ($packageArray && is_array($packageArray)) {
                                    foreach ($resultArray as $keyUserId => $valueFields) {
                                        if (array_key_exists($keyUserId, $packageArray)) {
                                            $packageName = CommonManager::getHistoryRepository($this->container, 'FaReportBundle:UserReportProfilePackageDaily')->getMostRecentPackageNameByUserIdAndDateRange($keyUserId, $data['search']['rus_from_date'], $data['search']['rus_to_date']);
                                            $resultArray[$keyUserId]['package_name'] = $packageName;
                                            if (isset($packageArray[$keyUserId]['package_revenue'])) {
                                                $resultArray[$keyUserId]['package_revenue'] = CommonManager::formatCurrency($packageArray[$keyUserId]['package_revenue'], $this->container);
                                            } else {
                                                $resultArray[$keyUserId]['package_revenue'] = CommonManager::formatCurrency(0, $this->container);
                                            }
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
                } else {
                    $reportBasicFields    = array_keys(CommonManager::getUserReportBasicFieldsArray());
                    $reportBooleanFields  = array_keys(CommonManager::getUserReportBooleanFieldsArray());
                    $reportDateFields     = array_keys(CommonManager::getUserReportDateFieldsArray());
                    $reportDailyFields    = array_keys(CommonManager::getUserReportDailyBasicFieldsArray());
                    $reportCategoryFields = array_keys(CommonManager::getUserReportCategoryFieldsArray());
                    $reportEditionFields  = array_keys(CommonManager::getUserReportEditionFieldsArray());
                    $reportPackageFields  = array_keys(CommonManager::getUserReportProfilePackageFieldsArray());
                    $reportAllowedFields  = array_merge($reportBasicFields, $reportBooleanFields, $reportDateFields, $reportDailyFields, $reportCategoryFields, $reportEditionFields, $reportPackageFields);
                    $selectedColumns      = $data['search']['rus_report_columns'];
                    unset($data['search']['rus_report_columns']);
                    foreach ($selectedColumns as $key => $value) {
                        if (in_array($value, $reportAllowedFields)) {
                            $data['search']['rus_report_columns'][] = $value;
                        }
                    }

                    if (CommonManager::inArrayMulti($reportDailyFields, $data['search']['rus_report_columns'])) {
                        $resultArray = $this->getHistoryRepository('FaReportBundle:UserReportDaily')->getUserReportDailyTotalSum($data['search']);
                    } else {
                        $resultArray[0] = array();
                    }

                    $newlySignupArray            = array();
                    $booleanAndOtherFieldsArray  = array();
                    $categoryAndEditionDataArray = $this->getHistoryRepository('FaReportBundle:UserReport')->getCategoryAndEditionDataArray($data['search'], $this->container);

                    if (CommonManager::inArrayMulti($reportBooleanFields, $data['search']['rus_report_columns'])) {
                        $booleanAndOtherFieldsArray = $this->getHistoryRepository('FaReportBundle:UserReport')->getBooleanAndOtherFieldsSumQuery($data['search'])->getResult();
                    }

                    if (CommonManager::inArrayMulti(array('is_new', 'signup_date'), $data['search']['rus_report_columns'])) {
                        $newlySignupArray = $this->getHistoryRepository('FaReportBundle:UserReport')->getNewlySignupUsersQuery($data['search'])->getResult();
                    }

                    if ($categoryAndEditionDataArray && count($categoryAndEditionDataArray) > 0) {
                        $resultArray[0] = array_merge($resultArray[0], $categoryAndEditionDataArray);
                    }

                    if ($booleanAndOtherFieldsArray && count($booleanAndOtherFieldsArray) > 0) {
                        $resultArray[0] = array_merge($resultArray[0], $booleanAndOtherFieldsArray[0]);
                    }

                    if ($newlySignupArray && count($newlySignupArray) > 0) {
                        $resultArray[0]           = array_merge($resultArray[0], $newlySignupArray[0]);
                        $resultArray[0]['is_new'] = $newlySignupArray[0]['signup_date'];
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
            'heading'         => 'Users Report',
            'form'            => $form->createView(),
            'pagination'      => $pagination,
            'sorter'          => $data['sorter'],
            'searchParams'    => $data['search'],
            'reportDataArray' => $resultArray,
            'isExport'        => $isExport,
        );

        return $this->render('FaReportBundle:UserReportAdmin:index.html.twig', $parameters);
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
            return parent::handleMessage('Please select report criteria.', 'fa_report_user', array(), 'error');
        } else {
            $serilizedSearchCriteria = serialize($this->container->get('session')->get('search_criteria'));
            $serilizedSearchCriteria = trim($serilizedSearchCriteria);
            CommonManager::setAdminBackUrl($request, $this->container);
            $backUrl = CommonManager::getAdminCancelUrl($this->container);
            exec('nohup'.' '.$this->container->getParameter('fa.php.path').' bin/console fa:export-user-report --criteria=\''.$serilizedSearchCriteria.'\' >/dev/null &');
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
    public function ajaxListUserReportCsvAction(Request $request)
    {
        $htmlContent   = '';

        if ($request->isXmlHttpRequest()) {
            $fileList    = CommonManager::listDirFileByDate($this->container->get('kernel')->getRootDir()."/../data/reports/user/", array('csv'));
            $htmlContent = $this->renderView('FaReportBundle:UserReportAdmin:ajaxListUserReportCsv.html.twig', array('fileList' => $fileList));
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
    public function downloadUserReportCsvAction($fileName)
    {
        $filePath = $this->container->get('kernel')->getRootDir()."/../data/reports/user/".$fileName;
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
    public function ajaxDeleteUserReportCsvAction(Request $request)
    {
        $htmlContent = '';
        $error       = '';

        if ($request->isXmlHttpRequest()) {
            $reportPath = $this->container->get('kernel')->getRootDir()."/../data/reports/user/";
            $fileName   = $request->get('fileName');
            if (is_file($reportPath.$fileName)) {
                unlink($reportPath.$fileName);
                $fileList    = CommonManager::listDirFileByDate($reportPath, array('csv'));
                $htmlContent = $this->renderView('FaReportBundle:UserReportAdmin:ajaxListUserReportCsv.html.twig', array('fileList' => $fileList, 'csvDelete' => 1));
            }
            return new JsonResponse(array('htmlContent' => $htmlContent, 'error' => $error));
        } else {
            return new Response();
        }
    }
}
