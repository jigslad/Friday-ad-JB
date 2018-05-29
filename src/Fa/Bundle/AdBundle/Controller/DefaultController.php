<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Controller;

use Fa\Bundle\CoreBundle\Controller\CoreController;
use Fa\Bundle\UserBundle\Entity\Resource;
use Fa\Bundle\CoreBundle\Manager\CommonManager;

/**
 * This is default controller.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class DefaultController extends CoreController
{
    /**
     * IndexAction.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        CommonManager::removeAdminBackUrl($this->container);
        $metaDataFields = $this->getRepository('FaAdBundle:AdForSale')->getNotIndexedAdForSaleFields();
        $adForSales = $this->getRepository('FaAdBundle:AdForSale')->findAll();
        foreach ($adForSales as $adForSale) {
            $metaData = array();
            foreach ($metaDataFields as $field) {
                if (in_array($field, $metaDataFields)) {
                    if ($this->getField($field, $adForSale)) {
                        $metaData[$field] = $this->getField($field, $adForSale);
                    }
                }
            }

            if (count($metaData)) {
                $adForSale->setMetaData(serialize($metaData));
                $this->getEntityManager()->persist($adForSale);
                $this->getEntityManager()->flush();
            }
        }
        exit;
        return $this->render('FaAdBundle:Default:index.html.twig');
    }

    /**
     *  Set field data.
     *
     * @param string $field  Field name.
     * @param object $object Instance.
     *
     * @return object
     */
    private function getField($field, $object)
    {
        $fieldVal   = null;
        $methodName = 'get'.str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));
        if (method_exists($object, $methodName) === true) {
            $fieldVal = call_user_func(array($object, $methodName));
        }

        return $fieldVal;
    }
}
