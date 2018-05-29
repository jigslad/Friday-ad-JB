<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\EntityBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Fa\Bundle\EntityBundle\Entity\Location;

/**
 * This controller is used for
 *
 * @author Sagar Lotiya <sagar@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */
class LoadLocationData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * Load fixture.
     *
     * @param ObjectManager $em object.
     */
    public function load(ObjectManager $em)
    {
        // code is keeped for future use but directly not used.
        return false;

        $metadata = $em->getClassMetaData('Fa\Bundle\EntityBundle\Entity\Location');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);


        $country_root = new Location();
        $country_root->setId(1);
        $country_root->setName('country_root');
        $em->persist($country_root);
        $em->flush();

        $country = new Location();
        $country->setId(2);
        $country->setName('United Kingdom');
        $country->setParent($country_root);
        $country->setUrl('uk');
        $em->persist($country);
        $em->flush();

        $metadata = $em->getClassMetaData('Fa\Bundle\EntityBundle\Entity\Location');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_AUTO);

        $reader = new \EasyCSV\Reader(__DIR__."/town_county.csv");
        $reader->setDelimiter(';');

        $batchSize = 100;
        $row = 0;
        while ($row = $reader->getRow()) {
            if ($row['table'] == 'county') {
                $county = new Location();
                $county->setName($row['county_name']);
                $county->setParent($country);
                $county->setLatitude($row['latitude']);
                $county->setLongitude($row['longitude']);
                $county->setOldRefId($row['county_id']);
                $county->setUrl($row['url']);
                if ($row['region_id'] != '#') {
                    $county->setRegionId($row['region_id']);
                }
                $em->persist($county);
            } else {
                $town = new Location();
                $town->setName($row['town_name']);
                $town->setParent($county);
                $town->setLatitude($row['latitude']);
                $town->setLongitude($row['longitude']);
                $town->setOldRefId($row['town_id']);
                $town->setUrl($row['url']);
                $em->persist($town);
            }

            if (($row % $batchSize) == 0) {
                $em->flush();
            }

            $row++;
        }
        $em->flush();
    }
    /**
     * Get order of fixture.
     *
     * @return integer
     */
    public function getOrder()
    {
        return 3; // the order in which fixtures will be loaded
    }
}
