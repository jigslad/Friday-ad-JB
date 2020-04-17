<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2018, FMG
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
use Fa\Bundle\CoreBundle\Manager\CommonManager;
use Fa\Bundle\AdBundle\Repository\AdRepository;
use Fa\Bundle\UserBundle\Repository\UserRepository;
use Fa\Bundle\EntityBundle\Repository\EntityRepository;

/**
 * This command is used to update weekly refresh date for ad if weekly refresh upsell purchased.
 *
 * @author Rohini <rohini.subburam@fridyamediagroup.com>
 * @copyright 2018 Friday Media Group Ltd
 * @version 1.0
 */
class UpdateAdRefreshDateWithUserCommand extends ContainerAwareCommand
{
    /**
     * Configure.
     */
    protected function configure()
    {
        $this
        ->setName('fa:update:ad-refresh-date-with-user')
        ->setDescription("Update ad refresh date by user detail.")
        ->addOption('memory_limit', null, InputOption::VALUE_OPTIONAL, 'Memory limit of script execution', null)
        ->addOption('user_id', null, InputOption::VALUE_OPTIONAL, 'User ids', null)
        ->addOption('email', null, InputOption::VALUE_OPTIONAL, 'User email', null)
        ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset of the query', null)
        ->addOption('last_days', null, InputOption::VALUE_REQUIRED, 'update for last few days only', null)
        ->setHelp(
            <<<EOF
Cron: To be setup daily at mid-night.

Actions:
- Update ad refresh date by user detail.

Command:
 - php app/console fa:update:ad-refresh-date-with-user --user_id="xxxx"
 - php app/console fa:update:ad-refresh-date-with-user --last_days=1
 - php app/console fa:update:ad-refresh-date-with-user --email="xxxx@xxxx.xxx"
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
        if (!$input->getOption('last_days')) {
            $output->writeln('Please enter last_days.', true);
            return false;
        }

        //get options passed in command
        

        $adids = null;
        //$adids = $this->em->getRepository('FaAdBundle:AdUserPackageUpsell')->getAdsByTypeValue('2',$lastDays,$reqids);
        
        $searchParam                        = array();
        $searchParam['entity_ad_status']    = array('id' => array(\Fa\Bundle\EntityBundle\Repository\EntityRepository::AD_STATUS_LIVE_ID));
        

        if ($adids) {
            $searchParam['ad'] = array('id' => $adids);
        }

        $offset = $input->getOption('offset');
        /*if ($lastDays) {
            $date = date('d/m/Y', strtotime('-'.$lastDays.' day'));
            $searchParam['ad']['weekly_refresh_at_from_to'] =  $date.'|'.$date;
        }*/

        if (isset($offset)) {
            $this->updateAdRefreshDateWithOffset($searchParam, $input, $output);
        } else {
            $this->updateAdRefreshDate($searchParam, $input, $output);

            //send userwise email
            $memoryLimit = '';
            if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
                $memoryLimit = ' -d memory_limit='.$input->getOption("memory_limit");
            }
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:process-email-queue --email_identifier="confirmation_of_ad_refreshing"';
            $output->writeln($command, true);
            passthru($command, $returnVar);

            if ($returnVar !== 0) {
                $output->writeln('Error occurred during subtask', true);
            }
        }
    }

    /**
     * Update refresh date for ad with given offset.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function updateAdRefreshDateWithOffset($searchParam, $input, $output)
    {
        $qb          = $this->getAdQueryBuilder(false, $input);
        $step        = 100;
        $offset      = 0;

        $qb->setFirstResult($offset);
        $qb->setMaxResults($step);

        $ads = $qb->getQuery()->getResult();


        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        
        $memoryLimit = '';
        if ($input->hasOption("memory_limit") && $input->getOption("memory_limit")) {
            $memoryLimit = ' -d memory_limit=' . $input->getOption("memory_limit");
        }

        foreach ($ads as $ad) {
            $user = ($ad->getUser() ? $ad->getUser() : null);
            $ad->setWeeklyRefreshAt(time());
            $entityManager->persist($ad);
            $entityManager->flush($ad);
            $userId = $user ? $user->getId(): null;
            if (!$ad->getIsFeedAd()) {
                $this->em->getRepository('FaMessageBundle:NotificationMessageEvent')->setNotificationEvents('advert_refreshed', $ad->getId(), $userId);
            }

            //send email only if ad has user and status is active and not feed ad.
            if (!$ad->getIsFeedAd() && $user && CommonManager::checkSendEmailToUser($userId, $this->getContainer())) {
                //$this->em->getRepository('FaAdBundle:Ad')->sendRefreshAdEmail($ad, 'confirmation_of_ad_refreshing', null, $this->getContainer());
                $this->em->getRepository('FaEmailBundle:EmailQueue')->addEmailToQueue('confirmation_of_ad_refreshing', $user, $ad, $this->getContainer());
            }
            $command = $this->getContainer()->getParameter('fa.php.path') . $memoryLimit . ' ' . $this->getContainer()
                ->get('kernel')
                ->getRootDir() . '/console fa:update:ad-solr-index --id="' . $ad->getId() . '" --status="A" update';

            $output->writeln('Refresh date is updated for ad id: '.$ad->getId().' User Id:'.($user ? $user->getId() : null), true);
        }

        $output->writeln('Memory Allocated: '.((memory_get_peak_usage(true) / 1024) / 1024).' MB', true);
    }

    /**
     * Update refresh date for ad.
     *
     * @param array  $searchParam Search parameters.
     * @param object $input       Input object.
     * @param object $output      Output object.
     */
    protected function updateAdRefreshDate($searchParam, $input, $output)
    {
        $qb        = $this->getAdQueryBuilder(true, $input);
        $count     = $qb->getQuery()->getSingleScalarResult();
        $step      = 100;
        $stat_time = time();

        $output->writeln('SCRIPT START TIME '.date('d-m-Y H:i:s', $stat_time), true);
        $output->writeln('Total ads : '.$count, true);
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
            $command = $this->getContainer()->getParameter('fa.php.path').$memoryLimit.' '.$this->getContainer()->getParameter('project_path').'/console fa:update:ad-refresh-date-with-user '.$commandOptions;
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
     * @param boolean $onlyCount count only.
     * @param array $input input parameters.
     *
     * @return Doctrine_Query Object.
     */
    protected function getAdQueryBuilder($onlyCount = false, $input)
    {
        $user_id        = $input->getOption('user_id');
        $offset         = $input->getOption('offset');
        $lastDays       = $input->getOption('last_days');
        $user_email     = $input->getOption('email');

        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $adRepository  = $entityManager->getRepository('FaAdBundle:Ad');

        $qb = $adRepository->getBaseQueryBuilder();

        if ($onlyCount) {
            $qb->select('COUNT('.AdRepository::ALIAS.'.id)');
        } else {
            $qb->select(AdRepository::ALIAS);
        }
        $qb->innerJoin(AdRepository::ALIAS.'.user', UserRepository::ALIAS, 'WITH', AdRepository::ALIAS.'.user = '.UserRepository::ALIAS.'.id');
        $qb->where(AdRepository::ALIAS.'.status = :adStatus')->setParameter('adStatus', EntityRepository::AD_STATUS_LIVE_ID);
        $qb->andWhere(UserRepository::ALIAS.'.status = :userStatus')->setParameter('userStatus', EntityRepository::USER_STATUS_ACTIVE_ID);

        if (!empty($user_id)) {
            $qb->andWhere(UserRepository::ALIAS.'.id = :userId')
                ->setParameter('userId', $user_id);
        }
        
        if (!empty($user_email)) {
            $qb->andWhere(UserRepository::ALIAS.'.email = :userEmail')
                ->setParameter('userEmail', $user_email);
        }
        
        $qb->orderBy(AdRepository::ALIAS.'.id');

        return $qb;
    }
}
