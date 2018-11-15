<?php

namespace Tests\Fa\Bundle\Controller;


use Fa\Bundle\AdBundle\Controller\AdPostController;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;


class AdPostControllerTest extends TestCase
{
    // ...

    public function testAdPost()
    {
        $controller = new AdPostController;

        $request = new Request();
        $request->setMethod('POST');

        $form = $this
            ->createMock('Symfony\Component\Form\Form')
            ->method('isValid')
            ;

        $form
            ->expects($this->once())
            ->method('bindRequest')
            ->with($this->equalTo($request))
        ;
        $form
            ->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true))
        ;

        $formFactory = $this->getMock('Symfony\Component\Form\FormFactoryInterface');
        $formFactory
            ->expects($this->once())
            ->method('create')
            ->will($this->returnValue($form))
        ;

        $mailer = $this
            ->getMockBuilder('\Swift_Mailer')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $mailer
            ->expects($this->once())
            ->method('send')
        ;

        $controller->setFormFactory($formFactory);

        $controller->firstStepAction($request);
    }
}
