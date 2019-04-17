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
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;
use Fa\Bundle\EntityBundle\Repository\CategoryRepository;

/**
 * This command is used to update ad yac number.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class UpdateAdYacNumberCommand extends ContainerAwareCommand
{
    /**
     * Entity manager.
     *
     * @var object
     */
    private $em;

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:ad-yac-number')
        ->setDescription("Update ad yac number.")
        ->addArgument('action', InputArgument::REQUIRED, 'allocate or setsold or edit')
        ->addOption('ad_id', null, InputOption::VALUE_REQUIRED, 'Ad id', null)
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('start_date', null, InputOption::VALUE_OPTIONAL, 'startDate in d-m-Y', 0)
        ->addOption('end_date', null, InputOption::VALUE_OPTIONAL, 'endDate in d-m-Y', 0)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to update ad yac number.

Command:
 - php app/console fa:update:ad-yac-number allocate --ad_id=XXX
 - php app/console fa:update:ad-yac-number setsold --ad_id=XXX
 - php app/console fa:update:ad-yac-number edit --ad_id=XXX
 - php app/console fa:update:ad-yac-number allocate --start_date="28-11-2017" --end_date="28-12-2017"
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
        //get arguments passed in command
        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        //get options passed in command
        $offset = $input->getOption('offset');

        if (isset($offset)) {
            $this->updateYacNumberWithOffset($input, $output);
        } else {
            $this->updateYacNumber($input, $output);
        }

        //$ad       = $this->em->getRepository('FaAdBundle:Ad')->find($input->getOption('ad_id'));
    }
    
    /**
     * Update yac number with given offset.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    
    protected function updateYacNumberWithOffset($input, $output)
    {
        $action       = $input->getArgument('action');
        $objQB  = $this->getMainQueryBuilder($input, $action, false);
        $step   = 100;
        $offset      = $input->getOption('offset');
        $em          = $this->getContainer()->get('doctrine')->getManager();

        $objQB->setFirstResult($offset);
        $objQB->setMaxResults($step);

        $objAds = $objQB->getQuery()->execute();

        if (!$objAds) {
            $output->writeln('No ads found.', true);
        }
        //echo '<pre>';print_r($objAds);die;
        foreach ($objAds as $objAd) {
            $ad = $this->em->getRepository('FaAdBundle:Ad')->find($objAd['ad_Ref']);
            $this->updateAdYacNumber($input, $output, $ad);
        }
    }

    /**
    * update yac number
    *
    * @param object $input       Input object.
    * @param object $output      Output object.
    */
    protected function updateYacNumber($input, $output)
    {
        $action       = $input->getArgument('action');
        $objQB  = $this->getMainQueryBuilder($input, $action, true);
        $count  = $objQB->getQuery()->getSingleScalarResult();

        $step      = 100;
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        $output->writeln("Total records to update yac number are: ".$count);
        for ($i = 0; $i <= $count;) {
            if ($i == 0) {
                $low = 0;
            } else {
                $low = $i;
            }

            $i              = ($i + $step);
            $commandOptions = null;
            //echo '<pre>';print_r($input->getOptions());die;
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:update:ad-yac-number '.$action.' '.$commandOptions;
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }

        $output->writeln('SCRIPT END TIME '.date('d-m-Y H:i:s', time()), true);
        $output->writeln('TIME TAKEN TO EXECUTE SCRIPT '.((time() - $stat_time) / 60), true);
    }
    /**
     * Get query builder for ads.
     *
     * @return Doctrine_Query Object.
     */
    protected function getMainQueryBuilder($input, $action, $onlyCount = false)
    {
        $adId = intval($input->getOption('ad_id'));
        $startDate = strtotime($input->getOption('start_date'));
        $endDate = strtotime($input->getOption('end_date'));
        
        //echo $startDate.'==='.$endDate.'<br>';
        $adRepository  = $this->em->getRepository('FaAdBundle:Ad');
        $qb = $adRepository->getBaseQueryBuilder();

        if ($onlyCount) {
            $qb->select('COUNT('.AdRepository::ALIAS.'.id)');
        } else {
            $qb->select(AdRepository::ALIAS.'.id AS ad_Ref', UserRepository::ALIAS.'.id AS userId', AdRepository::ALIAS.'.privacy_number AS privacy_number', UserRepository::ALIAS.'.is_private_phone_number  AS is_private_phone_number', UserRepository::ALIAS.'.email  AS email');
        }
        $qb->innerJoin(AdRepository::ALIAS.'.user', UserRepository::ALIAS, 'WITH', AdRepository::ALIAS.'.user = '.UserRepository::ALIAS.'.id');

        $qb->where(UserRepository::ALIAS.'.status = :userStatus')->setParameter('userStatus', EntityRepository::USER_STATUS_ACTIVE_ID);
        $qb->andWhere(AdRepository::ALIAS.'.status = :adStatus')->setParameter('adStatus', EntityRepository::AD_STATUS_LIVE_ID);
        //$qb->andWhere(AdRepository::ALIAS.'.use_privacy_number = 1');
        $qb->andWhere(UserRepository::ALIAS.'.is_private_phone_number = 1');
        if ($action=='allocate') {
            $qb->andWhere(AdRepository::ALIAS.'.privacy_number IS NULL');
        } else {
            $qb->andWhere(AdRepository::ALIAS.'.privacy_number IS not NULL');
        }
        
        $qb->andWhere('('.AdRepository::ALIAS.'.phone IS Not NULL or '.UserRepository::ALIAS.'.phone IS Not NULL)');
        
        if (!empty($adId)) {
            $qb->andWhere(AdRepository::ALIAS.'.id = '.$adId);
        }
        if (!empty($startDate) && !empty($endDate)) {
            $qb->andWhere(AdRepository::ALIAS.'.updated_at >= :startDate')
                ->andWhere(AdRepository::ALIAS.'.updated_at <= :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate);
        }

        $qb->orderBy(UserRepository::ALIAS.'.id');

        return $qb;
    }
    /**
     * Update ad yac number.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     * @param object $ad     Ad object.
     */
    protected function updateAdYacNumber($input, $output, $ad)
    {
        $action = $input->getArgument('action');
        $yacManager   = $this->getContainer()->get('fa.yac.manager');
        $adRepository = $this->em->getRepository('FaAdBundle:Ad');
        $yacManager->init();

        /*if ($ad->getUser()) {
            $phone = $ad->getUser()->getPhone();
        } else {
            $phone = $ad->getPhone();
        }*/
        if ($ad->getPhone()) {
            $phone = $ad->getPhone();
        } elseif ($ad->getUser()) {
            $phone = $ad->getUser()->getPhone();
        }


        if ($action == 'allocate') {
            $categoryId = $ad->getCategory()->getId();
            $adExpiryDays = $this->em->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($categoryId, $this->getContainer());
            
            $getActivePackage = array();
            $getActivePackage = $this->em->getRepository('FaAdBundle:AdUserPackage')->getActiveAdPackage($ad->getId());
            if(!empty($getActivePackage)) {               
                $selectedPackageObj = $this->em->getRepository('FaPromotionBundle:Package')->findOneBy(array('id' => $getActivePackage->getPackage()->getId()));
                if ($selectedPackageObj->getDuration()) {
                    $getLastCharacter = substr($selectedPackageObj->getDuration(),-1);
                    $noInDuration = substr($selectedPackageObj->getDuration(),0, -1);
                    if($getLastCharacter=='m') { $adExpiryDays = $noInDuration*28;   }
                    elseif($getLastCharacter=='d') { $adExpiryDays = $noInDuration; }
                    else { $adExpiryDays = $selectedPackageObj->getDuration(); }
                }               
            }
            
            if ($ad->getFuturePublishAt()) {
                $expiryDate = strtotime("+$adExpiryDays days", $ad->getFuturePublishAt());
           // } elseif ($ad->getExpiresAt()) {
                //$expiryDate = $ad->getExpiresAt();
            } else {
                $expiryDate = strtotime("+$adExpiryDays days");
            }

            $expiryDate = $adRepository->getYacExpiry($ad->getId(), $expiryDate);

            // if no privacy number assigned then assigned new one else extend.
            if (!$ad->getPrivacyNumber()) {
                $info = '';
                if ($categoryId) {
                    $info = $this->em->getRepository('FaEntityBundle:Category')->getRootCategoryName($categoryId, $this->getContainer());
                }

                $response = $yacManager->allocateYacNumber($ad->getId(), $phone, $expiryDate, $info);

                if (isset($response['YacNumber']) && $response['YacNumber']) {
                    $ad->setPrivacyNumber($response['YacNumber']);
                    $this->em->persist($ad);
                    $this->em->flush($ad);
                    $output->writeln('Yac number has been assigned to ad id:'.$ad->getId(), true);
                } elseif (isset($response['error']) && $response['error']) {
                    $output->writeln('Yac error for ad id '.$ad->getId().': '.$response['error'], true);
                }
            } elseif ($ad->getPrivacyNumber()) {
                $yacResponse = $yacManager->extendYacNumber($ad->getPrivacyNumber(), $expiryDate);
                if ($yacResponse['errorCode'] && ($yacResponse['errorCode'] == '-117' || $yacResponse['errorCode'] == 'XML_ERROR')) {
                    $categoryNames = array_values($this->em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($ad->getCategory()->getId(), false, $this->getContainer()));
                    $yacResponse = $yacManager->allocateYacNumber($ad->getId(), $phone, $expiryDate, $categoryNames[0]);
                    if (!$yacResponse['error'] && $yacResponse['YacNumber']) {
                        $ad->setPrivacyNumber($yacResponse['YacNumber']);
                        $this->em->persist($ad);
                        $this->em->flush($ad);
                        $output->writeln('Yac number has been assigned to ad id:'.$ad->getId(), true);
                    }
                } elseif ($yacResponse) {
                    $output->writeln('Yac number has been assigned to ad id:'.$ad->getId(), true);
                }
            }
        } elseif ($action == 'setsold') {
            if ($ad->getPrivacyNumber()) {
                $response = $yacManager->removeYacNumber($ad->getPrivacyNumber());
                if ($response === true) {
                    $ad->setPrivacyNumber(null);
                    $ad->setUsePrivacyNumber(null);
                    $this->em->persist($ad);
                    $this->em->flush($ad);
                    $output->writeln('Yac number set sold for ad id:'.$ad->getId(), true);
                } elseif (isset($response['error']) && $response['error']) {
                    $output->writeln('Yac error for ad id '.$ad->getId().': '.$response['error'], true);
                }
            }
        } elseif ($action == 'edit') {
            if ($ad->getPrivacyNumber()) {
                $response = $yacManager->editPhoneNumber($ad->getPrivacyNumber(), $phone);
                if ($response === true) {
                    $output->writeln('Yac number has been edited for ad id:'.$ad->getId(), true);
                } elseif (isset($response['error']) && $response['error']) {
                    $output->writeln('Yac error for ad id '.$ad->getId().': '.$response['error'], true);
                }
            }
        }

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }
}
