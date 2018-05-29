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
use Fa\Bundle\DotMailerBundle\Entity\DotmailerNewsletterType;
use Fa\Bundle\DotMailerBundle\Repository\DotmailerNewsletterTypeRepository;

/**
 * Load static page block data.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class LoadDotmailerNewsletterTypeDataData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * Load data.
     *
     * @param Doctrine\Common\Persistence\ObjectManager $em
     */
    public function load(ObjectManager $em)
    {
        // set class meta data
        $metadata = $em->getClassMetaData('Fa\Bundle\DotMailerBundle\Entity\DotmailerNewsletterType');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        $typeId = 1;
        foreach ($this->getNewsletterTypes() as $name => $idLabel) {
            $dotmailerNewsletterType = new DotmailerNewsletterType();
            $dotmailerNewsletterType->setId($typeId);
            $dotmailerNewsletterType->setName($name);
            $dotmailerNewsletterType->setLabel($idLabel['label']);
            $dotmailerNewsletterType->setOrd($idLabel['ord']);
            $dotmailerNewsletterType->setParentId($idLabel['parent_id']);
            if ($idLabel['id']) {
                $dotmailerNewsletterType->setCategoryId($idLabel['id']);
            }
            $em->persist($dotmailerNewsletterType);

            $typeId++;
        }
        $em->flush();
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

    private function getNewsletterTypes()
    {
        return array(
                'n-for-sale' => array('id' => 2, 'label' => '<b>For Sale</b>', 'ord' => 1, 'parent_id' => 0),
                'n-agricultural' => array('id' => 3, 'label' => 'Agricultural', 'ord' => 2, 'parent_id' => 1),
                'n-antiques' => array('id' => 39, 'label' => 'Antiques and Collectables', 'ord' => 3, 'parent_id' => 1),
                'n-baby-and-kids' => array('id' => 8, 'label' => 'Baby and Kids', 'ord' => 4, 'parent_id' => 1),
                'n-bizarre-bazaar' => array('id' => 827, 'label' => 'Bizarre Bazaar', 'ord' => 5, 'parent_id' => 1),
                'n-business-office' => array('id' => 18, 'label' => 'Business and Office', 'ord' => 6, 'parent_id' => 1),
                'n-electronics' => array('id' => 56, 'label' => 'Electronics', 'ord' => 7, 'parent_id' => 1),
                'n-fashion' => array('id' => 103, 'label' => 'Fashion', 'ord' => 8, 'parent_id' => 1),
                'n-home-and-garden' => array('id' => 158, 'label' => 'Home and Garden', 'ord' => 9, 'parent_id' => 1),
                'n-leisure' => array('id' => 361, 'label' => 'Leisure', 'ord' => 10, 'parent_id' => 1),
                'n-services' => array('id' => 585, 'label' => '<b>Services</b>', 'ord' => 11, 'parent_id' => 0),
                'n-business-legal' => array('id' => 627, 'label' => 'Business, Legal And It Services', 'ord' => 12, 'parent_id' => 11),
                'n-celebrations' => array('id' => 651, 'label' => 'Celebrations And Special Occasions', 'ord' => 13, 'parent_id' => 11),
                'n-family-care-servic' => array('id' => 586, 'label' => 'Family And Care Services', 'ord' => 14, 'parent_id' => 11),
                'n-health-beauty' => array('id' => 646, 'label' => 'Health And Beauty Services', 'ord' => 15, 'parent_id' => 11),
                'n-musical' => array('id' => 635, 'label' => 'Musical Services', 'ord' => 16, 'parent_id' => 11),
                'n-pet-farming-equest' => array('id' => 668, 'label' => 'Pet, Farming And Equestrian Services', 'ord' => 17, 'parent_id' => 11),
                'n-property-home' => array('id' => 592, 'label' => 'Property And Home Services', 'ord' => 18, 'parent_id' => 11),
                'n-transport-services' => array('id' => 3407, 'label' => 'Transport Services', 'ord' => 19, 'parent_id' => 11),
                'n-motors' => array('id' => 444, 'label' => '<b>Motors</b>', 'ord' => 20, 'parent_id' => 0),
                'n-boats' => array('id' => 445, 'label' => 'Boats', 'ord' => 21, 'parent_id' => 20),
                'n-cars' => array('id' => 456, 'label' => 'Cars', 'ord' => 22, 'parent_id' => 20),
                'n-motorcycles' => array('id' => 470, 'label' => 'Motorcycles', 'ord' => 23, 'parent_id' => 20),
                'n-motorhomes' => array('id' => 475, 'label' => 'Motorhomes And Caravans', 'ord' => 24, 'parent_id' => 20),
                'n-farm' => array('id' => 458, 'label' => 'Farm', 'ord' => 25, 'parent_id' => 20),
                'n-horseboxes' => array('id' => 468, 'label' => 'Horseboxes and Trailers', 'ord' => 26, 'parent_id' => 20),
                'n-commercial-vehicle' => array('id' => 457, 'label' => 'Commercial Vehicles', 'ord' => 27, 'parent_id' => 20),
                'n-properties' => array('id' => 678, 'label' => '<b>Property</b>', 'ord' => 28, 'parent_id' => 0),
                'n-properties-sale' => array('id' => 698, 'label' => 'Property for sale', 'ord' => 29, 'parent_id' => 28),
                'n-properties-rent' => array('id' => 679, 'label' => 'Property for rent', 'ord' => 30, 'parent_id' => 28),
                'n-properties-share' => array('id' => 717, 'label' => 'Shared properties', 'ord' => 31, 'parent_id' => 28),
                'n-animals' => array('id' => 725, 'label' => '<b>Animals</b>', 'ord' => 32, 'parent_id' => 0),
                'n-horses' => array('id' => 758, 'label' => 'Horses And Equestrian', 'ord' => 33, 'parent_id' => 32),
                'n-livestock' => array('id' => 766, 'label' => 'Livestock', 'ord' => 34, 'parent_id' => 32),
                'n-cats' => array('id' => 728, 'label' => 'Cats and Kittens', 'ord' => 35, 'parent_id' => 32),
                'n-dogs' => array('id' => 729, 'label' => 'Dogs And Puppies', 'ord' => 36, 'parent_id' => 32),
                'n-community' => array('id' => 783, 'label' => '<b>Community</b>', 'ord' => 37, 'parent_id' => 0),
                'n-classes-tuition' => array('id' => 803, 'label' => 'Classes And Tuition', 'ord' => 38, 'parent_id' => 37),
                'n-clubs-societies' => array('id' => 819, 'label' => 'Clubs, Societies and Teams', 'ord' => 39, 'parent_id' => 37),
                'n-friendship-dating' => array('id' => 824, 'label' => 'Friendship And Dating', 'ord' => 40, 'parent_id' => 37),
                'n-whats-on' => array('id' => 784, 'label' => "What's On", 'ord' => 41, 'parent_id' => 37),
                'n-in-your-area' => array('id' => 821, 'label' => 'In your Area', 'ord' => 42, 'parent_id' => 37),
                'n-jobs' => array('id' => 500, 'label' => '<b>Jobs</b>', 'ord' => 43, 'parent_id' => 0),
                'n-job-seekers' => array('id' => '', 'label' => 'Job seekers', 'ord' => 44, 'parent_id' => 43),
                'n-job-posters' => array('id' => '', 'label' => 'Job posters', 'ord' => 45, 'parent_id' => 43),
                'n-competitions' => array('id' => '', 'label' => '<b>Competitions</b>', 'ord' => 46, 'parent_id' => 0),
                'n-deals' => array('id' => '', 'label' => '<b>Deals</b>', 'ord' => 47, 'parent_id' => 0),
                'n-third-party' => array('id' => '', 'label' => '<b>Third Party</b>', 'ord' => 48, 'parent_id' => 0)
             );
    }
}