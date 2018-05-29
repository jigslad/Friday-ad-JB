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
use Fa\Bundle\CoreBundle\Entity\ConfigRule;
use Fa\Bundle\CoreBundle\Entity\Config;
use Fa\Bundle\CoreBundle\Repository\ConfigRepository;

/**
 * This fixture is used to load config rule data .
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class LoadConfigRuleData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * (non-PHPdoc)
     * @see \Doctrine\Common\DataFixtures\FixtureInterface::load()
     */
    public function load(ObjectManager $em)
    {
        // set class meta data
        $metadata = $em->getClassMetaData('Fa\Bundle\CoreBundle\Entity\Config');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        $config1 = new Config();
        $config1->setId(ConfigRepository::PAYPAL_COMMISION);
        $config1->setName("PayPal Commission");
        $config1->setCreatedAt(time());
        $em->persist($config1);
        $em->flush();

        $config2 = new Config();
        $config2->setId(ConfigRepository::PRODUCT_INSERTION_FEE);
        $config2->setName("Product Insertion Fee");
        $config2->setCreatedAt(time());
        $em->persist($config2);
        $em->flush();

        $config3 = new Config();
        $config3->setId(ConfigRepository::AD_EXPIRATION_DAYS);
        $config3->setName("Ad Expiration Days");
        $config3->setCreatedAt(time());
        $em->persist($config3);
        $em->flush();

        $config4 = new Config();
        $config4->setId(ConfigRepository::LISTING_TOPAD_SLOTS);
        $config4->setName("Listing Top Ad Slots");
        $config4->setCreatedAt(time());
        $em->persist($config4);
        $em->flush();

        $config5 = new Config();
        $config5->setId(ConfigRepository::PERIOD_BEFORE_CHECKING_VIEWS);
        $config5->setName("Period(in days) before checking views(Move expired to archive)");
        $config5->setCreatedAt(time());
        $em->persist($config5);
        $em->flush();

        $config6 = new Config();
        $config6->setId(ConfigRepository::PRECEDING_PERIOD_TO_CHECK_VIEWS);
        $config6->setName("Preceding period(in days) to check views(Move expired to archive)");
        $config6->setCreatedAt(time());
        $em->persist($config6);
        $em->flush();

        $config7 = new Config();
        $config7->setId(ConfigRepository::VAT_AMOUNT);
        $config7->setName("Vat Amount");
        $config7->setCreatedAt(time());
        $em->persist($config7);
        $em->flush();

        $config8 = new Config();
        $config8->setId(ConfigRepository::NUMBER_OF_ORGANIC_RESULTS);
        $config8->setName("Number of organic results");
        $config8->setCreatedAt(time());
        $em->persist($config8);
        $em->flush();

        $config9 = new Config();
        $config9->setId(ConfigRepository::NUMBER_OF_BUSINESSPAGE_SLOTS);
        $config9->setName("Number of business page slots");
        $config9->setCreatedAt(time());
        $em->persist($config9);
        $em->flush();

        $config10 = new Config();
        $config10->setId(ConfigRepository::TOP_BUSINESSPAGE);
        $config10->setName("Top business page");
        $config10->setCreatedAt(time());
        $em->persist($config10);
        $em->flush();
    }

    /**
     * (non-PHPdoc)
     * @see \Doctrine\Common\DataFixtures\OrderedFixtureInterface::getOrder()
     */
    public function getOrder()
    {
        return 6; // the order in which fixtures will be loaded
    }
}
