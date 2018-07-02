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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fa\Bundle\AdminBundle\Controller\CrudController;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\ContentBundle\Repository\SeoToolRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\ContentBundle\Form\SeoToolSearchAdminType;

/**
 * This controller is used for static page management.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class SeoToolAdminController extends CrudController implements ResourceAuthorizationController
{
    /**
     * Get table name.
     *
     * @return object
     */
    protected function getTableName()
    {
        return 'seo_tool';
    }

    /**
     * Lists all SeoTool entities.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);
        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaContentBundle:SeoTool'), $this->getRepositoryTable('FaContentBundle:SeoTool'), 'fa_content_seo_tool_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        // initialize search manager service and fetch data based of filters
        $data['query_joins']   = array(
            'seo_tool' => array(
                'category' => array('type' => 'left'),
                'seo_tool_popular_search' => array('type' => 'left'),
                'seo_tool_top_link' => array('type' => 'left'),
            )
        );
        $data['select_fields']  = array(
            'seo_tool' => array('id', 'page', 'status', 'h1_tag', 'meta_keywords', 'page_title'),
            'category' => array('name as category_name')
        );

        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaContentBundle:SeoTool'), $data);
        $queryBuilder = $this->get('fa.sqlsearch.manager')->getQueryBuilder();
        $queryBuilder->distinct(SeoToolRepository::ALIAS.'.id');
        $query = $this->get('fa.sqlsearch.manager')->getQuery();

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page, 10, 0, true);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(SeoToolSearchAdminType::class, null, array('action' => $this->generateUrl('seo_tool_admin'), 'method' => 'GET'));

        if ($data['search']) {
            $form->submit($data['search']);
        }

        $parameters = array(
            'heading'    => $this->get('translator')->trans('Seo Tools'),
            'form'       => $form->createView(),
            'pagination' => $pagination,
            'sorter'     => $data['sorter'],
        );

        return $this->render('FaContentBundle:SeoToolAdmin:index.html.twig', $parameters);
    }

    public function adsTxtAction(Request $request)
    {
        
        $errorMsg         = '';
        $content = '';
        $adsTxtFile          = $this->container->get('kernel')->getRootDir().'/../data/ads.txt';

        if ($request->ismethod('post')) {
            $filePostContent = $request->get('file_text');
            file_put_contents($adsTxtFile, "");
            file_put_contents($adsTxtFile, $filePostContent);
            return $this->handleMessage($this->get('translator')->trans('Ads txt was successfully updated.', array(), 'success'),'ads_txt');
        } 
        $content = file_get_contents($adsTxtFile);

        $parameters = array(
            'heading'    => $this->get('translator')->trans('Ads Txt'),
            'content'     => $content,
        );

        return $this->render('FaContentBundle:SeoToolAdmin:adstxt.html.twig', $parameters);
    }

    public function uploadCsvAction(Request $request)
    {
        $csvColumnsArray = array();
        if ($request->ismethod('post')) {
            $errorMsg         = '';
            $webPath          = $this->container->get('kernel')->getRootDir().'/../web';
            $objUploadedFile  = $request->files->get('objCSVFileTopLink');
            if (!$objUploadedFile) {
                $objUploadedFile  = $request->files->get('objCSVFilePopularSearch');
            }
            $fileOriginalName = $objUploadedFile->getClientOriginalName();
            $fileExtension    = substr(strrchr($fileOriginalName,'.'),1);
            $tmpFilePath      = $webPath.DIRECTORY_SEPARATOR.$this->container->getParameter('fa.ad.image.tmp.dir');

            if ($fileExtension == 'csv') {
                //upload file.
                $objUploadedFile->move($tmpFilePath, $fileOriginalName);
                $objFile    = fopen($tmpFilePath.'/'.$fileOriginalName,"r");
                $rowCounter = 0;
                while(! feof($objFile))
                {
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

            unlink($tmpFilePath.'/'.$fileOriginalName);
        }

        return new JsonResponse(array('data' => $csvColumnsArray, 'error' => $errorMsg));
    }
}
