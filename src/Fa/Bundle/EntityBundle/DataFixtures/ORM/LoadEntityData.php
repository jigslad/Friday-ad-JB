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
use Fa\Bundle\EntityBundle\Entity\Entity;
use Fa\Bundle\EntityBundle\Repository\EntityRepository as BaseEntityRepository;

/**
 * Load entity data.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class LoadEntityData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * Load fixture.
     *
     * @param ObjectManager $em object.
     */
    public function load(ObjectManager $em)
    {
        return false;
        // set class meta data
        $metadata = $em->getClassMetaData('Fa\Bundle\EntityBundle\Entity\Entity');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        $adTypeArray = array('For sale', 'Wanted', 'Swap', 'Free');

        $i = 1;
        $gap = 20;
        $type = $em->getRepository('Fa\Bundle\EntityBundle\Entity\CategoryDimension')->findOneBy(array('id' => BaseEntityRepository::AD_TYPE_ID));

        foreach ($adTypeArray as $adType) {
            $entity = new Entity();
            $entity->setId($i);
            $entity->setCategoryDimension($type);
            $entity->setName($adType);
            $em->persist($entity);
            $em->flush();
            $i++;
        }

        $i = $i + $gap;

        $adStatusArray = array('Live', 'Draft', 'Expired', 'Sold', 'Moderated', 'Rejected', 'RejectedWithReason', 'Inactive');
        $type = $em->getRepository('Fa\Bundle\EntityBundle\Entity\CategoryDimension')->findOneBy(array('id' => BaseEntityRepository::AD_STATUS_ID));

        foreach ($adStatusArray as $adStatus) {
            $entity = new Entity();
            $entity->setId($i);
            $entity->setCategoryDimension($type);
            $entity->setName($adStatus);
            $em->persist($entity);
            $em->flush();
            $i++;
        }

        $i = $i + $gap;

        $userStatusArray = array('Active', 'Inactive', 'Blocked', 'Deleted');
        $type = $em->getRepository('Fa\Bundle\EntityBundle\Entity\CategoryDimension')->findOneBy(array('id' => BaseEntityRepository::USER_STATUS_ID));

        foreach ($userStatusArray as $userStatus) {
            $entity = new Entity();
            $entity->setId($i);
            $entity->setCategoryDimension($type);
            $entity->setName($userStatus);
            $em->persist($entity);
            $em->flush();
            $i++;
        }

        $i = $i + $gap;

        $colourArray = array('Beige', 'Light gray', 'Gray', 'Silver', 'Yellow', 'Golden', 'Black', 'Orange', 'Brown', 'White', 'Red', 'Blue', 'Green', 'Other');
        $type = $em->getRepository('Fa\Bundle\EntityBundle\Entity\CategoryDimension')->findOneBy(array('id' => BaseEntityRepository::COLOUR_ID));

        foreach ($colourArray as $colour) {
            $entity = new Entity();
            $entity->setId($i);
            $entity->setCategoryDimension($type);
            $entity->setName($colour);
            $em->persist($entity);
            $em->flush();
            $i++;
        }
    }
    /**
     * Get order of fixture.
     *
     * @return integer
     */
    public function getOrder()
    {
        return 2; // the order in which fixtures will be loaded
    }
}
