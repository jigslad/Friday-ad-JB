<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Fa\Bundle\AdBundle\DataFixtures\ORM\LoadPaaFieldData;
use Fa\Bundle\AdBundle\Repository\PaaFieldRuleRepository;
use Fa\Bundle\AdBundle\Repository\AdAdultRepository;

/**
 * This controller is used for ad management.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class LoadPaaFieldAdultData extends LoadPaaFieldData
{
    /**
     * Entity manager.
     *
     * @var object
     */
    private $_em;

    /**
     * (non-PHPdoc)
     * @see \Doctrine\Common\DataFixtures\FixtureInterface::load()
     *
     * @param object $em
     */
    public function load(ObjectManager $em)
    {
        return false;

        $this->_em = $em;

        // Add common fields
        //$this->addCommonPaaFields($this->_em);

        // Add category dimension fields
        $category = $this->_em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => 'Adult', 'lvl' => 1));
        if ($category) {
            $fieldOrderRequired = array(
                'title' => 1,
                'description' => 2,
            );

            $fieldRuleLabel = array();

            $fieldRuleStatus = array (
                'is_new' => 0,
                'qty' => 0,
                'price_text' => 0,
                'price' => 0,
                'delivery_method_option_id' => 0,
                'payment_method_id' => 0
            );

            $fieldRuleStep = array (
                'title' => 2,
                'description' => 2,
                'location' => 4,
                'personalized_title' => 4
            );

            $fieldRuleMaxValue = array (
                'title' => 100,
                'description' => 2000,
                'personalized_title' => 140,
            );

            $fieldRuleMinMaxType = array (
                'title' => PaaFieldRuleRepository::MIN_MAX_TYPE_LENGTH,
                'description' => PaaFieldRuleRepository::MIN_MAX_TYPE_LENGTH,
                'personalized_title' => PaaFieldRuleRepository::MIN_MAX_TYPE_LENGTH,
            );

            // Add rule for top level category only
            $this->addTopLevelCategoryPaaFieldRules($category, $this->_em, $fieldOrderRequired, $fieldRuleLabel, $fieldRuleStatus, array(), array(), array(), $fieldRuleMaxValue, $fieldRuleMinMaxType, array(), $fieldRuleStep);
        }
    }

    /**
     * Get order.
     *
     * @see \Doctrine\Common\DataFixtures\OrderedFixtureInterface::getOrder()
     *
     * @return integer
     */
    public function getOrder()
    {
        return 4; // the order in which fixtures will be loaded
    }
}
