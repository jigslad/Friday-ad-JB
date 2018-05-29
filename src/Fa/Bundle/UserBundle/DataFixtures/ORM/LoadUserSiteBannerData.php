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
use Fa\Bundle\UserBundle\Entity\UserSiteBanner;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;

/**
 * This fixture is used to load user site banner.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class LoadUserSiteBannerData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
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
        $metadata = $em->getClassMetaData('Fa\Bundle\UserBundle\Entity\UserSiteBanner');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        // set role
        $userSiteBanner1 = new UserSiteBanner();
        $userSiteBanner1->setCategoryId(CategoryRepository::FOR_SALE_ID);
        $userSiteBanner1->setId(1);
        $userSiteBanner1->setOrd(1);
        $userSiteBanner1->setPath('uploads/usersitebanner');
        $userSiteBanner1->setFilename('for-sale-default.png');
        $em->persist($userSiteBanner1);
        $em->flush();

        $userSiteBanner2 = new UserSiteBanner();
        $userSiteBanner2->setCategoryId(CategoryRepository::MOTORS_ID);
        $userSiteBanner2->setId(2);
        $userSiteBanner2->setOrd(2);
        $userSiteBanner2->setPath('uploads/usersitebanner');
        $userSiteBanner2->setFilename('motors-default.png');
        $em->persist($userSiteBanner2);
        $em->flush();

        $userSiteBanner3 = new UserSiteBanner();
        $userSiteBanner3->setCategoryId(CategoryRepository::JOBS_ID);
        $userSiteBanner3->setId(3);
        $userSiteBanner3->setOrd(3);
        $userSiteBanner3->setPath('uploads/usersitebanner');
        $userSiteBanner3->setFilename('jobs-default.png');
        $em->persist($userSiteBanner3);
        $em->flush();

        $userSiteBanner4 = new UserSiteBanner();
        $userSiteBanner4->setCategoryId(CategoryRepository::SERVICES_ID);
        $userSiteBanner4->setId(4);
        $userSiteBanner4->setOrd(4);
        $userSiteBanner4->setPath('uploads/usersitebanner');
        $userSiteBanner4->setFilename('services-default.png');
        $em->persist($userSiteBanner4);
        $em->flush();

        $userSiteBanner5 = new UserSiteBanner();
        $userSiteBanner5->setCategoryId(CategoryRepository::PROPERTY_ID);
        $userSiteBanner5->setId(5);
        $userSiteBanner5->setOrd(5);
        $userSiteBanner5->setPath('uploads/usersitebanner');
        $userSiteBanner5->setFilename('property-default.png');
        $em->persist($userSiteBanner5);
        $em->flush();

        $userSiteBanner6 = new UserSiteBanner();
        $userSiteBanner6->setCategoryId(CategoryRepository::ANIMALS_ID);
        $userSiteBanner6->setId(6);
        $userSiteBanner6->setOrd(6);
        $userSiteBanner6->setPath('uploads/usersitebanner');
        $userSiteBanner6->setFilename('animals-default.png');
        $em->persist($userSiteBanner6);
        $em->flush();

        $userSiteBanner7 = new UserSiteBanner();
        $userSiteBanner7->setCategoryId(CategoryRepository::ADULT_ID);
        $userSiteBanner7->setId(7);
        $userSiteBanner7->setOrd(7);
        $userSiteBanner7->setPath('uploads/usersitebanner');
        $userSiteBanner7->setFilename('adult-default.png');
        $em->persist($userSiteBanner7);
        $em->flush();

        $userSiteBanner8 = new UserSiteBanner();
        $userSiteBanner8->setCategoryId(null);
        $userSiteBanner8->setId(8);
        $userSiteBanner8->setOrd(8);
        $userSiteBanner8->setPath('uploads/usersitebanner');
        $userSiteBanner8->setFilename('other_1.png');
        $em->persist($userSiteBanner8);
        $em->flush();

        $userSiteBanner9 = new UserSiteBanner();
        $userSiteBanner9->setCategoryId(null);
        $userSiteBanner9->setId(9);
        $userSiteBanner9->setOrd(9);
        $userSiteBanner9->setPath('uploads/usersitebanner');
        $userSiteBanner9->setFilename('other_2.png');
        $em->persist($userSiteBanner9);
        $em->flush();

        $userSiteBanner10 = new UserSiteBanner();
        $userSiteBanner10->setCategoryId(null);
        $userSiteBanner10->setId(10);
        $userSiteBanner10->setOrd(10);
        $userSiteBanner10->setPath('uploads/usersitebanner');
        $userSiteBanner10->setFilename('other_3.png');
        $em->persist($userSiteBanner10);
        $em->flush();

        $userSiteBanner11 = new UserSiteBanner();
        $userSiteBanner11->setCategoryId(null);
        $userSiteBanner11->setId(11);
        $userSiteBanner11->setOrd(11);
        $userSiteBanner11->setPath('uploads/usersitebanner');
        $userSiteBanner11->setFilename('other_4.png');
        $em->persist($userSiteBanner11);
        $em->flush();

        $userSiteBanner12 = new UserSiteBanner();
        $userSiteBanner12->setCategoryId(null);
        $userSiteBanner12->setId(12);
        $userSiteBanner12->setOrd(12);
        $userSiteBanner12->setPath('uploads/usersitebanner');
        $userSiteBanner12->setFilename('other_5.png');
        $em->persist($userSiteBanner12);
        $em->flush();

        $userSiteBanner13 = new UserSiteBanner();
        $userSiteBanner13->setCategoryId(null);
        $userSiteBanner13->setId(13);
        $userSiteBanner13->setOrd(13);
        $userSiteBanner13->setPath('uploads/usersitebanner');
        $userSiteBanner13->setFilename('other_6.png');
        $em->persist($userSiteBanner13);
        $em->flush();

        $userSiteBanner14 = new UserSiteBanner();
        $userSiteBanner14->setCategoryId(null);
        $userSiteBanner14->setId(14);
        $userSiteBanner14->setOrd(14);
        $userSiteBanner14->setPath('uploads/usersitebanner');
        $userSiteBanner14->setFilename('other_7.png');
        $em->persist($userSiteBanner14);
        $em->flush();

        $userSiteBanner15 = new UserSiteBanner();
        $userSiteBanner15->setCategoryId(null);
        $userSiteBanner15->setId(15);
        $userSiteBanner15->setOrd(15);
        $userSiteBanner15->setPath('uploads/usersitebanner');
        $userSiteBanner15->setFilename('other_8.png');
        $em->persist($userSiteBanner15);
        $em->flush();

        $userSiteBanner16 = new UserSiteBanner();
        $userSiteBanner16->setCategoryId(CategoryRepository::COMMUNITY_ID);
        $userSiteBanner16->setId(16);
        $userSiteBanner16->setOrd(16);
        $userSiteBanner16->setPath('uploads/usersitebanner');
        $userSiteBanner16->setFilename('community-default.png');
        $em->persist($userSiteBanner16);
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
