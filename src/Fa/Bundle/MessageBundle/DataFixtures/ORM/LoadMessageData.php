<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\MessageBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Fa\Bundle\MessageBundle\Entity\Message;

/**
 * Load message data.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class LoadMessageData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * Load.
     *
     * @param ObjectManager $em
     */
    public function load(ObjectManager $em)
    {
        $metadata = $em->getClassMetaData('Fa\Bundle\MessageBundle\Entity\Message');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        $messageRoot = new Message();
        $messageRoot->setId(1);
        $em->persist($messageRoot);
        $em->flush();
    }

    /**
     * Get order.
     */
    public function getOrder()
    {
        return 10; // the order in which fixtures will be loaded
    }
}
