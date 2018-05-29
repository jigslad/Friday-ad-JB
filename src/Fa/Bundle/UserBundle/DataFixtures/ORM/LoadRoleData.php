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
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\UserBundle\Entity\Role;

/**
 * This fixture is used to load role data.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class LoadRoleData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{

    /**
     * ContainerInterface.
     *
     * @var object
     */
    private $container;

    /**
     * Set container object.
     *
     * @param ContainerInterface $container object.
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Load fixture.
     *
     * @param ObjectManager $em object.
     */
    public function load(ObjectManager $em)
    {
        //return false;

        // set class meta data
        $metadata = $em->getClassMetaData('Fa\Bundle\UserBundle\Entity\Role');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        // set role
        $role1 = new Role();
        $role1->setName('ROLE_ADMIN');
        $role1->setId(1);
        $role1->setType('A');
        $em->persist($role1);
        $em->flush();

        $role2 = new Role();
        $role2->setName('ROLE_SUPER_ADMIN');
        $role2->setId(2);
        $role2->setType('A');
        $em->persist($role2);
        $em->flush();

        $role5 = new Role();
        $role5->setName('ROLE_SELLER');
        $role5->setId(5);
        $role5->setType('C');
        $em->persist($role5);
        $em->flush();

        $role6 = new Role();
        $role6->setName('ROLE_BUSINESS_SELLER');
        $role6->setId(6);
        $role6->setType('C');
        $em->persist($role6);
        $em->flush();
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
