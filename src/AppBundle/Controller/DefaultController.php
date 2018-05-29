<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     *
     * @Route("/test/testok", name="fa_test_ok")
     */
    public function indexAction(Request $request)
    {
        // @Route("/test/testok", name="fa_test_ok")
        // @Route("/", name="homepage")
        // replace this example code with whatever you need
//         var_dump('hello symfony3.4');die('lll');
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
        ] );
    }
}
