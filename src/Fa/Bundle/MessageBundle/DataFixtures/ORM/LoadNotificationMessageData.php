<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Fa\Bundle\MessageBundle\Entity\NotificationMessage;
use Gedmo\Sluggable\Util as Sluggable;

/**
 * Load static page block data.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class LoadNotificationMessageData extends AbstractFixture implements OrderedFixtureInterface
{

    /**
     * Load data.
     *
     * @param Doctrine\Common\Persistence\ObjectManager $em
     */
    public function load(ObjectManager $em)
    {
        //return false;
        // set class meta data
        $metadata = $em->getClassMetaData('Fa\Bundle\MessageBundle\Entity\NotificationMessage');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        $notificationMessageId = 1;

        $reader = new \EasyCSV\Reader(__DIR__."/notification_message.csv");
        $reader->setDelimiter(';');

        $batchSize = 100;
        $row = 0;
        while ($row = $reader->getRow()) {
            $notificationMessage = new NotificationMessage();
            $notificationMessage->setId($notificationMessageId);
            $notificationMessage->setName($row['name']);
            $notificationMessage->setNotificationType($row['path']);
            $notificationMessage->setStatus($row['status']);
            $notificationMessage->setIsDismissable($row['is_dismissable']);
            $notificationMessage->setIsFlash($row['is_flash']);
            $notificationMessage->setMessage($row['text_message']);
            $notificationMessage->setDuration($row['duration']);
            $em->persist($notificationMessage);

            if (($row % $batchSize) == 0) {
                $em->flush();
            }

            $row++;
            $notificationMessageId++;
        }
        $em->flush();
    }

    /**
     * Get order.
     *
     * @return integer
     */
    public function getOrder()
    {
        return 6; // the order in which fixtures will be loaded
    }
}
