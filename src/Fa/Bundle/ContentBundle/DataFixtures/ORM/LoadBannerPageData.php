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
use Fa\Bundle\ContentBundle\Entity\BannerPage;
use Fa\Bundle\ContentBundle\Repository\BannerPageRepository;
use Gedmo\Sluggable\Util as Sluggable;

/**
 * Load static page block data.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class LoadBannerPageData extends AbstractFixture implements OrderedFixtureInterface
{

    /**
     * Load data.
     *
     * @param Doctrine\Common\Persistence\ObjectManager $em
     */
    public function load(ObjectManager $em)
    {
        // set class meta data
        $metadata = $em->getClassMetaData('Fa\Bundle\ContentBundle\Entity\BannerPage');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        $bannerPageId = 1;

        $bannerPage1 = new BannerPage();
        $bannerPage1->setId($bannerPageId);
        $bannerPage1->setName('Homepage');
        $bannerPage1->setSlug(Sluggable\Urlizer::urlize('Homepage', '_'));
        $em->persist($bannerPage1);
        $em->flush();
        $bannerPageId++;

        $bannerPage2 = new BannerPage();
        $bannerPage2->setId($bannerPageId);
        $bannerPage2->setName('Search Results');
        $bannerPage2->setSlug(Sluggable\Urlizer::urlize('Search Results', '_'));
        $em->persist($bannerPage2);
        $em->flush();
        $bannerPageId++;

        $bannerPage3 = new BannerPage();
        $bannerPage3->setId($bannerPageId);
        $bannerPage3->setName('Ad Details');
        $bannerPage3->setSlug(Sluggable\Urlizer::urlize('Ad Details', '_'));
        $em->persist($bannerPage3);
        $em->flush();
        $bannerPageId++;

        $bannerPage4 = new BannerPage();
        $bannerPage4->setId($bannerPageId);
        $bannerPage4->setName('All Other Pages');
        $bannerPage4->setSlug(Sluggable\Urlizer::urlize('All Other Pages', '_'));
        $em->persist($bannerPage4);
        $em->flush();
        $bannerPageId++;
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
