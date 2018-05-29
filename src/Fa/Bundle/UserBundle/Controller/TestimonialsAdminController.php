<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fa\Bundle\AdminBundle\Controller\CrudController;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\UserBundle\Repository\TestimonialsRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\UserBundle\Form\TestimonialsSearchAdminType;

/**
 * This controller is used for user management.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class TestimonialsAdminController extends CrudController implements ResourceAuthorizationController
{
    /**
     * table name
     */
    protected function getTableName()
    {
        return 'testimonials';
    }

    /**
     * Lists all Testimonials entities.
     */
    public function indexAction(Request $request)
    {
        CommonManager::setAdminBackUrl($request, $this->container);

        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaUserBundle:Testimonials'), $this->getRepositoryTable('FaUserBundle:Testimonials'), 'fa_user_testimonials_search_admin');
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();

        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaUserBundle:Testimonials'), $data);
        $qb = $this->get('fa.sqlsearch.manager')->getQueryBuilder();
        $query = $qb->getQuery();

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page);
        $pagination = $this->get('fa.pagination.manager')->getPagination();

        // initialize form manager service
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(TestimonialsSearchAdminType::class, null, array('action' => $this->generateUrl('testimonials_admin'), 'method' => 'GET'));

        if ($data['search']) {
            $form->submit($data['search']);
        }

        $parameters = array(
            'heading'    => $this->get('translator')->trans('Testimonials'),
            'form'       => $form->createView(),
            'pagination' => $pagination,
            'sorter'     => $data['sorter'],
        );

        return $this->render('FaUserBundle:TestimonialsAdmin:index.html.twig', $parameters);
    }

    /**
     * Change status
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function ajaxChangeStatusAction(Request $request)
    {
        $response = new Response();

        try {
            if ($request->isXmlHttpRequest() && $request->get('id') != null && $request->get('status') != null) {
                $this->getRepository('FaUserBundle:Testimonials')->updateStatus($request->get('id'), $request->get('status'));
                $this->handleMessage($this->get('translator')->trans('Status changed successfully for testimonials %id%', array('%id%' => $request->get('id'))), null);
            }
        } catch (\Exception $e) {
            $this->handleMessage($this->get('translator')->trans('Sorry we are not able to change status for testimonials %id%', array('%id%' => $request->get('id'))), null, array(), 'error');
        }

        return $response->setContent('{}');
    }
}
