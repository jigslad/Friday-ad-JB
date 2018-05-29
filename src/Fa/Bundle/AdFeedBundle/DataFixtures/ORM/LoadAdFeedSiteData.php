<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdFeedBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Fa\Bundle\AdFeedBundle\Entity\AdFeedSite;
use Fa\Bundle\AdFeedBundle\Repository\AdFeedSiteRepository;

/**
 * This command is used to generate entity cache.
 *
 * php app/console fa:parse:feed parse  --type="boat" --site_id="1"
 * php app/console fa:parse:feed parse  --type="gun"  --site_id="8"
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class LoadAdFeedSiteData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * Load data fixtures with the passed EntityManager.
     *
     * @param ObjectManager $em Object.
     */
    public function load(ObjectManager $em)
    {
        $adFeedSite1 = new AdFeedSite();
        $adFeedSite1->setId(1);
        $adFeedSite1->setType('ClickEditVehicleAdvert');
        $adFeedSite1->setRefSiteId(10);
        $adFeedSite1->setStatus('A');
        $em->persist($adFeedSite1);
        $em->flush();

        $adFeedSite2 = new AdFeedSite();
        $adFeedSite2->setId(2);
        $adFeedSite2->setType('BoatAdvert');
        $adFeedSite2->setRefSiteId(10);
        $adFeedSite2->setStatus('A');
        $em->persist($adFeedSite2);
        $em->flush();

        $adFeedSite2 = new AdFeedSite();
        $adFeedSite2->setId(3);
        $adFeedSite2->setType('HorseAdvert');
        $adFeedSite2->setRefSiteId(10);
        $adFeedSite2->setStatus('A');
        $em->persist($adFeedSite2);
        $em->flush();

        $adFeedSite2 = new AdFeedSite();
        $adFeedSite2->setId(4);
        $adFeedSite2->setType('PropertyAdvert');
        $adFeedSite2->setRefSiteId(10);
        $adFeedSite2->setStatus('A');
        $em->persist($adFeedSite2);
        $em->flush();

        $adFeedSite2 = new AdFeedSite();
        $adFeedSite2->setId(5);
        $adFeedSite2->setType('PetAdvert');
        $adFeedSite2->setRefSiteId(10);
        $adFeedSite2->setStatus('A');
        $em->persist($adFeedSite2);
        $em->flush();

        $adFeedSite2 = new AdFeedSite();
        $adFeedSite2->setId(6);
        $adFeedSite2->setType('MerchandiseAdvert');
        $adFeedSite2->setRefSiteId(10);
        $adFeedSite2->setStatus('A');
        $em->persist($adFeedSite2);
        $em->flush();
    }

    /**
     * Get the order of this fixture.
     *
     * @return integer
     */
    public function getOrder()
    {
        return 6; // the order in which fixtures will be loaded
    }
}
