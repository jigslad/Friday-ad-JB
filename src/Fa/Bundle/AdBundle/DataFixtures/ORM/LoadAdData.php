<?php
/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Fa\Bundle\AdBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\AdBundle\Entity\Ad;
use Fa\Bundle\UserBundle\Entity\User;

/**
 * This file is part of the fa bundle.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class LoadAdData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{

    /**
     * Container.
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\DependencyInjection\ContainerAwareInterface::setContainer()
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null.
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * (non-PHPdoc)
     * @see \Doctrine\Common\DataFixtures\FixtureInterface::load()
     *
     * @param object $em
     */
    public function load(ObjectManager $em)
    {
        return false;

        $userIdArr = $em->getRepository('FaUserBundle:User')
        ->createQueryBuilder('u')
        ->select('u.id')
        ->getQuery()
        ->getArrayResult();

        $statusIdArr = array('26', '27', '28', '29', '30', '31', '32');
        $typeIdArr = array('1', '2', '3', '4', '5');

        $categoryIdArr = $em->getRepository('FaEntityBundle:Category')
        ->createQueryBuilder('c')
        ->select('c.id')
        ->where('c.lvl = 3')
        ->setMaxResults(100)
        ->getQuery()
        ->getArrayResult();


        for ($i = 1; $i <= 200; $i++) {
            $ad = new Ad();

            $randomKey    = array_rand($userIdArr);
            $adUserId = $userIdArr[$randomKey]['id'];
            $ad->setUser($em->getRepository('Fa\Bundle\UserBundle\Entity\User')->findOneBy(array('id' => $adUserId)));

            $randomKey    = array_rand($statusIdArr);
            $adStatusId = $statusIdArr[$randomKey];
            $ad->setStatus($em->getRepository('Fa\Bundle\EntityBundle\Entity\Entity')->findOneBy(array('id' => $adStatusId)));

            $randomKey    = array_rand($typeIdArr);
            $adTypeId = $typeIdArr[$randomKey];
            $ad->setType($em->getRepository('Fa\Bundle\EntityBundle\Entity\Entity')->findOneBy(array('id' => $adTypeId)));

            $randomKey    = array_rand($categoryIdArr);
            $categoryId = $categoryIdArr[$randomKey]['id'];
            $ad->setCategory($em->getRepository('Fa\Bundle\EntityBundle\Entity\Category')->findOneBy(array('id' => $categoryId)));

            $ad->setIsNew(1);
            $ad->setTitle('Testing ad'.$i);
            $ad->setHasVideo(1);
            $ad->setCreatedAt(time());

            $em->persist($ad);
        }

        $em->flush();
    }

    /**
     * (non-PHPdoc)
     * @see \Doctrine\Common\DataFixtures\OrderedFixtureInterface::getOrder()
     *
     * @return integer
     */
    public function getOrder()
    {
        return 3; // the order in which fixtures will be loaded
    }
}
