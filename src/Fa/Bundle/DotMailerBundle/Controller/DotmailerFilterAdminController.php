<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\DotMailerBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\DotMailerBundle\Repository\DotmailerRepository;
use Fa\Bundle\DotMailerBundle\Entity\DotmailerFilter;
use Fa\Bundle\AdminBundle\Controller\CrudController;
use Fa\Bundle\DotMailerBundle\Repository\DotmailerFilterRepository;
use Fa\Bundle\DotMailerBundle\Form\DotmailerFilterAdminType;
use Fa\Bundle\DotMailerBundle\Form\DotmailerFilterSearchAdminType;

/**
 * This controller is used for dotmailer management.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class DotmailerFilterAdminController extends CrudController implements ResourceAuthorizationController
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
        return 'dotmailer_filter';
    }

    /**
     * List dotmailer newsletters.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);
        $data                  = array();
        $data['pager']['page'] = $request->get('page', 1);

        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaDotMailerBundle:DotmailerFilter'), $this->getRepositoryTable('FaDotMailerBundle:DotmailerFilter'), 'fa_dotmailer_dotmailer_filter_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaDotMailerBundle:DotmailerFilter'), $data);
        $query = $this->get('fa.sqlsearch.manager')->getQuery();

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page, 10, 0, true);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(DotmailerFilterSearchAdminType::class, null, array('action' => $this->generateUrl('dotmailer_filter_admin'), 'method' => 'GET'));

        if ($data['search']) {
            $form->submit($data['search']);
        }

        $parameters = array(
            'heading'       => $this->get('translator')->trans('Approve/review marketing filter'),
            'pagination'    => $pagination,
            'form'          => $form->createView(),
            'sorter'        => $data['sorter'],
        );

        return $this->render('FaDotMailerBundle:DotmailerFilterAdmin:index.html.twig', $parameters);
    }

    /**
     * Search dotmailer newsletters.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createAction(Request $request)
    {
        $dotmailerFilter = new DotmailerFilter();
        $formManager     = $this->get('fa.formmanager');
        $form            = $formManager->createForm(DotmailerFilterAdminType::class, $dotmailerFilter, array('action' => $this->generateUrl('dotmailer_filter_create_admin')));

        if ('POST' === $request->getMethod()) {
            if ($formManager->isValid($form)) {
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(array('success' => '1', 'htmlContent' => ''));
                } else {
                    return $this->handleMessage($this->get('translator')->trans('Filter has been created successfully.'), 'dotmailer_filter_admin');
                }
            } else {
                if ($request->isXmlHttpRequest()) {
                    $htmlContent = $this->renderView('FaDotMailerBundle:DotmailerFilterAdmin:addEditForm.html.twig', array('form'   => $form->createView()));
                    return new JsonResponse(array('success' => '', 'htmlContent' => $htmlContent));
                }
            }
        }

        $parameters = array('form' => $form->createView());
        return $this->render('FaDotMailerBundle:DotmailerFilterAdmin:new.html.twig', $parameters);
    }

    /**
     * Approve filter from pending.
     *
     * @param Request $request A request object.
     * @param integer $id      Id.
     *
     * @return Response A response object.
     */
    public function approveAction(Request $request, $id)
    {
        CommonManager::setAdminBackUrl($request, $this->container);
        $backUrl         = CommonManager::getAdminBackUrl($this->container);
        $dotmailerFilter = $this->getRepository($this->getBundleName().':'.$this->getEntityName())->find($id);

        try {
            if (!$dotmailerFilter) {
                throw $this->createNotFoundException($this->get('translator')->trans('Unable to find %bundleAlias%.', array('%bundleAlias%' => $this->getBundleAlias())));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return $this->handleException($e, 'error', $this->getRouteName(''));
        }

        $dotmailerFilter->setStatus(DotmailerFilterRepository::STATUS_APPROVED);
        $this->getEntityManager()->persist($dotmailerFilter);
        $this->getEntityManager()->flush($dotmailerFilter);

        // export emails here after approve
        exec('nohup'.' '.$this->container->getParameter('fa.php.path').' '.$this->container->getParameter('project_path').'/console fa:dotmailer:export-filter --id='.$dotmailerFilter->getId().' >/dev/null &');

        return parent::handleMessage($this->get('translator')->trans('Filter has been sent for export in dotmailer please check the status after some time.', array(), 'success'), $this->getRouteName(''));
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
        CommonManager::setAdminBackUrl($request, $this->container);
        $backUrl       = CommonManager::getAdminCancelUrl($this->container);
        $deleteManager = $this->get('fa.deletemanager');
        $entities      = array();

        if ($id == '' || $id == 0) {
            if ($request->get('delete_filter_checkbox')) {
                $idsToDelete = $request->get('delete_filter_checkbox');
                if ($idsToDelete && is_array($idsToDelete) && count($idsToDelete) > 0) {
                    foreach ($idsToDelete as $key => $dotmailerId) {
                        $entity = $this->getRepository($this->getBundleName().':'.$this->getEntityName())->find($dotmailerId);
                        if ($entity) {
                            $entities[] = $entity;
                        }
                    }
                }
            }
        } else {
            $entity     = $this->getRepository($this->getBundleName().':'.$this->getEntityName())->find($id);
            $entities[] = $entity;
        }

        foreach ($entities as $key => $entity) {
            try {
                if (!$entity) {
                    throw $this->createNotFoundException($this->get('translator')->trans('Unable to find %bundleAlias%.', array('%bundleAlias%' => $this->getBundleAlias())));
                }
            } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
                return $this->handleException($e, 'error', $this->getRouteName(''));
            }

            try {
                $deleteManager->delete($entity);
            } catch (\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException $e) {
                return parent::handleMessage($this->get('translator')->trans("This record can not be removed from database because it's reference exists in database.", array(), 'error'), ($backUrl ? $backUrl : $this->getRouteName('')), array(), 'error');
            } catch (\Exception $e) {
                return parent::handleException($e, 'error', ($backUrl ? $backUrl : $this->getRouteName('')));
            }
        }

        return parent::handleMessage($this->get('translator')->trans('Record has been deleted successfully.', array(), 'success'), ($backUrl ? $backUrl : $this->getRouteName('')));
    }
}
