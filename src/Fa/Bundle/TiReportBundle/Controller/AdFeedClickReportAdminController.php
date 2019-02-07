<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\TiReportBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Symfony\Component\HttpFoundation\Request;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\Query;
use Fa\Bundle\TiReportBundle\Entity\AdFeedClickReportDaily;
use Fa\Bundle\TiReportBundle\Repository\AdFeedClickReportDailyRepository;
use Fa\Bundle\TiReportBundle\Form\AdFeedClickSearchAdminType;

/**
 * This is default controller for dot mailer bundle.
 *
 * @author Mohit Chauhan <mohitc@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class AdFeedClickReportAdminController extends CoreController implements ResourceAuthorizationController
{
    /**
     * Ad report action.
     *
     * @param Request $request A Request object .
     *
     * @param Response A Response object.
     */
    public function indexAction(Request $request)
    {
        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getTiHistoryRepository('FaTiReportBundle:AdFeedClickReportDaily'), $this->getTiHistoryRepositoryTable('FaTiReportBundle:AdFeedClickReportDaily'), 'fa_ti_report_ad_feed_click_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();
        // initialize form manager service
        $formManager    = $this->get('fa.formmanager');
        $form           = $formManager->createForm(AdFeedClickSearchAdminType::class, null, array('action' => $this->generateUrl('fa_ti_ad_feed_click_report'), 'method' => 'GET'));
        $pagination     = null;
        $data['search'] = array_filter($data['search']);

        if ($data['search']) {
            $form->submit($data['search']);
            if ($form->isValid()) {
                $query = $this->getTiHistoryRepository('FaTiReportBundle:AdFeedClickReportDaily')->getAdFeedClickReportDailyQuery($data['search'], $data['sorter'], $this->container);

                if ($data['search']['feed_report_type'] == 'all') {
                    $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Fa\Bundle\TiReportBundle\Walker\AdFeedClickReportSqlWalker');
                    $query->setHint("adFeedClickReportRemoveId.ad_feed_site_id", true);
                } else {
                    $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Fa\Bundle\TiReportBundle\Walker\AdFeedClickReportSqlWalker');
                    $query->setHint("adFeedClickReportRemoveId.ad_id", true);
                }

                // initialize pagination manager service and prepare listing with pagination based of data
                $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
                $this->get('fa.pagination.manager')->init($query, $page, 20, 0, true);
                $pagination = $this->get('fa.pagination.manager')->getPagination();
            }
            $data['search']['report_columns'] = array_keys(AdFeedClickReportDailyRepository::getAdFeedClickReportFields());
            asort($data['search']['report_columns']);
        }

        // store criteria in session for export.
        if ($pagination && $pagination->getNbResults()) {
            if (isset($data['search']['_token'])) {
                unset($data['search']['_token']);
            }
            $criteria = array('search' => $data['search'], 'sort' => $data['sorter']);
            $this->get('session')->set('ad_feed_click_report_export_criteria', serialize($criteria));
        } else {
            $this->get('session')->remove('ad_feed_click_report_export_criteria');
        }

        if (isset($data['search']) && isset($data['search']['feed_report_type']) && $data['search']['feed_report_type'] == 'all') {
            $key = array_search('ad_id', $data['search']['report_columns']);
            if ($key !== false) {
                unset($data['search']['report_columns'][$key]);
            }
        }

        $parameters = array(
            'heading'         => 'TI Track-Back Ad Report',
            'form'            => $form->createView(),
            'pagination'      => $pagination,
            'sorter'          => $data['sorter'],
            'searchParams'    => $data['search'],
            'page'            => (isset($page) ? $page : 1),
        );

        return $this->render('FaTiReportBundle:AdFeedClickReportAdmin:index.html.twig', $parameters);
    }

    /**
     * Ad feed click report export to csv action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function exportAdFeedClickReportToCsvAction(Request $request)
    {
        if (!$this->get('session')->has('ad_feed_click_report_export_criteria')) {
            return parent::handleMessage('Please select report criteria.', 'fa_report_ad_feed_click', array(), 'error');
        } else {
            CommonManager::setAdminBackUrl($request, $this->container);
            $backUrl = CommonManager::getAdminCancelUrl($this->container);
            exec('nohup'.' '.$this->container->getParameter('fa.php.path').' bin/console fa:ti:export-ad-feed-click-report --criteria=\''.$this->get('session')->get('ad_feed_click_report_export_criteria').'\' >/dev/null &');
            $searchParam = unserialize($this->get('session')->get('ad_feed_click_report_export_criteria'));
            if (isset($searchParam['search']['csv_email']) && $searchParam['search']['csv_email']) {
                $message = $this->get('translator')->trans('Report csv will be generated and emailed to %email%.', array('%email%' => $searchParam['search']['csv_email']));
            } else {
                $message = $this->get('translator')->trans('It will take time to generate csv, Please check Download generated cvs list after 5 mins or as per search result count.');
            }
            return parent::handleMessage($message, ($backUrl ? $backUrl : 'fa_report_ad_feed_click'));
        }
    }

    /**
     * Ad report list csv action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function ajaxListAdFeedClickReportCsvAction(Request $request)
    {
        $htmlContent   = '';

        if ($request->isXmlHttpRequest()) {
            $fileList    = CommonManager::listDirFileByDate($this->container->get('kernel')->getRootDir()."/../data/reports/adFeedClick/", array('csv'));
            $htmlContent = $this->renderView('FaTiReportBundle:AdFeedClickReportAdmin:ajaxListAdFeedClickReportCsv.html.twig', array('fileList' => $fileList));
            return new JsonResponse(array('htmlContent' => $htmlContent));
        } else {
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
    public function ajaxDeleteAdFeedClickReportCsvAction(Request $request)
    {
        $htmlContent = '';
        $error       = '';

        if ($request->isXmlHttpRequest()) {
            $reportPath = $this->container->get('kernel')->getRootDir()."/../data/reports/adFeedClick/";
            $fileName   = $request->get('fileName');
            if (is_file($reportPath.$fileName)) {
                unlink($reportPath.$fileName);
                $fileList    = CommonManager::listDirFileByDate($reportPath, array('csv'));
                $htmlContent = $this->renderView('FaTiReportBundle:AdFeedClickReportAdmin:ajaxListAdFeedClickReportCsv.html.twig', array('fileList' => $fileList, 'csvDelete' => 1));
            }
            return new JsonResponse(array('htmlContent' => $htmlContent, 'error' => $error));
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
    public function downloadAdFeedClickReportCsvAction($fileName)
    {
        $filePath = $this->container->get('kernel')->getRootDir()."/../data/reports/adFeedClick/".$fileName;
        if (is_file($filePath)) {
            CommonManager::downloadFile($filePath, $fileName);
            return new Response();
        }
    }
}
