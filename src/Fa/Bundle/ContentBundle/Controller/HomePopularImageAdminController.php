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
use Fa\Bundle\ContentBundle\Entity\HomePopularImage;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\ContentBundle\Form\HomePopularImageAdminType;
use Fa\Bundle\ContentBundle\Form\HomePopularImageAdminSearchType;
use Fa\Bundle\AdminBundle\Controller\CrudController;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This controller is used for user management.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class HomePopularImageAdminController extends CrudController implements ResourceAuthorizationController
{
    /**
     * Get table name.
     *
     * @return string
     */
    public function getTableName()
    {
        return 'home_popular_image';
    }

    /**
     * Get word to be displayed on template.
     *
     * @return string
     */
    protected function getDisplayWord()
    {
        return 'Homepage What\'s Popular';
    }

    /**
     * Lists all home popular image entities.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);
        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaContentBundle:HomePopularImage'), $this->getRepositoryTable('FaContentBundle:HomePopularImage'), 'fa_content_home_popular_image_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        // initialize search manager service and fetch data based of filters
        $data['select_fields'] = array(
                                      'home_popular_image' => array('id', 'path', 'status', 'file_name','created_at'),
                                 );

        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaContentBundle:HomePopularImage'), $data);
        $query = $this->get('fa.sqlsearch.manager')->getQuery();

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(HomePopularImageAdminSearchType::class, null, array('action' => $this->generateUrl('home_popular_image_admin'), 'method' => 'GET'));

        if ($data['search']) {
            $form->submit($data['search']);
        }

        $parameters = array(
            'statusArray' => EntityRepository::getStatusArray($this->container),
            'heading'     => 'Homepage What\'s Popular',
            'form'        => $form->createView(),
            'pagination'  => $pagination,
            'sorter'      => $data['sorter'],
        );

        return $this->render('FaContentBundle:HomePopularImageAdmin:index.html.twig', $parameters);
    }
    
    /**
     * Creates a new record.
     *
     * @param Request $request A request object.
     *
     * @return Response A response object.
     */
    public function createAction(Request $request)
    {
        $entity      = $this->getEntity();
        $formManager = $this->get('fa.formmanager');
        
        $options =  array(
            'action' => $this->generateUrl($this->getRouteName('create')),
            'method' => 'POST'
        );
        
        $fqn = $this->getFQNForForms('fa_'.$this->getBundleAlias().'_'.$this->getTableName().'_admin');
        $form = $formManager->createForm($fqn, $entity, $options);
        
        if ($formManager->isValid($form)) {
            return $this->handleMessage($this->get('translator')->trans('%displayWord% was successfully added.', array('%displayWord%' => $this->getDisplayWord()), 'success'), ($form->get('saveAndNew')->isClicked() ? $this->getRouteName('new') : $this->getRouteName('')));
        }
        
        $parameters = array(
            'entity'  => $entity,
            'form'    => $form->createView(),
            'heading' => $this->get('translator')->trans('New %displayWord%', array('%displayWord%' => $this->getDisplayWord())),
        );
        
        return $this->render($this->getBundleName().':'.$this->getControllerName().':new.html.twig', $parameters);
    }
    
    /**
     * Edits an existing record.
     *
     * @param Request $request A request object.
     * @param integer $id      Id.
     *
     * @return Response A Response object.
     */
    public function updateAction(Request $request, $id)
    {
        $backUrl     = CommonManager::getAdminBackUrl($this->container);
        $formManager = $this->get('fa.formmanager');
        $entity      = $this->getRepository($this->getBundleName().':'.$this->getEntityName())->find($id);
        
        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find %bundleAlias%.', array('%bundleAlias%' => $this->getBundleAlias())));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', $this->getRouteName(''));
        }
        
        $options =  array(
            'action' => $this->generateUrl($this->getRouteName('update'), array('id' => $entity->getId())),
            'method' => 'PUT'
        );
        
        $fqn = $this->getFQNForForms('fa_'.$this->getBundleAlias().'_'.$this->getTableName().'_admin');
        $form = $formManager->createForm($fqn, $entity, $options);
        
        if ($formManager->isValid($form)) {
            $messageManager = $this->get('fa.message.manager');
            $messageManager->setFlashMessage($this->get('translator')->trans('%displayword% was successfully updated.', array('%displayword%' => $this->getDisplayWord())), 'success');
            if (empty($backUrl)) { 
                $backUrl = $this->generateUrl('home_popular_image_admin');
            }
            return $this->redirect($backUrl);
        }
        
        $parameters = array(
            'entity'  => $entity,
            'form'    => $form->createView(),
            'heading' => $this->get('translator')->trans('Edit %displayword%', array('%displayword%' => $this->getDisplayWord())),
        );
        
        return $this->render($this->getBundleName().':'.$this->getControllerName().':new.html.twig', $parameters);
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
        $entity        = $this->getRepository($this->getBundleName().':'.$this->getEntityName())->find($id);
        $deleteManager = $this->get('fa.deletemanager');
        
        try {
            if (!$entity) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find %bundleAlias%.', array('%bundleAlias%' => $this->getBundleAlias())));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', $this->getRouteName(''));
        }
        
        $oldFileName = $entity->getFileName();
        $file        = $entity->getAbsolutePath();
        
        $oldOverlayFileName = $entity->getOverlayFileName();
        $overlayFile        = $entity->getAbsolutePathForOverlay();
        
        // Count how many rules found with same image, delete image if only one rule found
        $data['query_filters'] = array('home_popular_image' => array('file_name' => $oldFileName, 'overlay_file_name' => $oldOverlayFileName));
        $this->get('fa.sqlsearch.manager')->init($this->getRepository($this->getBundleName().':'.$this->getEntityName()), $data);
        $imageCount = $this->get('fa.sqlsearch.manager')->getResultCount();
        
        // Delete rule
        $deleteManager->delete($entity);
        
        // Delete image from directory
        if ($imageCount <= 1) {
            if (file_exists($file)) {
                unlink($file);
                exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project      _path').'/console fa:remove:single-image-s3 --file_path=homepopular/'.$oldFileName.' >/dev/null &');
            }
            
            if (file_exists($overlayFile)) {
                unlink($overlayFile);
                exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project      _path').'/console fa:remove:single-image-s3 --file_path=homepopular/'.$oldOverlayFileName.' >/dev/null &');
            }
            
        }
        
        return parent::handleMessage($this->get('translator')->trans('%displayWord% was successfully deleted.', array('%displayWord%' => $this->getDisplayWord()), 'success'), ($backUrl ? $backUrl : $this->getRouteName('')));
    }
    
    /**
     * Deletes a record.
     *
     * @param Request $request A request object.
     * @param integer $id      Id.
     *
     * @return Response A response object.
     */
    public function deleteRightImageAction(Request $request, $id)
    {
        $backUrl       = CommonManager::getAdminBackUrl($this->container);
        $entity        = $this->getRepository($this->getBundleName().':'.$this->getEntityName())->find($id);
        $deleteManager = $this->get('fa.deletemanager');
        
        $oldOverlayFileName = $entity->getOverlayFileName();
        $overlayFile        = $entity->getAbsolutePathForOverlay();
        
        // Count how many rules found with same image, delete image if only one rule found
        $data['query_filters'] = array('home_popular_image' => array('overlay_file_name' => $oldOverlayFileName));
        $this->get('fa.sqlsearch.manager')->init($this->getRepository($this->getBundleName().':'.$this->getEntityName()), $data);
        $imageCount = $this->get('fa.sqlsearch.manager')->getResultCount();
        
        //update field value
        $entity->setOverlayFileName(null);
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush($entity);
        
        // Delete image from directory
        if ($imageCount <= 1) {
            if (file_exists($overlayFile)) {
                unlink($overlayFile);
                exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project      _path').'/console fa:remove:single-image-s3 --file_path=homepopular/'.$oldOverlayFileName.' >/dev/null &');
            }
        }
        
        return parent::handleMessage($this->get('translator')->trans('Right-Hand-Side Image was successfully deleted.', array('%displayWord%' => $this->getDisplayWord()), 'success'), ($backUrl ? $backUrl : $this->getRouteName('')));
    }
    
        
    public function getFQNForForms($formName)
    {
        // getName() symfony form function is removed After symfony 3, so to handle dynamic forms we need create array
        $formClassArray = [
            'fa_content_home_popular_image_admin' => HomePopularImageAdminType::class
        ];
        $formName = isset($formClassArray[$formName]) ? $formClassArray[$formName] : $formName;
        return $formName;
    }
}
