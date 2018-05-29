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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\UserBundle\Event\FormEvent;
use Fa\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\UserBundle\Form\UserHalfAccountType;

/**
 * This controller is used for registration management.
 *
 * @author Janaksinh Jadeja <janak@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class HalfAccountController extends CoreController
{
    /**
     * This action is used for create user half account.
     *
     * @param Request $request A Request object.
     *
     * @return Response A Response object.
     */
    public function ajaxCreateAction(Request $request)
    {
        $formManager = $this->get('fa.formmanager');
        $form        = $formManager->createForm(UserHalfAccountType::class, null, array('method' => 'POST'));

        if ($request->isXmlHttpRequest() && 'POST' === $request->getMethod() && $request->get('is_form_load', null) == null) {
            if ($formManager->isValid($form)) {
                $user = $this->getRepository('FaUserBundle:User')->findOneBy(array('email' => $form->get('email')->getData()));
                return new JsonResponse(array('success' => '1', 'htmlContent' => '', 'user_id' => $user->getId()));
            } else {
                $htmlContent = $this->renderView('FaUserBundle:HalfAccount:createForm.html.twig', array('form' => $form->createView()));
                return new JsonResponse(array('success' => '', 'htmlContent' => $htmlContent, 'user_id' => ''));
            }
        }

        $parameters = array('form' => $form->createView());
        return $this->render('FaUserBundle:HalfAccount:create.html.twig', $parameters);
    }
}
