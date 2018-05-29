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

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fa\Bundle\CoreBundle\Controller\CoreController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Fa\Bundle\EntityBundle\Repository\LocationRepository;

/**
 * This is default controller for dot mailer bundle.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class DefaultController extends CoreController
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
        /*$createDataField = $this->get('fa.dotmailer.createdatafield.resource');
        $fields = array();
        $fields['Name'] = 'CATEGORY';
        $fields['Type'] = 'string';

        $createDataField->setDataToSubmit($fields);
        if ($createDataField->createDataField()) {
            echo "<br /><br />Field created successfully<br /><br />";
        } else {
            echo "<br /><br />Field doesn't created successfully<br /><br />";
        }*/

        /*$user = $this->getRepository('FaUserBundle:User')->findOneBy(array('email' => 'janakb@aspl.in'));
        $createContact = $this->get('fa.dotmailer.createdatafield.resource');
        $createContact->setUser($user);
        $createContact->createContact();*/

        return $this->render('FaDotMailerBundle:Default:index.html.twig');
    }

    /**
     * Create data field action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function createDataFieldAction(Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }

        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        $parameters = array();

        if ($user->getEmail() == 'sagar@aspl.in') {
            $createDataField = $this->get('fa.dotmailer.createdatafield.resource');
            $fields = array();
            $fields = $this->container->getParameter('fa.dotmailer.data.fields');
            $createdField = '';
            $notCreatedField = '';

            foreach ($fields as $data) {
                if ($data['Name'] == 'EMAIL') {
                    continue;
                }

                $createDataField->setDataToSubmit($data);
                if ($createDataField->createDataField()) {
                    $createdField .= "<b>".$data['Name']."</b>".': Field created successfully'."<br />";
                } else {
                    $notCreatedField .= "<b>".$data['Name']."</b>".': Fields doesn\'t created successfully'. "<br />";
                }
            }

            $parameters['createdField']    = $createdField;
            $parameters['notCreatedField'] = $notCreatedField;
        }

        return $this->render('FaDotMailerBundle:Default:index.html.twig', $parameters);
    }

    /**
     * Create address book action.
     *
     * @param Request $request A Request object.
     *
     * @param Response A Response object.
     */
    public function createAddressBookAction(Request $request)
    {
        $redirectResponse = $this->checkIsValidLoggedInUser($request);
        if ($redirectResponse !== true) {
            return $redirectResponse;
        }

        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        $parameters = array();

        if ($user->getEmail() == 'sagar@aspl.in') {
            $createAddressBook = $this->get('fa.dotmailer.createaddressbook.resource');
            $message = '';

            if ($createAddressBook->createAddressBook(40000, 'Master address book', 'Private')) {
                $message = "Address book created successfully";
            } else {
                $message = "Address book doesn't created successfully";
            }

            $parameters['message'] = $message;
        }

        return $this->render('FaDotMailerBundle:Default:index.html.twig', $parameters);
    }
}
