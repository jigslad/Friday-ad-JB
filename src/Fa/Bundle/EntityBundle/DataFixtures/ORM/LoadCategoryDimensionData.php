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
use Fa\Bundle\EntityBundle\Entity\CategoryDimension;

/**
 * Load category dimension data.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class LoadCategoryDimensionData extends AbstractFixture implements OrderedFixtureInterface
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
        $metadata = $em->getClassMetaData('Fa\Bundle\EntityBundle\Entity\CategoryDimension');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        $sharedCategoryDimensionArray = array('Ad type', 'colour', 'Ad status', 'User status', 'status');

        $i = 1;

        foreach ($sharedCategoryDimensionArray as $sharedCategoryDimension) {
            $categoryDimension = new CategoryDimension();
            $categoryDimension->setId($i);
            $categoryDimension->setName($sharedCategoryDimension);
            $em->persist($categoryDimension);
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
        return 1; // the order in which fixtures will be loaded
    }
}
