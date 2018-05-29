<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\UserBundle\Command;

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
 * This command is used to update user statistics.
 *
 * @author Amit Limbadia <amitl@aspl.in>
 * @copyright  2014 Friday Media Group Ltd
 * @version v1.0
 */
class UpdateUserAdYacNumberCommand extends ContainerAwareCommand
{
    /**
     * Limit total records to process.
     *
     * @var integer
     */
    private $limit = 200;

    /**
     * Entity manager.
     *
     * @var object
     */
    private $em;

    /**
     * User entity.
     *
     * @var object
     */
    private $userObj;

    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:user-ad-yac-number')
        ->setDescription("Update user ad statistics.")
        ->addArgument('action', InputArgument::REQUIRED, 'allocate or setsold or edit')
        ->addOption('user_id', null, InputOption::VALUE_REQUIRED, 'User id', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->setHelp(
            <<<EOF
Cron: To be setup to run at mid-night.

Actions:
- Can be run to update user's ad yac number.

Command:
 - php app/console fa:update:user-ad-yac-number allocate --user_id=1
 - php app/console fa:update:user-ad-yac-number setsold --user_id=1
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
        $offset         = $input->getOption('offset');
        $userId         = $input->getOption('user_id');
        $this->em       = $this->getContainer()->get('doctrine')->getManager();
        $userRepository = $this->em->getRepository('FaUserBundle:User');
        $this->userObj  = $userRepository->find($userId);


        if (!$this->userObj) {
            $output->writeln('No user found.', true);
        } else if (!$this->userObj->getPhone()) {
            $output->writeln('Use has no phone number.', true);
        } else {
            if (isset($offset)) {
                $this->updateUserAdYacNumberWithOffset($input, $output);
            } else {
                $this->updateUserAdYacNumber($input, $output);
            }
        }
    }

    /**
     * Update user ad yac number.
     *
     * @param object $input  Input object.
     * @param object $output Output object.
     */
    protected function updateUserAdYacNumber($input, $output)
    {
        $userId    = $input->getOption('user_id');
        $count     = $this->getUserTotalAdCount($userId);
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        for ($i = 0; $i <= $count;) {
            if ($i == 0) {
                $low = 0;
            } else {
                $low = $i;
            }

            $i              = ($i + $this->limit);
            $commandOptions = null;
            foreach ($input->getOptions() as $option => $value) {
                if ($value) {
                    $commandOptions .= ' --'.$option.'='.$value;
                }
            }

            if (isset($low)) {
                $commandOptions .= ' --offset='.$low;
            }

            $memoryLimit = '';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->get('kernel')->getRootDir().'/console fa:update:user-ad-yac-number '.$input->getArgument('action').' '.$commandOptions;
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
     * Update user total ad count with offset.
     *
     * @param object $input  Input object
     * @param object $output Output object
     */
    protected function updateUserAdYacNumberWithOffset($input, $output)
    {
        $action             = $input->getArgument('action');
        $offset             = $input->getOption('offset');
        $userId             = $input->getOption('user_id');
        $yacManager         = $this->getContainer()->get('fa.yac.manager');
        $categoryRepository = $this->em->getRepository('FaEntityBundle:Category');
        $adRepository       = $this->em->getRepository('FaAdBundle:Ad');
        $yacManager->init();

        $userAds = $this->getUserAdCountResult($userId, $offset, $this->limit);

        foreach ($userAds as $userAd) {
            if ($action == 'allocate') {
                $categoryId = $userAd['category_id'];
                $adExpiryDays = $this->em->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($categoryId, $this->getContainer());
                if ($userAd['future_publish_at']) {
                    $expiryDate = strtotime("+$adExpiryDays days", $userAd['future_publish_at']);
                } elseif ($userAd['expires_at']) {
                    $expiryDate = $userAd['expires_at'];
                } else {
                    $expiryDate = strtotime("+$adExpiryDays days");
                }

                $expiryDate = $adRepository->getYacExpiry($userAd['ad_id'], $expiryDate);

                if (!$userAd['privacy_number']) {
                    $info = '';
                    if ($userAd['category_id']) {
                        $catPath = array_values($categoryRepository->getCategoryPathArrayById($userAd['category_id'], false));
                        if (isset($catPath[0])) {
                            $info = $catPath[0];
                        }
                    }

                    $adExpiryDays  = $this->em->getRepository('FaCoreBundle:ConfigRule')->getExpirationDays($userAd['category_id'], $this->getContainer());
                    $expiryDate    = strtotime("+$adExpiryDays days");
                    $response = $yacManager->allocateYacNumber($userAd['ad_id'], $this->userObj->getPhone(), $expiryDate, $info);

                    if (isset($response['YacNumber']) && $response['YacNumber']) {
                        $adObj = $adRepository->find($userAd['ad_id']);
                        $adObj->setPrivacyNumber($response['YacNumber']);
                        $this->em->persist($adObj);
                        $output->writeln('Yac no assiged to ad id:'.$userAd['ad_id'], true);
                    } elseif (isset($response['error']) && $response['error']) {
                        $output->writeln('Yac error for ad id '.$userAd['ad_id'].': '.$response['error'], true);
                    }
                } elseif ($userAd['privacy_number']) {
                    $yacResponse = $yacManager->extendYacNumber($userAd['privacy_number'], $expiryDate);
                    if ($yacResponse['errorCode'] && ($yacResponse['errorCode'] == '-117' || $yacResponse['errorCode'] == 'XML_ERROR')) {
                        $categoryNames = array_values($this->em->getRepository('FaEntityBundle:Category')->getCategoryPathArrayById($categoryId, false, $this->getContainer()));
                        $yacResponse = $yacManager->allocateYacNumber($userAd['ad_id'], $this->userObj->getPhone(), $expiryDate, $categoryNames[0]);
                        if (!$yacResponse['error'] && $yacResponse['YacNumber']) {
                            $adObj = $adRepository->find($userAd['ad_id']);
                            $adObj->setPrivacyNumber($yacResponse['YacNumber']);
                            $this->em->persist($adObj);
                            $output->writeln('Yac no assiged to ad id:'.$userAd['ad_id'], true);
                        }
                    } elseif ($yacResponse) {
                        $output->writeln('Yac no assiged to ad id:'.$userAd['ad_id'], true);
                    }
                }
            } elseif ($action == 'setsold') {
                if ($userAd['privacy_number']) {
                    $response = $yacManager->removeYacNumber($userAd['privacy_number']);
                    if ($response === true) {
                        $adObj = $adRepository->find($userAd['ad_id']);
                        $adObj->setPrivacyNumber('');
                        $this->em->persist($adObj);
                        $output->writeln('Yac no set sold for ad id:'.$userAd['ad_id'], true);
                    } elseif (isset($response['error']) && $response['error']) {
                        $output->writeln('Yac error for ad id '.$userAd['ad_id'].': '.$response['error'], true);
                    }
                }
            } elseif ($action == 'edit') {
                if ($userAd['privacy_number']) {
                    $response = $yacManager->editPhoneNumber($userAd['privacy_number'], $this->userObj->getPhone());
                    if ($response === true) {
                        $output->writeln('Yac no edited for ad id:'.$userAd['ad_id'], true);
                    } elseif (isset($response['error']) && $response['error']) {
                        $output->writeln('Yac error for ad id '.$userAd['ad_id'].': '.$response['error'], true);
                    }
                }
            }
        }

        $this->em->flush();
        $this->em->clear();

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Get query builder for ads.
     *
     * @param integer $userId User id.
     *
     * @return integer
     */
    protected function getUserTotalAdCount($userId)
    {
        $userRepository  = $this->em->getRepository('FaUserBundle:User');
        $query = $userRepository->getBaseQueryBuilder()
            ->select('COUNT('.AdRepository::ALIAS.'.id) as ad_count')
            ->innerJoin('Fa\Bundle\AdBundle\Entity\Ad', AdRepository::ALIAS, 'WITH', UserRepository::ALIAS.'.id = '.AdRepository::ALIAS.'.user')
            ->innerJoin(AdRepository::ALIAS.'.category', CategoryRepository::ALIAS)
            ->andWhere(AdRepository::ALIAS.'.user = :userId')
            ->setParameter('userId', $userId)
            ->andWhere(AdRepository::ALIAS.'.status IN (:statusIds)')
            ->setParameter('statusIds', array(EntityRepository::AD_STATUS_LIVE_ID));

        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     * Get user ad count results.
     *
     * @param integer $userId User id.
     * @param integer $offset Offset.
     * @param integer $limit  Limit.
     *
     * @return Doctrine_Query object.
     */
    protected function getUserAdCountResult($userId, $offset, $limit)
    {
        $userRepository  = $this->em->getRepository('FaUserBundle:User');
        $query = $userRepository->getBaseQueryBuilder()
            ->select(AdRepository::ALIAS.'.id as ad_id', AdRepository::ALIAS.'.privacy_number', CategoryRepository::ALIAS.'.id as category_id', AdRepository::ALIAS.'.expires_at', AdRepository::ALIAS.'.future_publish_at')
            ->innerJoin('Fa\Bundle\AdBundle\Entity\Ad', AdRepository::ALIAS, 'WITH', UserRepository::ALIAS.'.id = '.AdRepository::ALIAS.'.user')
            ->innerJoin(AdRepository::ALIAS.'.category', CategoryRepository::ALIAS)
            ->andWhere(AdRepository::ALIAS.'.user = :userId')
            ->setParameter('userId', $userId)
            ->andWhere(AdRepository::ALIAS.'.status IN (:statusIds)')
            ->setParameter('statusIds', array(EntityRepository::AD_STATUS_LIVE_ID))
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $query->getQuery()->getArrayResult();
    }
}
