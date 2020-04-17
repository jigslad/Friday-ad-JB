<?php

namespace Tests\Fa\Bundle\Controller;


use Fa\Bundle\AdBundle\Controller\AdPostController;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Fa\Bundle\AdBundle\Controller\AdListController;

class AdPostControllerTest extends WebTestCase
{
    // ...

    public function testAdPost()
    {
        $kernel = static::bootKernel(array('debug' => true, 'env' => 'test'));
        $container = static::$kernel->getContainer();
        /*
        // Auth
        $container->get('security.context')->setToken(
            new UsernamePasswordToken(
                'maintenance', null, 'main', array('ROLE_FIXTURE_LOADER')
            )
        );
        */

        $adlist = new AdListController();
        $adlist->setContainer($container);
        $request = new Request();
        $request->set('location', 'uk');
        $result = $adlist->searchResultAction($request);

        // print value
        print_r($result);

        $this->assertContains('expected value', $result );
        print('test completed');
    }
}
