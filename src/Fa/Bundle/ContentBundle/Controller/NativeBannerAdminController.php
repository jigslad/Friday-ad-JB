<?php


namespace Fa\Bundle\ContentBundle\Controller;

use Fa\Bundle\AdminBundle\Controller\CrudController;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\ContentBundle\Form\NativeBannerSearchAdminType;
use Fa\Bundle\ContentBundle\Repository\NativeBannerRepository;


/**
 * This controller is used for banner management.
 *
 * @author Jigar Lad <jigar.lad@fridaymediagroup.com>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */

class NativeBannerAdminController extends CrudController implements ResourceAuthorizationController
{
    /**
     * Get table name.
     *
     * @return object
     */
    protected function getTableName()
    {
        return 'native_banner';
    }

    /**
     * Index action.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request){
        CommonManager::setAdminBackUrl($request, $this->container);
        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaContentBundle:NativeBanner'),$this->getRepositoryTable('FaContentBundle:NativeBanner'), 'fa_content_native_banner_search_admin');
        $data               = $this->get('fa.searchfilters.manager')->getFiltersData();
        $selectedPagesArray = array();

        // initialize search manager service and fetch data based of filters
        $data['select_fields'] = array(
            'native_banner' => array('id','title','device'),
            'category' => array('name as category_name')
        );
        $data['query_joins'] = array(
            'native_banner' => array(
                'category' => array('type' => 'left')
            )
        );

        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaContentBundle:NativeBanner'), $data);
        $qb = $this->get('fa.sqlsearch.manager')->getQueryBuilder();
        $qb->addSelect("group_concat(".NativeBannerRepository::ALIAS.".title, ', ') as page_names");
        $qb->addGroupBy(NativeBannerRepository::ALIAS.'.id');
        $query = $qb->getQuery();
        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page);
        $pagination = $this->get('fa.pagination.manager')->getPagination();
        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(NativeBannerSearchAdminType::class, null, array('action' => $this->generateUrl('native_banner_admin'), 'method' => 'GET'));

        if ($data['search']) {
            $form->submit($data['search']);
        }

        $parameters = array(
            'heading'            => $this->get('translator')->trans('NativeBanner'),
            'form'               => $form->createView(),
            'pagination'         => $pagination,
            'sorter'             => $data['sorter'],
            'selectedPagesArray' => $selectedPagesArray
        );
        return $this->render($this->getBundleName().':'.$this->getControllerName().':index.html.twig', $parameters);
    }

    /**
     * Displays a form to edit an existing record.
     *
     * @param Request $request A request object.
     * @param integer $id      Id.
     *
     * @return Response A response object.
     *
     */
    public function editAction(Request $request, $id)
    {
        CommonManager::setAdminBackUrl($request, $this->container);
        $formManager        = $this->get('fa.formmanager');
        $entity             = $this->getRepository($this->getBundleName().':'.$this->getEntityName())->find($id);
        $selectedPagesArray = array();

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

        $this->unsetFormFields($form);

        $objBannerPages = $entity->getBannerPages();
        foreach ($objBannerPages as $objBannerPage) {
            $selectedPagesArray[] = $objBannerPage->getId();
        }

        $parameters = array(
            'entity'             => $entity,
            'form'               => $form->createView(),
            'heading'            => $this->get('translator')->trans('Edit %displayWord%', array('%displayWord%' => $this->getDisplayWord())),
            'selectedPagesArray' => $selectedPagesArray
        );

        return $this->render($this->getBundleName().':'.$this->getControllerName().':new.html.twig', $parameters);
    }
}