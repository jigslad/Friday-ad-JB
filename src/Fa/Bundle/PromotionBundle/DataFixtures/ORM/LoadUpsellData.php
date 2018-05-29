<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\PromotionBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Fa\Bundle\PromotionBundle\Entity\Upsell;
use Fa\Bundle\PromotionBundle\Repository\UpsellRepository;

/**
 * This fixture is used to load upsell data.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class LoadUpsellData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
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
        $translatable = $this->container->get('gedmo.listener.translatable');
        $translatable->setTranslatableLocale($this->container->getParameter('locale'));
        $metadata = $em->getClassMetaData('Fa\Bundle\PromotionBundle\Entity\Upsell');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        $upsellId = 1;
        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::UPSELL_TYPE_ADDITIONAL_PHOTO_ID);
        $upsell->setValue('1');
        $upsell->setTitle('1 photo');
        $upsell->setUpsellFor('ad');
        $upsell->setStatus(1);
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::UPSELL_TYPE_ADDITIONAL_PHOTO_ID);
        $upsell->setValue('2');
        $upsell->setTitle('2 photos');
        $upsell->setUpsellFor('ad');
        $upsell->setStatus(1);
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::UPSELL_TYPE_ADDITIONAL_PHOTO_ID);
        $upsell->setValue('8');
        $upsell->setTitle('8 photos');
        $upsell->setUpsellFor('ad');
        $upsell->setStatus(1);
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::UPSELL_TYPE_ADDITIONAL_PHOTO_ID);
        $upsell->setValue('20');
        $upsell->setTitle('20 photos');
        $upsell->setUpsellFor('ad');
        $upsell->setStatus(1);
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::UPSELL_TYPE_TOP_ADVERT_ID);
        $upsell->setTitle('Top Ad');
        $upsell->setUpsellFor('ad');
        $upsell->setDuration('7d');
        $upsell->setStatus(1);
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::UPSELL_TYPE_URGENT_ADVERT_ID);
        $upsell->setTitle('Urgent Ad');
        $upsell->setUpsellFor('ad');
        $upsell->setStatus(1);
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::UPSELL_TYPE_PRINT_EDITIONS_ID);
        $upsell->setTitle('1 Edition Print Publication');
        $upsell->setUpsellFor('ad');
        $upsell->setValue('1');
        $upsell->setStatus(1);
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::UPSELL_TYPE_PRINT_EDITIONS_ID);
        $upsell->setTitle('3 Editions Print Publication');
        $upsell->setUpsellFor('ad');
        $upsell->setValue('3');
        $upsell->setStatus(1);
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::UPSELL_TYPE_PRINT_EDITIONS_ID);
        $upsell->setTitle('5 Editions Print Publication');
        $upsell->setUpsellFor('ad');
        $upsell->setValue('5');
        $upsell->setStatus(1);
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::UPSELL_TYPE_PRINT_PHOTO_ID);
        $upsell->setTitle('Photo in Print');
        $upsell->setUpsellFor('ad');
        $upsell->setStatus(1);
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::UPSELL_TYPE_AD_REFRESH_ID);
        $upsell->setTitle('Weekly Ad Refresh');
        $upsell->setUpsellFor('ad');
        $upsell->setValue('7');
        $upsell->setStatus(1);
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::UPSELL_TYPE_AD_REFRESH_ID);
        $upsell->setTitle('Monthly Ad Refresh');
        $upsell->setUpsellFor('ad');
        $upsell->setValue('30');
        $upsell->setStatus(1);
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::UPSELL_TYPE_BRANDING_ID);
        $upsell->setTitle('Branding');
        $upsell->setUpsellFor('ad');
        $upsell->setStatus(1);
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::UPSELL_TYPE_VIDEO_ID);
        $upsell->setTitle('Video');
        $upsell->setUpsellFor('ad');
        $upsell->setStatus(1);
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::UPSELL_TYPE_TARGETED_EMAILS_ID);
        $upsell->setTitle('Targeted Emails');
        $upsell->setUpsellFor('ad');
        $upsell->setStatus(1);
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::UPSELL_TYPE_LOCATION_LOOKUP_ID);
        $upsell->setTitle('Location Lookup');
        $upsell->setUpsellFor('ad');
        $upsell->setStatus(1);
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::UPSELL_TYPE_ATTACH_DOCUMENTS_ID);
        $upsell->setTitle('1 Document Upload');
        $upsell->setUpsellFor('ad');
        $upsell->setValue(1);
        $upsell->setStatus(1);
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::UPSELL_TYPE_ATTACH_DOCUMENTS_ID);
        $upsell->setTitle('3 Document Upload');
        $upsell->setUpsellFor('ad');
        $upsell->setValue(3);
        $upsell->setStatus(1);
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::UPSELL_TYPE_SCREENING_QUESTIONS_ID);
        $upsell->setTitle('1 Screening Question');
        $upsell->setUpsellFor('ad');
        $upsell->setValue(1);
        $upsell->setStatus(1);
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::UPSELL_TYPE_SCREENING_QUESTIONS_ID);
        $upsell->setTitle('5 Screening Question');
        $upsell->setUpsellFor('ad');
        $upsell->setValue(5);
        $upsell->setStatus(1);
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::UPSELL_TYPE_LISTED_ON_FMG_SITE_ID);
        $upsell->setTitle('FMG Sites Listed');
        $upsell->setUpsellFor('ad');
        $upsell->setStatus(1);
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::UPSELL_TYPE_ACCURATE_VALUATION_ID);
        $upsell->setTitle('Accurate Valuation');
        $upsell->setUpsellFor('ad');
        $upsell->setStatus(1);
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::UPSELL_TYPE_EXPANDED_LOCATION_ID);
        $upsell->setTitle('Local Listing');
        $upsell->setUpsellFor('ad');
        $upsell->setValue('30');
        $upsell->setStatus(1);
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::UPSELL_TYPE_EXPANDED_LOCATION_ID);
        $upsell->setTitle('National Listing');
        $upsell->setValue('National');
        $upsell->setUpsellFor('ad');
        $upsell->setStatus(1);
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::SHOP_ENHANCED_PROFILE);
        $upsell->setTitle('Enhanced profile');
        $upsell->setStatus(1);
        $upsell->setUpsellFor('shop');
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::SHOP_VARIFIED_BUSINESS_BADGE);
        $upsell->setTitle('Verified business badge');
        $upsell->setStatus(1);
        $upsell->setUpsellFor('shop');
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::SHOP_PROFILE_EXPOSURE);
        $upsell->setTitle('Profile exposure');
        $upsell->setStatus(1);
        $upsell->setUpsellFor('shop');
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::SHOP_PROFILE_EXPOSURE);
        $upsell->setTitle('Profile exposure (30 miles)');
        $upsell->setStatus(1);
        $upsell->setValue('30');
        $upsell->setUpsellFor('shop');
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::SHOP_PROFILE_EXPOSURE);
        $upsell->setTitle('Profile exposure (60 miles)');
        $upsell->setStatus(1);
        $upsell->setValue('60');
        $upsell->setUpsellFor('shop');
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::SHOP_PROFILE_EXPOSURE);
        $upsell->setTitle('Profile exposure (national)');
        $upsell->setStatus(1);
        $upsell->setValue('national');
        $upsell->setUpsellFor('shop');
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::SHOP_ADVERT_EXPOSURE);
        $upsell->setTitle('Advert exposure');
        $upsell->setStatus(1);
        $upsell->setUpsellFor('shop');
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::SHOP_ADVERT_EXPOSURE);
        $upsell->setTitle('Advert exposure (30 miles)');
        $upsell->setStatus(1);
        $upsell->setValue('30');
        $upsell->setUpsellFor('shop');
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::SHOP_ADVERT_EXPOSURE);
        $upsell->setTitle('Advert exposure (60 miles)');
        $upsell->setStatus(1);
        $upsell->setValue('60');
        $upsell->setUpsellFor('shop');
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::SHOP_ADVERT_EXPOSURE);
        $upsell->setTitle('Advert exposure (national)');
        $upsell->setStatus(1);
        $upsell->setValue('national');
        $upsell->setUpsellFor('shop');
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::SHOP_ITEM_QUANTITIES);
        $upsell->setTitle('Item quantities');
        $upsell->setStatus(1);
        $upsell->setUpsellFor('shop');
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::SHOP_FULL_SOCIAL_INTEGRATION);
        $upsell->setTitle('Full social integration');
        $upsell->setStatus(1);
        $upsell->setUpsellFor('shop');
        $em->persist($upsell);
        $em->flush();
        $upsellId++;

        $upsell = new Upsell();
        $upsell->setId($upsellId);
        $upsell->setType(UpsellRepository::UPSELL_TYPE_HOMEPAGE_FEATURE_ADVERT_ID);
        $upsell->setTitle('Homepage Advert');
        $upsell->setStatus(1);
        $upsell->setDuration('7d');
        $upsell->setUpsellFor('ad');
        $em->persist($upsell);
        $em->flush();
        $upsellId++;
    }

    /**
     * (non-PHPdoc)
     * @see \Doctrine\Common\DataFixtures\OrderedFixtureInterface::getOrder()
     *
     * @return integer
     */
    public function getOrder()
    {
        return 1; // the order in which fixtures will be loaded
    }
}
