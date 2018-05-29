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
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to update ad yac number.

Command:
 - php app/console fa:update:ad-yac-number allocate --ad_id=XXX
 - php app/console fa:update:ad-yac-number setsold --ad_id=XXX
 - php app/console fa:update:ad-yac-number edit --ad_id=XXX
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
        $ad       = $this->em->getRepository('FaAdBundle:Ad')->find($input->getOption('ad_id'));

        if (!$ad) {
            $output->writeln('No ad found.', true);
        }

        if ($ad->getUser()) {
            if (!$ad->getUser()->getPhone()) {
                $output->writeln('Ad user has no phone number.', true);
            }
        } else {
            if (!$ad->getPhone()) {
                $output->writeln('Detached ad has no phone number.', true);
            }
        }

        $this->updateAdYacNumber($input, $output, $ad);
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
        $action       = $input->getArgument('action');
        $yacManager   = $this->getContainer()->get('fa.yac.manager');
        $adRepository = $this->em->getRepository('FaAdBundle:Ad');
        $yacManager->init();

        if ($ad->getUser()) {
            $phone = $ad->getUser()->getPhone();
        } else {
            $phone = $ad->getPhone();
        }

        if ($action == 'allocate') {
            $categoryId = $ad->getCategory()->getId();
            $adExpiryDays = $this->em->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($categoryId, $this->getContainer());
            if ($ad->getFuturePublishAt()) {
                $expiryDate = strtotime("+$adExpiryDays days", $ad->getFuturePublishAt());
            } elseif ($ad->getExpiresAt()) {
                $expiryDate = $ad->getExpiresAt();
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
