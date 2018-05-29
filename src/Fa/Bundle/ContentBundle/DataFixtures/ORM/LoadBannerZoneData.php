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
use Fa\Bundle\ContentBundle\Entity\BannerZone;
use Fa\Bundle\ContentBundle\Repository\BannerZoneRepository;
use Gedmo\Sluggable\Util as Sluggable;

/**
 * Load static page block data.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class LoadBannerZoneData extends AbstractFixture implements OrderedFixtureInterface
{

    /**
     * Load data.
     *
     * @param Doctrine\Common\Persistence\ObjectManager $em
     */
    public function load(ObjectManager $em)
    {
        // set class meta data
        $metadata = $em->getClassMetaData('Fa\Bundle\ContentBundle\Entity\BannerZone');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        $homePage = $em->getRepository('Fa\Bundle\ContentBundle\Entity\BannerPage')->findOneBy(array('slug' => 'homepage'));
        $searchResultsPage = $em->getRepository('Fa\Bundle\ContentBundle\Entity\BannerPage')->findOneBy(array('slug' => 'search_results'));
        $adDetailsPage = $em->getRepository('Fa\Bundle\ContentBundle\Entity\BannerPage')->findOneBy(array('slug' => 'ad_details'));
        $allOtherPages = $em->getRepository('Fa\Bundle\ContentBundle\Entity\BannerPage')->findOneBy(array('slug' => 'all_other_pages'));

        $bannerZoneId = 1;

        $bannerZone1 = new BannerZone();
        $bannerZone1->setId($bannerZoneId);
        $bannerZone1->setName('Above Header');
        $bannerZone1->setSlug(Sluggable\Urlizer::urlize('Above Header', '_'));
        $bannerZone1->setMaxWidth(null);
        $bannerZone1->setMaxHeight(null);
        $bannerZone1->addBannerPage($homePage);
        $bannerZone1->addBannerPage($searchResultsPage);
        $bannerZone1->addBannerPage($adDetailsPage);
        $bannerZone1->addBannerPage($allOtherPages);
        $bannerZone1->setIsDesktop(1);
        $bannerZone1->setIsTablet(1);
        $em->persist($bannerZone1);
        $em->flush();
        $bannerZoneId++;

        $bannerZone2 = new BannerZone();
        $bannerZone2->setId($bannerZoneId);
        $bannerZone2->setName('In Header');
        $bannerZone2->setSlug(Sluggable\Urlizer::urlize('In Header', '_'));
        $bannerZone2->setMaxWidth(728);
        $bannerZone2->setMaxHeight(90);
        $bannerZone2->addBannerPage($searchResultsPage);
        $bannerZone2->addBannerPage($adDetailsPage);
        $bannerZone2->setIsDesktop(1);
        $bannerZone2->setIsTablet(1);
        $em->persist($bannerZone2);
        $em->flush();
        $bannerZoneId++;

        $bannerZone3 = new BannerZone();
        $bannerZone3->setId($bannerZoneId);
        $bannerZone3->setName('Margin Left');
        $bannerZone3->setSlug(Sluggable\Urlizer::urlize('Margin Left', '_'));
        $bannerZone3->setMaxWidth(null);
        $bannerZone3->setMaxHeight(null);
        $bannerZone3->addBannerPage($homePage);
        $bannerZone3->addBannerPage($searchResultsPage);
        $bannerZone3->addBannerPage($adDetailsPage);
        $bannerZone3->addBannerPage($allOtherPages);
        $bannerZone3->setIsDesktop(1);
        $em->persist($bannerZone3);
        $em->flush();
        $bannerZoneId++;

        $bannerZone4 = new BannerZone();
        $bannerZone4->setId($bannerZoneId);
        $bannerZone4->setName('Margin Right');
        $bannerZone4->setSlug(Sluggable\Urlizer::urlize('Margin Right', '_'));
        $bannerZone4->setMaxWidth(null);
        $bannerZone4->setMaxHeight(null);
        $bannerZone4->addBannerPage($homePage);
        $bannerZone4->addBannerPage($searchResultsPage);
        $bannerZone4->addBannerPage($adDetailsPage);
        $bannerZone4->addBannerPage($allOtherPages);
        $bannerZone4->setIsDesktop(1);
        $em->persist($bannerZone4);
        $em->flush();
        $bannerZoneId++;

        $bannerZone5 = new BannerZone();
        $bannerZone5->setId($bannerZoneId);
        $bannerZone5->setName('SR Above Results');
        $bannerZone5->setSlug(Sluggable\Urlizer::urlize('SR Above Results', '_'));
        $bannerZone5->setMaxWidth(800);
        $bannerZone5->setMaxHeight(null);
        $bannerZone5->addBannerPage($searchResultsPage);
        $bannerZone5->setIsDesktop(1);
        $bannerZone5->setIsTablet(1);
        $em->persist($bannerZone5);
        $em->flush();
        $bannerZoneId++;

        $bannerZone6 = new BannerZone();
        $bannerZone6->setId($bannerZoneId);
        $bannerZone6->setName('SR In Results Top');
        $bannerZone6->setSlug(Sluggable\Urlizer::urlize('SR In Results Top', '_'));
        $bannerZone6->setMaxWidth(800);
        $bannerZone6->setMaxHeight(null);
        $bannerZone6->addBannerPage($searchResultsPage);
        $bannerZone6->setIsDesktop(1);
        $bannerZone6->setIsTablet(1);
        $em->persist($bannerZone6);
        $em->flush();
        $bannerZoneId++;

        $bannerZone7 = new BannerZone();
        $bannerZone7->setId($bannerZoneId);
        $bannerZone7->setName('SR In Results Bottom');
        $bannerZone7->setSlug(Sluggable\Urlizer::urlize('SR In Results Bottom', '_'));
        $bannerZone7->setMaxWidth(800);
        $bannerZone7->setMaxHeight(null);
        $bannerZone7->addBannerPage($searchResultsPage);
        $bannerZone7->setIsDesktop(1);
        $bannerZone7->setIsTablet(1);
        $em->persist($bannerZone7);
        $em->flush();
        $bannerZoneId++;

        $bannerZone8 = new BannerZone();
        $bannerZone8->setId($bannerZoneId);
        $bannerZone8->setName('SR Below Results');
        $bannerZone8->setSlug(Sluggable\Urlizer::urlize('SR Below Results', '_'));
        $bannerZone8->setMaxWidth(800);
        $bannerZone8->setMaxHeight(null);
        $bannerZone8->addBannerPage($searchResultsPage);
        $bannerZone8->setIsDesktop(1);
        $bannerZone8->setIsTablet(1);
        $em->persist($bannerZone8);
        $em->flush();
        $bannerZoneId++;

        $bannerZone9 = new BannerZone();
        $bannerZone9->setId($bannerZoneId);
        $bannerZone9->setName('Ad Details Right');
        $bannerZone9->setSlug(Sluggable\Urlizer::urlize('Ad Details Right', '_'));
        $bannerZone9->setMaxWidth(300);
        $bannerZone9->setMaxHeight(250);
        $bannerZone9->addBannerPage($adDetailsPage);
        $bannerZone9->setIsDesktop(1);
        $bannerZone9->setIsTablet(1);
        $em->persist($bannerZone9);
        $em->flush();
        $bannerZoneId++;

        $bannerZone10 = new BannerZone();
        $bannerZone10->setId($bannerZoneId);
        $bannerZone10->setName('Ad Details Bottom');
        $bannerZone10->setSlug(Sluggable\Urlizer::urlize('Ad Details Bottom', '_'));
        $bannerZone10->setMaxWidth(728);
        $bannerZone10->setMaxHeight(90);
        $bannerZone10->addBannerPage($adDetailsPage);
        $bannerZone10->setIsDesktop(1);
        $bannerZone10->setIsTablet(1);
        $em->persist($bannerZone10);
        $em->flush();
        $bannerZoneId++;

        $bannerZone11 = new BannerZone();
        $bannerZone11->setId($bannerZoneId);
        $bannerZone11->setName('SR Mobile Above Results');
        $bannerZone11->setSlug(Sluggable\Urlizer::urlize('SR Mobile Above Results', '_'));
        $bannerZone11->setMaxWidth(320);
        $bannerZone11->setMaxHeight(null);
        $bannerZone11->addBannerPage($searchResultsPage);
        $bannerZone11->setIsMobile(1);
        $em->persist($bannerZone11);
        $em->flush();
        $bannerZoneId++;

        $bannerZone12 = new BannerZone();
        $bannerZone12->setId($bannerZoneId);
        $bannerZone12->setName('SR Mobile In Results');
        $bannerZone12->setSlug(Sluggable\Urlizer::urlize('SR Mobile In Results', '_'));
        $bannerZone12->setMaxWidth(320);
        $bannerZone12->setMaxHeight(null);
        $bannerZone12->addBannerPage($searchResultsPage);
        $bannerZone12->setIsMobile(1);
        $em->persist($bannerZone12);
        $em->flush();
        $bannerZoneId++;

        $bannerZone13 = new BannerZone();
        $bannerZone13->setId($bannerZoneId);
        $bannerZone13->setName('SR Mobile Below Results');
        $bannerZone13->setSlug(Sluggable\Urlizer::urlize('SR Mobile Below Results', '_'));
        $bannerZone13->setMaxWidth(300);
        $bannerZone13->setMaxHeight(250);
        $bannerZone13->addBannerPage($searchResultsPage);
        $bannerZone13->setIsMobile(1);
        $em->persist($bannerZone13);
        $em->flush();
        $bannerZoneId++;

        $bannerZone14 = new BannerZone();
        $bannerZone14->setId($bannerZoneId);
        $bannerZone14->setName('Pixel Tracking');
        $bannerZone14->setSlug(Sluggable\Urlizer::urlize('Pixel Tracking', '_'));
        $bannerZone14->setMaxWidth(1);
        $bannerZone14->setMaxHeight(1);
        $bannerZone14->addBannerPage($homePage);
        $bannerZone14->addBannerPage($searchResultsPage);
        $bannerZone14->addBannerPage($adDetailsPage);
        $bannerZone14->addBannerPage($allOtherPages);
        $bannerZone14->setIsDesktop(1);
        $bannerZone14->setIsTablet(1);
        $bannerZone14->setIsMobile(1);
        $em->persist($bannerZone14);
        $em->flush();
        $bannerZoneId++;
    }

    /**
     * Get order.
     *
     * @return integer
     */
    public function getOrder()
    {
        return 7; // the order in which fixtures will be loaded
    }
}
