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
use Fa\Bundle\UserBundle\Entity\Testimonials;

/**
 * This fixture is used to load user data.
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class LoadTestimonialsData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
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
        return false;

        $user = $em->getRepository('Fa\Bundle\UserBundle\Entity\User')->findOneBy(array('id' => '87820'));
        $testimonials1 = new Testimonials();
        $testimonials1->setUser($user);
        $testimonials1->setStatus(0);
        $testimonials1->setUserName($user->getFirstname().' '.$user->getLastname());
        $testimonials1->setUserEmail($user->getEmail());
        $testimonials1->setComment("My first testimonials, My first testimonials, My first testimonials, My first testimonials My first testimonials");
        $em->persist($testimonials1);

        $user = $em->getRepository('Fa\Bundle\UserBundle\Entity\User')->findOneBy(array('id' => '87821'));
        $testimonials2 = new Testimonials();
        $testimonials2->setUser($user);
        $testimonials2->setStatus(0);
        $testimonials2->setUserName($user->getFirstname().' '.$user->getLastname());
        $testimonials2->setUserEmail($user->getEmail());
        $testimonials2->setComment("My second testimonials, My second testimonials, My second testimonials, My second testimonials My second testimonials");
        $em->persist($testimonials2);

        $user = $em->getRepository('Fa\Bundle\UserBundle\Entity\User')->findOneBy(array('id' => '87822'));
        $testimonials3 = new Testimonials();
        $testimonials3->setUser($user);
        $testimonials3->setStatus(0);
        $testimonials3->setUserName($user->getFirstname().' '.$user->getLastname());
        $testimonials3->setUserEmail($user->getEmail());
        $testimonials3->setComment("My testimonials, My testimonials, My testimonials, My testimonials My testimonials");
        $em->persist($testimonials3);

        $user = $em->getRepository('Fa\Bundle\UserBundle\Entity\User')->findOneBy(array('id' => '87823'));
        $testimonials4 = new Testimonials();
        $testimonials4->setUser($user);
        $testimonials4->setStatus(0);
        $testimonials4->setUserName($user->getFirstname().' '.$user->getLastname());
        $testimonials4->setUserEmail($user->getEmail());
        $testimonials4->setComment("My testimonials, My testimonials, My testimonials, My testimonials My testimonials");
        $em->persist($testimonials4);

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
