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
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Fa\Bundle\UserBundle\Repository\TestimonialsRepository;
use Fa\Bundle\UserBundle\Entity\Testimonials;
use Fa\Bundle\UserBundle\Form\TestimonialType;

/**
 * This controller is used for user testi monials.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class TestimonialsController extends CoreController
{
    /**
     * List user testimonials.
     *
     * @param Request $request Request object.
     *
     * @return Response A Response object.
     */
    public function listAction(Request $request)
    {
        // initialize search filter manager service and prepare filter data for searching
        $this->get('fa.searchfilters.manager')->init($this->getRepository('FaUserBundle:Testimonials'), $this->getRepositoryTable('FaUserBundle:Testimonials'));
        $data = $this->get('fa.searchfilters.manager')->getFiltersData();
        $data['query_sorter']  = array('testimonials' => array('created_at' => 'desc'));
        $data['select_fields']  = array(
                                    'testimonials' => array('id', 'user_name', 'created_at', 'comment'),
                                  );
        $data['static_filters'] = TestimonialsRepository::ALIAS.'.status = 1';
        $this->get('fa.sqlsearch.manager')->init($this->getRepository('FaUserBundle:Testimonials'), $data);
        $queryBuilder = $this->get('fa.sqlsearch.manager')->getQueryBuilder();
        $query        = $queryBuilder->getQuery();

        // initialize pagination manager service and prepare listing with pagination based of data
        $page = (isset($data['pager']['page']) && $data['pager']['page']) ? $data['pager']['page'] : 1;
        $this->get('fa.pagination.manager')->init($query, $page, '20');
        $pagination = $this->get('fa.pagination.manager')->getPagination();
        $parameters = array(
            'pagination' => $pagination,
        );

        return $this->render('FaUserBundle:Testimonials:list.html.twig', $parameters);
    }

    /**
     * Add testimonial.
     *
     * @param Request $request A Request object.
     *
     * @return Response|JsonResponse A Response or JsonResponse object.
     */
    public function ajaxAddTestimonialAction(Request $request)
    {
        $redirectToUrl = '';
        $error         = '';
        $htmlContent   = '';

        if ($request->isXmlHttpRequest()) {
            if ($this->isAuth()) {
                $formManager  = $this->get('fa.formmanager');
                $testimonial  = new Testimonials();
                $form         = $formManager->createForm(TestimonialType::class, $testimonial);
                if ('POST' === $request->getMethod()) {
                    $form->handleRequest($request);

                    if ($form->isValid()) {
                        //save information
                        $testimonial = $formManager->save($testimonial);
                    } elseif ($request->isXmlHttpRequest()) {
                        $htmlContent = $this->renderView('FaUserBundle:Testimonials:ajaxAddTestimonial.html.twig', array('form' => $form->createView()));
                    }
                } else {
                    $htmlContent = $this->renderView('FaUserBundle:Testimonials:ajaxAddTestimonial.html.twig', array('form' => $form->createView()));
                }
            } else {
                //set new cookies for contact seller.
                $response = new Response();
                //remove all cookies.
                $this->getRepository('FaUserBundle:User')->removeUserCookies($response);
                $response->headers->setCookie(new Cookie('frontend_redirect_after_login_path_info', $request->get('redirectUrl'), time() + 3600 * 24 * 7));
                $response->headers->setCookie(new Cookie('add_testimonial_flag', true, time() + 3600 * 24 * 7));
                $response->sendHeaders();

                $messageManager = $this->get('fa.message.manager');
                $messageManager->setFlashMessage($this->get('translator')->trans('Please login to add testimonial.', array(), 'frontend-testimonials'), 'success');
                $redirectToUrl = $this->container->get('router')->generate('login');
            }

            return new JsonResponse(array('error' => $error, 'redirectToUrl' => $redirectToUrl, 'htmlContent' => $htmlContent));
        } else {
            return new Response();
        }
    }
}
