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
use Fa\Bundle\UserBundle\Repository\RoleRepository;
use Fa\Bundle\PromotionBundle\Repository\UpsellRepository;

/**
 * This command is used to update dimensionads.
 *
 * @author Janak Jadeja <janak@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class ApplyOldAdPackageCommand extends ContainerAwareCommand
{

    const DATE = '2015-09-08';

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:apply-old-ad-package')
        ->setDescription("Apply package to old ads")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addArgument('action', InputArgument::REQUIRED, 'add')
        ->setHelp(
            <<<EOF
Actions:
Command:
   php app/console fa:update:apply-old-ad-package add
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

        $date = '2015-09-08';

        echo "RUN TIME DATE IS: >>>>>>>>>> ".$date."\N";
        $searchParam = array();

        if ($action == 'add') {
            $reader = new \EasyCSV\Reader(__DIR__."/ad_upsell.csv");
            $reader->setDelimiter(';');
            $batchSize = 100;
            $row = 0;
            $ad_id = array();

            while ($row = $reader->getRow()) {
                if (isset($row['ID']) && $row['ID']) {
                    $ad = $this->em->getRepository('FaAdBundle:Ad')->find($row['ID']);
                    if ($ad) {
                        $this->removeOldEntries($ad);
                        $d  = null;
                        $user   = $ad->getUser();
                        $ad_id[] = $row['ID'];

                        //get user roles.
                        $systemUserRoles = array_keys(RoleRepository::getUserTypes());
                        $userRolesArray  = array();
                        foreach ($user->getRoles() as $userRole) {
                            if (in_array($userRole->getId(), $systemUserRoles)) {
                                $userRolesArray[] = $userRole->getId();
                            }
                        }

                        $isUserHasPurchasedPackage = false;
                        $selectedPackagePrintId    = null;
                        //check if user has already purchased pkg or not
                        $adUserPackage = $this->em->getRepository('FaAdBundle:AdUserPackage')->getPurchasedAdPackage($ad->getId());
                        if ($adUserPackage) {
                            $isUserHasPurchasedPackage = true;
                        }
                        $categoryId = $ad->getCategory()->getId();
                        $parent     =  $this->getFirstLevelParent($categoryId);

                        $locationGroupIds    = $this->em->getRepository('FaAdBundle:AdLocation')->getLocationGroupIdForAd($ad->getId());

                        if ($locationGroupIds == null) {
                            $locationGroupIds = array(13);
                        }

                        if (count($locationGroupIds) == 1 && in_array(13, $locationGroupIds)) {
                            $row['print area'] = 'N';
                        } else {
                            $row['print area'] = 'Y';
                        }

                        $packages            = $this->em->getRepository('FaPromotionBundle:PackageRule')->getActivePackagesByCategoryId($categoryId, $locationGroupIds, $userRolesArray, array(), $this->getContainer());
                        $adExpiryDays        = $this->em->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($categoryId, $this->getContainer());
                        $packageRepository   = $this->em->getRepository('FaPromotionBundle:Package');

                        $pkg = array();

                        foreach ($packages as $package) {
                            $p = $package->getPackage();
                            $pkg[$p->getPackageText()] = $p->getId();
                        }

                        $appliedPackage = null;


                        if ($row['print area'] == 'N' && preg_match('/Top Ad/', $row['upsell'])) {
                            if ($parent['name'] == 'Adult') {
                                $appliedPackage = $pkg['Non Print Package 2'];
                            } else {
                                $appliedPackage = $pkg['Non Print Package 3'];
                            }
                        } elseif ($row['print area'] == 'N' && $row['upsell'] != '') {
                            if ($parent['name'] == 'Adult') {
                                $appliedPackage = $pkg['Non Print Package 1'];
                            } else {
                                $appliedPackage = $pkg['Non Print Package 2'];
                            }

                        } elseif ($row['print area'] == 'Y' && $row['print edition'] == '' && preg_match('/Top Ad/', $row['upsell'])) {
                             $appliedPackage = $pkg['Print Area Package 3'];
                        } elseif ($row['print area'] == 'Y' && $row['print edition'] == '' && $row['upsell'] != '') {
                                $appliedPackage = $pkg['Print Area Package 1'];
                        } elseif ($row['print area'] == 'Y' && $row['print edition'] != '') {
                            $edition_count =  count(explode(',', $row['print edition']));
                            $datetime1 = new \DateTime(self::DATE);
                            $datetime2 = new \DateTime($row['print expire']);
                            $interval = $datetime1->diff($datetime2);
                            $d = $interval->format('%R%a days');
                            $d  = intval($d);

                            echo "remaining days".$row['print expire'].'"'.$d.'"'."\n";

                            if ($row['upsell'] == 'Print booster') {
                                if ($d <= 7) {
                                    if ($edition_count == 1) {
                                        $appliedPackage = $pkg['Print Area Package 2'];
                                    } elseif ($edition_count == 3) {
                                        $appliedPackage = $pkg['Print Area Package 3'];
                                    } elseif ($edition_count == 5) {
                                        $appliedPackage = $pkg['Print Area Package 3'];
                                    }
                                } elseif ($d >= 7) {
                                    $appliedPackage = $pkg['Print Area Package 3'];
                                }

                            } elseif (preg_match('/Top Ad/', $row['upsell'])) {
                                if ($d <= 7) {
                                    $appliedPackage = $pkg['Print Area Package 3'];
                                } elseif ($d >= 7) {
                                    $appliedPackage = $pkg['Print Area Package 3'];
                                }

                            } elseif ($row['upsell'] != '') {
                                if ($d <= 7) {
                                    if ($edition_count == 1) {
                                        $appliedPackage = $pkg['Print Area Package 2'];
                                    } elseif ($edition_count == 3) {
                                        $appliedPackage = $pkg['Print Area Package 3'];
                                    } elseif ($edition_count == 5) {
                                        $appliedPackage = $pkg['Print Area Package 3'];
                                    }
                                } elseif ($d >= 7) {
                                    $appliedPackage = $pkg['Print Area Package 3'];
                                }
                            }
                        }

                        if ($appliedPackage) {
                            $packageObj = $this->em->getRepository('FaPromotionBundle:Package')->find($appliedPackage);
                            $value = array();

                            if ($d > 0) {
                                $w = ceil($d / 7);
                                $duration =  $w.'w';
                                $packagePrint    = $this->em->getRepository('FaPromotionBundle:PackagePrint')->findOneBy(array('duration' => $duration, 'package' => $packageObj->getId()));

                                if ($packagePrint) {
                                    $value['package'][$packageObj->getId()]['packagePrint'] = array('id' => $packagePrint->getId(), 'price' => 0, 'duration' => $packagePrint->getDuration());
                                    $value = $packageRepository->getPackageInfoForTransaction($packageObj, $ad, $user, $isUserHasPurchasedPackage, $selectedPackagePrintId);
                                    $selectedPackagePrintId = $packagePrint->getId();

                                } else {
                                    echo "print ad for duration -->".$duration."\n";
                                    $packagePrint    = $this->em->getRepository('FaPromotionBundle:PackagePrint')->findOneBy(array('package' => $packageObj->getId()));
                                    $selectedPackagePrintId = $packagePrint->getId();
                                    $value = $packageRepository->getPackageInfoForTransaction($packageObj, $ad, $user, $isUserHasPurchasedPackage, $selectedPackagePrintId);
                                    $value['package'][$packageObj->getId()]['packagePrint'] = array('id' => $packagePrint->getId(), 'price' => 0, 'duration' => $duration);

                                }
                            } else {
                                $value = $packageRepository->getPackageInfoForTransaction($packageObj, $ad, $user, $isUserHasPurchasedPackage, $selectedPackagePrintId);
                            }

                            if ($packageObj) {
                                $this->handlePackage($value['package'], $ad, $d);
                                echo 'Assigned package '.$packageObj->getPackageText().'>>>'.$packageObj->getTitle().'('.$packageObj->getId().')'.' To ad ID '.$ad->getId()."\n";

                            }

                            // activate the ad
                            $this->em->getRepository('FaAdBundle:Ad')->activateAd($ad->getId(), true, false, true);
                        }

                    }
                }
            }

            $newArrays = array_chunk($ad_id, 100);
            $this->em->flush();

            foreach ($newArrays as $newArray) {
                $idstring = implode(',', $newArray);
                $memoryLimit = '';
                if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                    $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
                }
                $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' bin/console fa:update:ad-solr-index --id="'.$idstring.'" --status="A" add';
                $output->writeln($command, true);
                passthru($command, $returnVar);

                if ($returnVar !== 0) {
                    $output->writeln('Error occurred during subtask', true);
                }
            }

        }
    }


    /**
     * Handle packages of transaction.
     *
     * @param array   $packages Packages array.
     * @param object  $adObj    Ad object.
     * @param integer $d        number of days
     */
    public function handlePackage(array $packages, $adObj, $d = null)
    {
        foreach ($packages as $package) {
            //get ad moderation flag.
            $packagePrint      = null;
            $addAdToModeration = false;

            if (isset($package['packagePrint'])) {
                $packagePrint = $package['packagePrint'];
            }

            // make entry into ad user package
            $adUserPackageId = $this->em->getRepository('FaAdBundle:AdUserPackage')->setAdUserPackage($package, $addAdToModeration, true);

            // handle upsells
            if (isset($package['upsell'])) {
                $this->handleUpsell($package['upsell'], $adUserPackageId, $adObj, $addAdToModeration, $packagePrint, $d);
            }

            return $addAdToModeration;
        }
    }

    private function removeOldEntries($adObj)
    {
        $adUserPackages = $this->em->getRepository('FaAdBundle:AdUserPackage')->findBy(array('ad_id' => $adObj->getId()));

        foreach ($adUserPackages as $adUserPackage) {
            $this->em->remove($adUserPackage);
        }

        $adUserPackagesUpsell = $this->em->getRepository('FaAdBundle:AdUserPackageUpsell')->findBy(array('ad_id' => $adObj->getId()));

        foreach ($adUserPackagesUpsell as $adUserPackageup) {
            $this->em->remove($adUserPackageup);
        }

        $adUserPackagesUpsell = $this->em->getRepository('FaAdBundle:AdPrint')->findBy(array('ad' => $adObj));

        foreach ($adUserPackagesUpsell as $adUserPackageup) {
            $this->em->remove($adUserPackageup);
        }

        //$this->em->flush();

    }

    /**
     * Handle upsell of transaction.
     *
     * @param array   $upsells           Upsells array.
     * @param integer $adUserPackageId   Ad user package id.
     * @param object  $adObj             Ad object.
     * @param boolean $addAdToModeration Send ad to moderate or not.
     * @param array   $packagePrint      Package print array.
     * @param integer $d               Package print array.
     */
    public function handleUpsell(array $upsells, $adUserPackageId, $adObj = null, $addAdToModeration = true, $packagePrint = null, $d = null)
    {
        $printUpsellFlag = false;
        foreach ($upsells as $upsell) {
            // make entry into ad user package upsell
            $adUserPackageUpsellId = $this->em->getRepository('FaAdBundle:AdUserPackageUpsell')->setAdUserPackageUpsell($upsell, $adUserPackageId, $addAdToModeration, true);
            //add ad to print.
            if ($adObj && $d > 0) {
                //check user has purchased print upsell else add free print ad.
                if (isset($upsell['type']) && $upsell['type'] == UpsellRepository::UPSELL_TYPE_PRINT_EDITIONS_ID) {
                    $printUpsellFlag = true;
                    $duration        = null;

                    if (is_array($packagePrint) && count($packagePrint)) {
                        $duration = $packagePrint['duration'];
                    }
                    $this->em->getRepository('FaAdBundle:AdPrint')->addPrintAd($upsell['value'], $duration, $adObj, $addAdToModeration, true, true, strtotime(self::DATE));
                }
            }
        }

        //if no print upsell then add free print upsell.
        if (!$printUpsellFlag) {
            $this->em->getRepository('FaAdBundle:AdPrint')->addPrintAd(1, '', $adObj, $addAdToModeration, false, true, strtotime(self::DATE));
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
}
