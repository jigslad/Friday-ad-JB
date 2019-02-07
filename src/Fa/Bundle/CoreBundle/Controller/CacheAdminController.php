<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Fa\Bundle\CoreBundle\Controller\ResourceAuthorizationController;
use Fa\Bundle\CoreBundle\Controller\CoreController;

/**
 * This controller is used for cache management.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class CacheAdminController extends CoreController implements ResourceAuthorizationController
{
    /**
     * Cache management links.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        return $this->render('FaCoreBundle:CacheAdmin:index.html.twig');
    }

    /**
     * Cache celar.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function clearAction()
    {
        exec('nohup '.$this->container->getParameter('fa.php.path').' bin/console fa:redis:flushall --no-interaction >/dev/null &');
        return parent::handleMessage($this->get('translator')->trans('Cache has been flushed.', array(), 'success'), 'cache_admin');
    }

    /**
     * Cache generate.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function generateAction()
    {
        exec('nohup'.' '.$this->container->getParameter('fa.php.path').' bin/console fa:update:entity generate FaEntityBundle:Entity >/dev/null &');
        exec('nohup'.' '.$this->container->getParameter('fa.php.path').' bin/console fa:update:entity generate FaEntityBundle:Category >/dev/null &');
        exec('nohup'.' '.$this->container->getParameter('fa.php.path').' bin/console fa:update:entity generate FaEntityBundle:Location >/dev/null &');
        exec('nohup'.' '.$this->container->getParameter('fa.php.path').' bin/console fa:update:entity generate FaEntityBundle:Locality >/dev/null &');
        exec('nohup'.' '.$this->container->getParameter('fa.php.path').' bin/console fa:update:entity generate FaPaymentBundle:DeliveryMethodOption >/dev/null &');
        exec('nohup'.' '.$this->container->getParameter('fa.php.path').' bin/console fa:update:seo:rule:cache generate adp >/dev/null &');
        exec('nohup'.' '.$this->container->getParameter('fa.php.path').' bin/console fa:update:seo:rule:cache generate aia >/dev/null &');
        exec('nohup'.' '.$this->container->getParameter('fa.php.path').' bin/console fa:update:seo:rule:cache generate hp >/dev/null &');
        exec('nohup'.' '.$this->container->getParameter('fa.php.path').' bin/console fa:update:seo:rule:cache generate alp >/dev/null &');
        exec('nohup'.' '.$this->container->getParameter('fa.php.path').' bin/console fa:generate:category-cache-for-autosuggest >/dev/null &');
        exec('nohup'.' '.$this->container->getParameter('fa.php.path').' bin/console fa:update:config:rule:cache generate 10 >/dev/null &');
        exec('nohup'.' '.$this->container->getParameter('fa.php.path').' bin/console fa:update:config:rule:cache generate 9 >/dev/null &');
        exec('nohup'.' '.$this->container->getParameter('fa.php.path').' bin/console fa:update:config:rule:cache generate 4 >/dev/null &');
        exec('nohup'.' '.$this->container->getParameter('fa.php.path').' bin/console fa:update:config:rule:cache generate 8 >/dev/null &');

        return parent::handleMessage($this->get('translator')->trans('Generating cache process is running, it will take approximate 5 minutes.', array(), 'success'), 'cache_admin');
    }
}
