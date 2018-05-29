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
use Fa\Bundle\UserBundle\Entity\User;

/**
 * This fixture is used to load user data.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class LoadUserData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
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

        $metadata = $em->getClassMetaData('Fa\Bundle\UserBundle\Entity\User');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        // set admin users
        $user = new User();
        $user->setFirstName('Friday');
        $user->setLastName('Admin');
        $user->setId(300000);
        $user->setUsername('admin');
        $user->setGender('M');
        $user->setEmail('sagar@aspl.in');
        $user->setStatus($em->getRepository('Fa\Bundle\EntityBundle\Entity\Entity')->findOneBy(array('id' => '52')));
        $user->addRole($em->getRepository('Fa\Bundle\UserBundle\Entity\Role')->findOneBy(array('name' => 'ROLE_SUPER_ADMIN')));

        $password = 'admin';

        // encode the password
        $factory = $this->container->get('security.encoder_factory');
        $encoder = $factory->getEncoder($user);
        $encodedPassword = $encoder->encodePassword($password, $user->getSalt());
        $user->setPassword($encodedPassword);
        $em->persist($user);
        $em->flush();

        $user = new User();
        $user->setId(300001);
        $user->setFirstName('Rosie');
        $user->setLastName('Williams');
        $user->setUsername('rosie');
        $user->setGender('F');
        $user->setEmail('rosie.williams@fridaymediagroup.com');
        $user->setStatus($em->getRepository('Fa\Bundle\EntityBundle\Entity\Entity')->findOneBy(array('id' => '52')));
        $user->addRole($em->getRepository('Fa\Bundle\UserBundle\Entity\Role')->findOneBy(array('name' => 'ROLE_SUPER_ADMIN')));

        $password = 'rosie';

        // encode the password
        $factory = $this->container->get('security.encoder_factory');
        $encoder = $factory->getEncoder($user);
        $encodedPassword = $encoder->encodePassword($password, $user->getSalt());
        $user->setPassword($encodedPassword);
        $em->persist($user);
        $em->flush();

        $user = new User();
        $user->setId(300002);
        $user->setFirstName('Steven');
        $user->setLastName('Stroffolino');
        $user->setUsername('steven');
        $user->setGender('M');
        $user->setEmail('steven.stroffolino@fridaymediagroup.com');
        $user->setStatus($em->getRepository('Fa\Bundle\EntityBundle\Entity\Entity')->findOneBy(array('id' => '52')));
        $user->addRole($em->getRepository('Fa\Bundle\UserBundle\Entity\Role')->findOneBy(array('name' => 'ROLE_SUPER_ADMIN')));

        $password = 'steven';

        // encode the password
        $factory = $this->container->get('security.encoder_factory');
        $encoder = $factory->getEncoder($user);
        $encodedPassword = $encoder->encodePassword($password, $user->getSalt());
        $user->setPassword($encodedPassword);
        $em->persist($user);
        $em->flush();

        $em->persist($user);
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
