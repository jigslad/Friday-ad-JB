<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Fa\Bundle\UserBundle\Repository\UserSearchAgentRepository;
use Gedmo\Sluggable\Util\Urlizer as Urlizer;

/**
 * This command is used to update dimensionads.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class UpdateSearchAgentCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:search-agent')
        ->setDescription("Update Search agent")
        ->addArgument('action', InputArgument::REQUIRED, 'add')
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('last_days', null, InputOption::VALUE_OPTIONAL, 'add or update for last few days only', null)
        ->setHelp(
            <<<EOF
Actions:
Command:
   php app/console update:search-agent add
EOF
        );
    }

    /**
     * Execute.
     *
     * @param InputInterface  $input  InputInterface object.
     * @param OutputInterface $output OutputInterface object.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        //get arguments passed in command
        $action = $input->getArgument('action');

        //get options passed in command
        $offset   = $input->getOption('offset');

        $searchParam = array();

        if ($action == 'add') {
            if (isset($offset)) {
                $this->updateDimensionWithOffset($searchParam, $input, $output);
            } else {
                $this->updateDimension($searchParam, $input, $output);
            }
        }
    }

    /**
     *   *Ad Placed -> not available in FADR
    AdultOrChild -> not available in FADR
    AdvertFlags -> not available in FADR
    Advertiser -> not available in FADR
    Service History
     *
     */


    /**
     Distance
     Category
     Section
     Search Term
     Private Or Trade
     Amenities
     Bathrooms
     Bedrooms
    Price
    Price Per Month
    Price Range
    Price Range Motoring
    Price Range Property For Rent
    Price Range Property For Sale
     *
     */
    /**



Colour -- Used only for Cars and Commercial Vehicles
Condition -- not used for any filter
Doors
Engine Size
Location

Make
Make(s) // not searchable in FADR
Makes(s)

Mileage
Model
Model(s)
Outside Space
PostTown

Property Type
Property Type Spain
Rent Or Buy
Town
Transmission
     */


    /**
     * Update dimension with given offset.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function updateDimensionWithOffset($searchParam, $input, $output)
    {
        $idsNotFound = array();
        $idsFound    = array();
        $qb          = $this->getAgentQueryBuilder($searchParam);
        $step        = 1000;
        $offset      = $input->getOption('offset');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $agents = $qb->getQuery()->getResult();
        $em  = $this->getContainer()->get('doctrine')->getManager();



        foreach ($agents as $agent) {
            $new_criteria = array();

            $new_criteria['sorter'] = array(
                    'sort_field' => 'item__published_at',
                    'sort_ord'   => 'desc',
            );

            $new_criteria['search'] = array();

            $criteria = explode('|', $agent->getOldCriteria());

            foreach ($criteria as $c) {
                $cr = explode(':', $c);
                $dr = array();

                if (count($cr) == 2) {
                    $dr[$cr[0]] = $cr[1];
                    foreach ($dr as $k => $v) {
                        $v = strtolower(trim($v));
                        $k = trim($k);

                        if ($k == 'Location' || $k == 'Town') {
                            $loc = $this->prepareLocationMapping($v);
                            if ($loc == 'reject') {
                                $agent->setStatus(0);
                                $em->persist($agent);
                                break 2;
                            } else {
                                $new_criteria['search']['item__location'] = $loc;
                                $new_criteria['search']['item__distance'] =  $this->getInt($v);
                            }
                        }


                        if ($k == 'Search Term') {
                            $new_criteria['search']['keywords'] = isset($new_criteria['search']['keywords']) ? $new_criteria['search']['keywords'].' '.$new_criteria['search']['keywords'] : $v;
                        }

                        if ($k == 'Category' || $k == 'Section') {
                            $cat_id = $this->prepareCategoryMapping($v);
                            if (is_integer($cat_id)) {
                                $new_criteria['search']['item__category_id'] = $cat_id;
                                $pcat = $this->getFirstLevelParent($new_criteria['search']['item__category_id']);
                                $scat = $this->getSecondLevelParent($new_criteria['search']['item__category_id']);
                            } else {
                                $new_criteria['search']['keywords'] = isset($new_criteria['search']['keywords']) ? $new_criteria['search']['keywords'].' '.$cat_id : $cat_id;
                            }
                        }

                        if ($k == 'Make') {
                            if (isset($scat['name']) &&  $scat['name'] == 'Cars') {
                                $makeModelUrl = Urlizer::urlize($v);
                                $category =  $this->em->getRepository('FaEntityBundle:Category')->getIdByNameAndFullSlugPattern($v, 'motors/cars/'.$makeModelUrl);
                                if (count($category) > 0) {
                                    $new_criteria['search']['item__category_id'] = $category[0]['id'];
                                };
                            } elseif (isset($scat['name']) && $scat['name'] == 'Commercial Vehicles') {
                                $makeModelUrl = Urlizer::urlize($v);
                                $category =  $this->em->getRepository('FaEntityBundle:Category')->getIdByNameAndFullSlugPattern($v, 'motors/commercial-vehicles/'.$makeModelUrl);
                                if (count($category) > 0) {
                                    $new_criteria['search']['item__category_id'] = $category[0]['id'];
                                };
                            }
                        }

                        if ($k == 'Make(s)' || $k =='Makes(s)') {
                            $make = explode(', ', $v);

                            if (isset($scat['name']) && $scat['name'] == 'Cars' && isset($make[0])) {
                                $makeModelUrl = Urlizer::urlize($make[0]);
                                $category =  $this->em->getRepository('FaEntityBundle:Category')->getIdByNameAndFullSlugPattern($v, 'motors/cars/'.$makeModelUrl);
                                if (count($category) > 0) {
                                    $new_criteria['search']['item__category_id'] = $category[0]['id'];
                                };
                            } elseif (isset($scat['name']) && $scat['name'] == 'Commercial Vehicles' && isset($make[0])) {
                                $makeModelUrl = Urlizer::urlize($make[0]);
                                $category =  $this->em->getRepository('FaEntityBundle:Category')->getIdByNameAndFullSlugPattern($v, 'motors/commercial-vehicles/'.$makeModelUrl);
                                if (count($category) > 0) {
                                    $new_criteria['search']['item__category_id'] = $category[0]['id'];
                                };
                            }
                        }

                        if ($k == 'Distance') {
                            $new_criteria['search']['item__distance'] =  $this->getInt($v);
                        }

                        if ($k == 'Private Or Trade') {
                            if ($v == 'trade') {
                                $new_criteria['search']['item__is_trade_ad'] =  1;
                            } else {
                                $new_criteria['search']['item__is_trade_ad'] =  0;
                            }
                        }

                        if ($k == 'Amenities') {
                            if (isset($pcat['name']) && $pcat['name'] == "Property") {
                                $new_criteria['search']['item_property__amenities_id'] = $this->getAmenities($v);
                            }
                        }

                        if ($k == 'Bathrooms') {
                            $new_criteria['search']['item_property__number_of_bathrooms_id'] =  $this->getBathRooms($this->getInt($v));
                        }

                        if ($k == 'Bedrooms') {
                            $new_criteria['search']['item_property__number_of_bedroom_id'] =  $this->getBedrooms($this->getInt($v));
                        }

                        if ($k == 'Price' || $k == 'Price Per Month' || $k == 'Price Range' || $k =='Price Range Motoring' ||  $k == 'Price Range Property For Rent' ||  $k == 'Price Range Property For Sale') {
                            $p = explode('-', $v);

                            if (isset($p[0])) {
                                $new_criteria['search']['item__price_from'] = intval($p[0]);
                            }

                            if (isset($p[1])) {
                                $new_criteria['search']['item__price_to'] =  intval($p[1]);
                            }
                        }

                        if ($k == 'Colour') {
                            $d = 0;
                            if (isset($new_criteria['search']['item__category_id'])) {
                                if ($pcat['name'] == 'Motors') {
                                    if ($scat['name'] == 'Cars') {
                                        $new_criteria['search']['item_motors__colour_id'] = $this->getCarColorId($v);
                                        $d = 1;
                                    } elseif ($scat['name'] == 'Commercial Vehicles') {
                                        $new_criteria['search']['item_motors__colour_id'] = $this->getCVColorId($v);
                                        $d = 1;
                                    }
                                }
                            }
                        }

                        if ($k == 'Mileage') {
                            $m = explode('-', $v);

                            $m1 = intval($m[0]);
                            $m2 =  isset($m[1]) ? intval($m[1]) : 0;
                            $m2 = $m2 == 0 ? $m1 : $m2;

                            if ($m2 <= 25000) {
                                $range = '0-25000';
                            } elseif ($m2 <= 50000) {
                                $range = '25001-50000';
                            } elseif ($m2 <= 75000) {
                                $range = '50001-75000';
                            } elseif ($m2 <= 75000) {
                                $range = '50001-75000';
                            } elseif ($m2 <= 100000) {
                                $range = '75001-100000';
                            } elseif ($m2 > 100000) {
                                $range = '100000+';
                            }

                            $new_criteria['search']['item_motors__mileage_range'] = $range;
                        }

                        if ($k == 'Condition') {
                            // remaining
                        }

                        if ($k == 'Doors') {
                            //ignore
                        }

                        if ($k == 'Engine Size') {
                            //ignore
                        }

                        if ($k == 'Engine Size CC') {
                            // ingored in FADR
                        }

                        if ($k == 'Features') {
                            // ingored in FADR
                        }

                        if ($k == 'Transmission') {
                            if ($scat['name'] == 'Cars') {
                                $new_criteria['search']['item_motors__transmission_id'] = $this->getCarTransmissionId($v);
                            } elseif ($scat['name'] == 'Commercial Vehicles') {
                                $new_criteria['search']['item_motors__transmission_id'] = $this->getCVTransmissionId($v);
                            }
                        }

                        if ($k == 'Fuel Type') {
                            if ($scat['name'] == 'Cars') {
                                $new_criteria['search']['item_motors__fuel_type_id'] = $this->getCarFuelTypeId($v);
                            } elseif ($scat['name'] == 'Commercial Vehicles') {
                                $new_criteria['search']['item_motors__fuel_type_id'] = $this->getCVFuelTypeId($v);
                            } elseif ($scat['name'] == 'Boats') {
                                $new_criteria['search']['item_motors__fuel_type_id'] = $this->getBoatFuelTypeId($v);
                            }
                        }
                    }
                }
            }

            $agent->setCriteria(serialize($new_criteria));
            $em->persist($agent);
        }

        $em->flush();

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    private function getBoatFuelTypeId($string)
    {
        $string = trim($string);
        $cType  = array();

        $cType['petrol']    = 1627;
        $cType['diesel']    = 1628;
        $cType['daul-Fuel'] = 1629;
        $cType['gas']       = 1631;
        $cType['electric']  = 6418;
        $cType['hybrid']    = 1630;

        if (isset($cType[$string])) {
            return $cType[$string];
        }
    }

    //Unleaded, LPG, Leaded, Gas, Dual Fuel
    public function getCVFuelTypeId($string)
    {
        $string = trim($string);
        $cType  = array();

        $cType['petrol'] = 6404;
        $cType['diesel'] = 6405;
        $cType['electric'] = 6407;
        $cType['hybrid'] = 6406;

        if (isset($cType[$string])) {
            return $cType[$string];
        } else {
            // echo $string."\n";
        }
    }

    //Dual Fuel, Unleaded, Leaded, LPG
    public function getCarFuelTypeId($string)
    {
        $string = strtolower(trim($string));
        $cType  = array();

        $cType['petrol'] = 6367;
        $cType['diesel'] = 6368;
        $cType['electric'] = 6370;
        $cType['hybrid'] = 6369;

        if (isset($cType[$string])) {
            return $cType[$string];
        } else {
            // echo $string."\n";
        }
    }

    private function getAmenities($string)
    {
        if ($string == 'central heating') {
            $string = 'Gas Central Heating';
        } elseif ($string == 'fireplaces') {
            $string = 'Fireplace';
        }

        $entityCache = $this->getContainer()->get('fa.entity.cache.manager');
        $entityId =  $entityCache->getEntityIdByName('FaEntityBundle:Entity', $string);

        if ($entityId) {
            return $entityId;
        }
    }

    private function getBedrooms($string)
    {
        $string = strtolower(trim($string));
        $cType  = array();
        // mapping done
        $cType['1'] = 2525;
        $cType['2'] = 2526;
        $cType['3'] = 2527;
        $cType['4'] = 2528;
        $cType['5'] = 2529; // TODO: confirm with client which mapping should we use for 5+

        if (isset($cType[$string])) {
            return $cType[$string];
        }
    }

    private function getBathRooms($string)
    {
        $string = strtolower(trim($string));
        $cType  = array();
        // mapping done
        $cType['1'] = 2534;
        $cType['2'] = 2535;
        $cType['3'] = 2536; // TODO: confirm with client which mapping should we use for 5+

        if (isset($cType[$string])) {
            return $cType[$string];
        }
    }



    /**
     * get first level parent
     *
     * @param integer $category_id
     *
     * @return object
     */
    private function getFirstLevelParent($category_id)
    {
        $cat = $this->em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($category_id, false, $this->getContainer());
        return $this->em->getRepository('FaEntityBundle:Category')->getCategoryArrayById(key($cat), $this->getContainer());
    }

    /**
     * get integer value
     *
     */
    private function getInt($s)
    {
        return (int) preg_replace('/[^\-\d]*(\-?\d*).*/', '$1', $s);
    }

    /**
     *
     * @param unknown $string
     */
    private function prepareLocationMapping($string)
    {
        if ($string == 'uk' || in_array($string, array('anywhere', 'any', 'any where'))) {
            $string = 'United Kingdom';
        }

        if ($string == 'bexhill') {
            $string = 'Bexhill On Sea';
        }

        if ($string == 'spain' || $string == 'gibraltar') {
            return 'reject';
        }

        $string = str_replace(array('-', '.', '  '), ' ', $string);


        $location = $this->em->getRepository('FaEntityBundle:Location')->findOneBy(array('name' => $string));

        if ($location) {
            return $location->getId();
        } else {
            $postcode = $this->em->getRepository('FaEntityBundle:Postcode')->findOneBy(array('post_code_c' => str_replace(' ', '', $string)));

            if ($postcode) {
                return $postcode->getPostCode();
            } else {
                $locality = $this->em->getRepository('FaEntityBundle:Locality')->findOneBy(array('name' => $string));
                if ($locality) {
                    return $locality->getId().','.$locality->getTownId();
                } else {
                    return 'reject';
                }
            }
        }
    }


    public function getCarColorId($string)
    {
        $string = trim(strtolower($string));
        $cType  = array();

        $cType['red'] = 6352;
        $cType['blue'] = 6341;
        $cType['white'] = 6354;
        $cType['black'] = 6340;
        $cType['gold'] = 6344;
        $cType['yellow'] = 6355;
        $cType['silver'] = 6338;
        $cType['grey'] = 6346;
        $cType['green'] = 6345;
        $cType['purple'] = 6348;
        $cType['orange'] = 6350;
        $cType['cream'] = 6339;
        $cType['pink'] = 6351;
        $cType['turquoise'] = 6353;
        $cType['maroon'] = 6347;
        $cType['bronze'] = 6342;

        if (isset($cType[$string])) {
            return $cType[$string];
        } else {
            // echo $string."\n";
        }
    }

    private function getCVColorId($string)
    {
        $string = trim($string);
        $cType  = array();

        $cType['red'] = 6389;
        $cType['blue'] = 6378;
        $cType['white'] = 6391;
        $cType['black'] = 6377;
        $cType['gold'] = 6381;
        $cType['yellow'] = 6392;
        $cType['silver'] = 6375;
        $cType['grey'] = 6383;
        $cType['green'] = 6382;
        $cType['purple'] = 6385;
        $cType['orange'] = 6387;
        $cType['cream'] = 6376;
        $cType['pink'] = 6388;
        $cType['turquoise'] = 6390;
        $cType['maroon'] = 6384;
        $cType['bronze'] = 6379;


        if (isset($cType[$string])) {
            return $cType[$string];
        } else {
            // echo $string."\n";
        }
    }

    public function getCarTransmissionId($string)
    {
        $string = trim($string);
        $cType  = array();

        $cType['manual']         = 6371;
        $cType['automatic']      = 6372;
        $cType['semi-automatic'] = 6374;

        if (isset($cType[$string])) {
            return $cType[$string];
        }
    }

    public function getCVTransmissionId($string)
    {
        $string = trim($string);
        $cType  = array();

        $cType['manual']         = 6408;
        $cType['automatic']      = 6409;
        $cType['semi-automatic'] = 6411;

        if (isset($cType[$string])) {
            return $cType[$string];
        }
    }

    /**
     * get matched category id
     *
     * @param string $string
     *
     * @return integer or null
     */
    private function prepareCategoryMapping($string)
    {
        if ($string == 'used cars') {
            $string = 'Cars';
        } elseif ($string == 'property & rentals') {
            $string = 'Property';
        } elseif ($string == 'vans & trucks' || $string == 'vans and trucks') {
            $string = 'Commercial Vehicles';
        } elseif ($string == 'computing & gaming') {
            $string = 'Computers and Tablets';
        } elseif ($string == 'shared accommodation') {
            $string = 'Share';
        } elseif ($string == 'singles clubs') {
            $string = 'Clubs';
        } elseif ($string == 'community & leisure') {
            $string = 'Community';
        } elseif ($string == 'music & instruments') {
            $string = 'Musical Instruments and Accessories';
        } elseif ($string == 'speedboats') {
            $string = 'Motor Boats';
        } elseif ($string == 'holidays & travel') {
            $string = 'Holidays and Travel';
        } elseif ($string =='fishing tackle') {
            return $string;
        }

        $category = $this->em->getRepository('FaEntityBundle:Category')->findOneBy(array('name' => $string));

        if ($category) {
            return $category->getId();
        } else {
            $Mappedcategory = $this->em->getRepository('FaEntityBundle:MappingCategory')->findOneBy(array('name' => $string));

            if ($Mappedcategory) {
                return $Mappedcategory->getNewId();
            } else {
                return $string;
            }
        }
    }

    /**
     * Update dimension.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function updateDimension($searchParam, $input, $output)
    {
        $count     = $this->getAgentCount($searchParam);
        $step      = 1000;
        $stat_time = time();
        $returnVar = null;

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        for ($i = 0; $i <= $count;) {
            if ($i == 0) {
                $low = 0;
            } else {
                $low = $i;
            }

            $i              = ($i + $step);
            $commandOptions = null;
            foreach ($input->getOptions() as $option => $value) {
                if ($value) {
                    $commandOptions .= ' --'.$option.'="'.$value.'"';
                }
            }

            if (isset($low)) {
                $commandOptions .= ' --offset='.$low;
            }

            $memoryLimit = '';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:update:search-agent '.$commandOptions.' '.$input->getArgument('action');
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }

        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }

    protected function getAgentQueryBuilder($searchParam)
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $adRepository  = $entityManager->getRepository('FaUserBundle:UserSearchAgent');
        $qb = $adRepository->createQueryBuilder(UserSearchAgentRepository::ALIAS);
        return $qb;
    }

    /**
     * Get query builder for ads.
     *
     * @param array $searchParam Search parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getAgentCount($searchParam)
    {
        $qb = $this->getAgentQueryBuilder($searchParam);
        $qb->select('COUNT('.$qb->getRootAlias().'.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * get first level parent
     *
     * @param integer $category_id
     *
     * @return object
     */
    private function getSecondLevelParent($category_id)
    {
        $cat = $this->em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($category_id, false, $this->getContainer());
        $ak = array();

        $ak = array_keys($cat);
        if (isset($ak['1'])) {
            return $this->em->getRepository('FaEntityBundle:Category')->getCategoryArrayById($ak['1'], $this->getContainer());
        } else {
            return null;
        }
    }
}
