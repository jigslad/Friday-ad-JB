<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Fa\Bundle\UserBundle\Entity\Permission;

/**
 * This fixture is used to load permission data.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class LoadPermissionData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * Load fixture.
     *
     * @param ObjectManager $em object.
     */
    public function load(ObjectManager $em)
    {
        //return false;

        // set class meta data
        $metadata = $em->getClassMetaData('Fa\Bundle\UserBundle\Entity\Permission');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        $permission = new Permission();
        $permission->setId(1);
        $permission->setName('CREATE');
        $em->persist($permission);
        $em->flush();

        $permission = new Permission();
        $permission->setId(2);
        $permission->setName('VIEW');
        $em->persist($permission);
        $em->flush();

        $permission = new Permission();
        $permission->setId(3);
        $permission->setName('EDIT');
        $em->persist($permission);
        $em->flush();

        $permission = new Permission();
        $permission->setId(4);
        $permission->setName('DELETE');
        $em->persist($permission);
        $em->flush();

        $permission = new Permission();
        $permission->setId(5);
        $permission->setName('MASTER');
        $em->persist($permission);
        $em->flush();
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
