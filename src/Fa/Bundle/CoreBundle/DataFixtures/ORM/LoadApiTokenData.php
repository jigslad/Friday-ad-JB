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
use Fa\Bundle\CoreBundle\Entity\ApiToken;
use Fa\Bundle\CoreBundle\Repository\ApiTokenRepository;

/**
 * This fixture is used to load api token data .
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class LoadApiTokenData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * (non-PHPdoc)
     * @see \Doctrine\Common\DataFixtures\FixtureInterface::load()
     */
    public function load(ObjectManager $em)
    {
        // set class meta data
        $metadata = $em->getClassMetaData('Fa\Bundle\CoreBundle\Entity\ApiToken');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        $printApiToken = new ApiToken();
        $printApiToken->setId(1);
        $printApiToken->setType(ApiTokenRepository::PRINT_API_TYPE_ID);
        $printApiToken->setToken(md5('!@#$%FA-Print-Api-!@#$%'));
        $printApiToken->setCreatedAt(time());
        $em->persist($printApiToken);
        $em->flush();

        $similarAdApiToken = new ApiToken();
        $similarAdApiToken->setId(2);
        $similarAdApiToken->setType(ApiTokenRepository::SIMILAR_AD_API_TYPE_ID);
        $similarAdApiToken->setToken(md5('!@#$%FA-Similar-Ad-Api-!@#$%'));
        $similarAdApiToken->setCreatedAt(time());
        $em->persist($similarAdApiToken);
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
