<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EntityBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Fa\Bundle\PaymentBundle\Entity\DeliveryMethodOption;

/**
 * This is load delivery method option data.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class LoadDeliveryMethodOptionData extends AbstractFixture implements OrderedFixtureInterface
{

    /**
     * Get load.
     *
     * @param object $em Object manager.
     */
    public function load(ObjectManager $em)
    {
        // set class meta data
        $metadata = $em->getClassMetaData('Fa\Bundle\PaymentBundle\Entity\DeliveryMethodOption');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        $options = array('Collection only', 'Posted', 'Post or Collect');

        $i = 1;
        foreach ($options as $option) {
            $entity = new DeliveryMethodOption();
            $entity->setId($i);
            $entity->setName($option);
            $entity->setStatus(1);
            $em->persist($entity);
            $em->flush();
            $i++;
        }
    }

    /**
     * Get order.
     *
     * @return integer
     */
    public function getOrder()
    {
        return 2; // the order in which fixtures will be loaded
    }
}
