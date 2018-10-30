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

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\DotMailerBundle\Entity\Dotmailer;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\UserBundle\Form\NewsletterType;

/**
 * This controller is used for admin side role management.
 *
 * @author Piyush Parmar <piyush@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class NewsletterController extends CoreController
{
    /**
     * This is index action.
     *
     * @param Request $request
     *
     * @return RedirectResponse A RedirectResponse object.
     */
    public function indexAction(Request $request)
    {
        $guid      = null;
        $dotmailer = null;

        if ($request->query->get('guid')) {
            $dotmailer = $this->getRepository('FaDotMailerBundle:Dotmailer')->findOneBy(array('guid' => $request->query->get('guid')));
            $action    = $this->generateUrl('my_account_newsletter').'?guid='.$request->query->get('guid');
        } else {
            $action = $this->generateUrl('my_account_newsletter');
        }

        if (!$dotmailer) {
            $redirectResponse = $this->checkIsValidLoggedInUser($request);
            if ($redirectResponse !== true) {
                return $redirectResponse;
            }
            $user = $this->container->get('security.token_storage')->getToken()->getUser();
            $dotmailer = $this->getRepository('FaDotMailerBundle:Dotmailer')->findOneBy(array('email' => $user->getEmail()));
            if (!$dotmailer) {
                $dotmailer = new Dotmailer();
                $dotmailer->setFadUser(1);
            }
        }

        $formManager = $this->get('fa.formmanager');
        $form = $formManager->createForm(NewsletterType::class, $dotmailer, array('action' => $action));


        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $this->get('fa.message.manager')->setFlashMessage($this->get('translator')->trans('Newsletter successfully saved.'), 'success');
                return $this->redirect($action);
            }
        }

        $parameters  = array('form' => $form->createView());
        return $this->render('FaUserBundle:Newsletter:index.html.twig', $parameters);
    }
}
